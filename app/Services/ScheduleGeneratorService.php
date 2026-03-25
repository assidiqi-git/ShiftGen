<?php

namespace App\Services;

use App\Exceptions\ScheduleConflictException;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleGeneratorService
{
    /** @var Collection<int, Shift> */
    private Collection $shifts;

    /** @var Collection<int, Employee> */
    private Collection $employees;

    public function generate(Carbon $from, Carbon $to, ?int $scheduleSetId = null): void
    {
        $this->shifts = Shift::orderBy('sort_order')->get();
        $this->employees = Employee::all();

        if ($this->shifts->isEmpty()) {
            throw new ScheduleConflictException('Belum ada shift yang terdapat di master data. Tambahkan shift terlebih dahulu.');
        }

        if ($this->employees->isEmpty()) {
            throw new ScheduleConflictException('Belum ada pegawai yang terdaftar. Tambahkan pegawai terlebih dahulu.');
        }

        $this->validateTotalHours();

        // Track cumulative work hours per employee (for overtime assignment)
        $workHours = $this->employees->mapWithKeys(fn ($e) => [$e->id => 0.0])->toArray();

        $current = $from->copy()->startOfDay();
        while ($current->lte($to)) {
            $this->generateForDay($current->copy(), $workHours, $scheduleSetId);
            $current->addDay();
        }
    }

    /** @param array<int, float> $workHours */
    private function generateForDay(Carbon $date, array &$workHours, ?int $scheduleSetId = null): void
    {
        $shiftCount = $this->shifts->count();
        $employeeCount = $this->employees->count();

        // Delete any existing draft schedules for this date (scoped by schedule set if provided) before regenerating
        Schedule::when($scheduleSetId !== null, fn ($q) => $q->where('schedule_set_id', $scheduleSetId))
            ->where('date', $date->toDateString())
            ->draft()
            ->delete();

        // We need to assign at least one employee per shift.
        // If we have fewer employees than shifts, overtime kicks in.
        $assignments = $this->buildAssignments($shiftCount, $employeeCount, $workHours);

        foreach ($assignments as [$shiftIndex, $employeeId, $isOvertime]) {
            /** @var Shift $shift */
            $shift = $this->shifts->get($shiftIndex);

            Schedule::create([
                'employee_id' => $employeeId,
                'shift_id' => $shift->id,
                'date' => $date->toDateString(),
                'is_overtime' => $isOvertime,
                'status' => 'draft',
                'schedule_set_id' => $scheduleSetId,
            ]);

            $workHours[$employeeId] += (float) $shift->duration_hours;
        }
    }

    /**
     * Build an assignment list: [[shiftIndex, employeeId, isOvertime], ...]
     *
     * Rules:
     * 1. Each shift gets exactly one employee.
     * 2. An employee can appear more than once (overtime) only if there are not enough employees.
     * 3. When an employee is assigned to multiple shifts in the same day, consecutive shifts
     *    (adjacent sort_order) are NOT allowed — at least one shift gap is required.
     *
     * @param  array<int, float>  $workHours
     * @return array<array{int, int, bool}>
     */
    private function buildAssignments(int $shiftCount, int $employeeCount, array $workHours): array
    {
        // Sort employees by cumulative work hours ascending (least hours first)
        $sortedEmployees = $this->employees
            ->sortBy(fn ($e) => $workHours[$e->id])
            ->values();

        // Track which shift indices an employee has been assigned today
        $assignedShiftsToday = $this->employees->mapWithKeys(fn ($e) => [$e->id => []])->toArray();

        $assignments = [];

        for ($shiftIdx = 0; $shiftIdx < $shiftCount; $shiftIdx++) {
            $assigned = $this->pickEmployee($shiftIdx, $sortedEmployees, $assignedShiftsToday, $workHours, $shiftCount);

            if ($assigned === null) {
                throw new ScheduleConflictException(
                    'Tidak bisa mengisi Shift #'.($shiftIdx + 1).' karena semua pegawai melanggar aturan jeda shift. '.
                    'Tambahkan lebih banyak pegawai atau kurangi jumlah shift.'
                );
            }

            [$employeeId, $isOvertime] = $assigned;
            $assignments[] = [$shiftIdx, $employeeId, $isOvertime];
            $assignedShiftsToday[$employeeId][] = $shiftIdx;
        }

        return $assignments;
    }

    /**
     * Pick the best available employee for the given shift index.
     *
     * @param  Collection<int, Employee>  $sortedEmployees
     * @param  array<int, int[]>  $assignedShiftsToday
     * @param  array<int, float>  $workHours
     * @return array{int, bool}|null [employeeId, isOvertime] or null
     */
    private function pickEmployee(
        int $shiftIdx,
        Collection $sortedEmployees,
        array $assignedShiftsToday,
        array $workHours,
        int $shiftCount
    ): ?array {
        // First pass: find employee not yet assigned today (not overtime)
        foreach ($sortedEmployees as $employee) {
            if (
                empty($assignedShiftsToday[$employee->id]) &&
                $this->isGapSatisfied($shiftIdx, $assignedShiftsToday[$employee->id])
            ) {
                return [$employee->id, false];
            }
        }

        // Second pass: allow overtime — find employee with the fewest hours that satisfies gap rule
        $candidates = $sortedEmployees->filter(
            fn ($e) => $this->isGapSatisfied($shiftIdx, $assignedShiftsToday[$e->id])
        );

        if ($candidates->isEmpty()) {
            return null;
        }

        $best = $candidates->sortBy(fn ($e) => $workHours[$e->id])->first();

        return [$best->id, true];
    }

    /**
     * An employee can be placed on $shiftIdx only if they are NOT already assigned
     * to an adjacent shift (shiftIdx ± 1).
     *
     * @param  int[]  $alreadyAssigned  list of shift indices already assigned to this employee today
     */
    private function isGapSatisfied(int $shiftIdx, array $alreadyAssigned): bool
    {
        foreach ($alreadyAssigned as $existing) {
            if (abs($existing - $shiftIdx) === 1) {
                return false; // adjacent — violates gap rule
            }
        }

        return true;
    }

    /**
     * Validate that the total shift hours per day does not exceed 24.
     */
    private function validateTotalHours(): void
    {
        $total = $this->shifts->sum('duration_hours');
        if ($total > 24) {
            throw new ScheduleConflictException("Total durasi semua shift ({$total} jam) melebihi 24 jam sehari.");
        }
    }
}
