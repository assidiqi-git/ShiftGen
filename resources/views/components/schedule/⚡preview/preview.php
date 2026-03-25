<?php

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleSet;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $date_from = '';

    public string $date_to = '';

    public ?int $schedule_set_id = null;

    public function mount(ScheduleSet $scheduleSet): void
    {
        $this->date_from = now()->startOfWeek()->subDay()->toDateString();
        $this->date_to = now()->endOfWeek()->addDay()->toDateString();

        $this->schedule_set_id = $scheduleSet->id;
        $this->date_from = $scheduleSet->date_from->toDateString();
        $this->date_to = $scheduleSet->date_to->toDateString();
    }

    private function toast(string $message, bool $success = true): void
    {
        $this->dispatch('schedule-preview-toast', message: $message, type: $success ? 'success' : 'error');
    }

    /**
     * Called by Alpine/Livewire drag-and-drop when a card is dropped.
     */
    public function updateSchedule(int $scheduleId, int $newShiftId, string $newDate): void
    {
        $schedule = Schedule::with('shift')->findOrFail($scheduleId);
        $employeeId = $schedule->employee_id;
        $oldDate = $schedule->date->toDateString();

        $newShiftSortOrder = Shift::whereKey($newShiftId)->value('sort_order');
        if ($newShiftSortOrder === null) {
            $this->toast('Tidak bisa memindahkan: shift tujuan tidak ditemukan.', false);

            return;
        }

        $hasAdjacentShift = Schedule::query()
            ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
            ->where('schedules.employee_id', $employeeId)
            ->whereDate('schedules.date', $newDate)
            ->when(
                $this->schedule_set_id !== null,
                fn ($q) => $q->where('schedules.schedule_set_id', $this->schedule_set_id)
            )
            ->where('schedules.id', '!=', $scheduleId)
            ->whereIn('shifts.sort_order', [$newShiftSortOrder - 1, $newShiftSortOrder + 1])
            ->exists();

        if ($hasAdjacentShift) {
            $this->toast('Tidak bisa memindahkan: melanggar aturan jeda ≥ 1 shift. Pegawai ini sudah ada di shift yang bersebelahan.', false);

            return;
        }

        $schedule->update([
            'shift_id' => $newShiftId,
            'date' => $newDate,
        ]);

        $this->recalculateOvertimeForEmployeeOnDate($employeeId, $oldDate);
        if ($oldDate !== $newDate) {
            $this->recalculateOvertimeForEmployeeOnDate($employeeId, $newDate);
        }

        $this->toast('Jadwal berhasil diperbarui.', true);
    }

    public function swapSchedule(int $draggedId, int $targetId): void
    {
        if ($draggedId === $targetId) {
            return;
        }

        $draggedSchedule = Schedule::with('employee')->find($draggedId);
        $targetSchedule = Schedule::with('employee')->find($targetId);

        if (! $draggedSchedule || ! $targetSchedule) {
            $this->toast('Gagal menukar: Data jadwal tidak ditemukan.', false);

            return;
        }

        $draggedOldDate = $draggedSchedule->date->toDateString();
        $targetOldDate = $targetSchedule->date->toDateString();

        $draggedNewShiftId = $targetSchedule->shift_id;
        $draggedNewDate = $targetOldDate;

        $targetNewShiftId = $draggedSchedule->shift_id;
        $targetNewDate = $draggedOldDate;

        $draggedNewShiftSortOrder = Shift::whereKey($draggedNewShiftId)->value('sort_order');
        $targetNewShiftSortOrder = Shift::whereKey($targetNewShiftId)->value('sort_order');
        if ($draggedNewShiftSortOrder === null || $targetNewShiftSortOrder === null) {
            $this->toast('Tidak bisa menukar: shift tidak ditemukan.', false);

            return;
        }

        $hasAdjacentShift = function (int $employeeId, string $date, int $newShiftSortOrder, array $excludeScheduleIds): bool {
            $excludeScheduleIds = array_values(array_unique($excludeScheduleIds));

            return Schedule::query()
                ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                ->where('schedules.employee_id', $employeeId)
                ->whereDate('schedules.date', $date)
                ->when(
                    $this->schedule_set_id !== null,
                    fn ($q) => $q->where('schedules.schedule_set_id', $this->schedule_set_id)
                )
                ->when(
                    count($excludeScheduleIds) > 0,
                    fn ($q) => $q->whereNotIn('schedules.id', $excludeScheduleIds)
                )
                ->whereIn('shifts.sort_order', [$newShiftSortOrder - 1, $newShiftSortOrder + 1])
                ->exists();
        };

        $sameEmployeeSameDate = $draggedSchedule->employee_id === $targetSchedule->employee_id
            && $draggedNewDate === $targetNewDate;

        if ($sameEmployeeSameDate && abs($draggedNewShiftSortOrder - $targetNewShiftSortOrder) === 1) {
            $this->toast("Tidak bisa menukar: melanggar aturan jeda ≥ 1 shift untuk pegawai {$draggedSchedule->employee->name}.", false);

            return;
        }

        if ($sameEmployeeSameDate) {
            $existingSortOrders = Schedule::query()
                ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                ->where('schedules.employee_id', $draggedSchedule->employee_id)
                ->whereDate('schedules.date', $draggedNewDate)
                ->when(
                    $this->schedule_set_id !== null,
                    fn ($q) => $q->where('schedules.schedule_set_id', $this->schedule_set_id)
                )
                ->whereNotIn('schedules.id', [$draggedId, $targetId])
                ->pluck('shifts.sort_order');

            $hasConflict = $existingSortOrders->contains(
                fn ($sortOrder) => abs($sortOrder - $draggedNewShiftSortOrder) === 1
            ) || $existingSortOrders->contains(
                fn ($sortOrder) => abs($sortOrder - $targetNewShiftSortOrder) === 1
            );

            if ($hasConflict) {
                $this->toast("Tidak bisa menukar: melanggar aturan jeda ≥ 1 shift untuk pegawai {$draggedSchedule->employee->name}.", false);

                return;
            }
        } else {
            $excludeForDragged = [$draggedId];
            if ($draggedSchedule->employee_id === $targetSchedule->employee_id) {
                $excludeForDragged[] = $targetId;
            }

            if ($hasAdjacentShift($draggedSchedule->employee_id, $draggedNewDate, $draggedNewShiftSortOrder, $excludeForDragged)) {
                $this->toast("Tidak bisa menukar: melanggar aturan jeda ≥ 1 shift untuk pegawai {$draggedSchedule->employee->name}.", false);

                return;
            }

            $excludeForTarget = [$targetId];
            if ($draggedSchedule->employee_id === $targetSchedule->employee_id) {
                $excludeForTarget[] = $draggedId;
            }

            if ($hasAdjacentShift($targetSchedule->employee_id, $targetNewDate, $targetNewShiftSortOrder, $excludeForTarget)) {
                $this->toast("Tidak bisa menukar: melanggar aturan jeda ≥ 1 shift untuk pegawai {$targetSchedule->employee->name}.", false);

                return;
            }
        }

        DB::transaction(function () use ($draggedSchedule, $targetSchedule, $draggedNewShiftId, $draggedNewDate, $targetNewShiftId, $targetNewDate): void {
            $draggedSchedule->update([
                'shift_id' => $draggedNewShiftId,
                'date' => $draggedNewDate,
            ]);

            $targetSchedule->update([
                'shift_id' => $targetNewShiftId,
                'date' => $targetNewDate,
            ]);
        });

        $recalculatePairs = [
            [$draggedSchedule->employee_id, $draggedOldDate],
            [$draggedSchedule->employee_id, $draggedNewDate],
            [$targetSchedule->employee_id, $targetOldDate],
            [$targetSchedule->employee_id, $targetNewDate],
        ];

        $seen = [];
        foreach ($recalculatePairs as [$employeeId, $date]) {
            $key = "{$employeeId}|{$date}";
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $this->recalculateOvertimeForEmployeeOnDate($employeeId, $date);
        }

        $this->toast('Posisi jadwal berhasil ditukar.', true);
    }

    private function recalculateOvertimeForEmployeeOnDate(int $employeeId, string $date): void
    {
        $scheduleIds = Schedule::query()
            ->select('schedules.id')
            ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
            ->where('schedules.employee_id', $employeeId)
            ->whereDate('schedules.date', $date)
            ->when(
                $this->schedule_set_id !== null,
                fn ($q) => $q->where('schedules.schedule_set_id', $this->schedule_set_id)
            )
            ->orderBy('shifts.sort_order')
            ->orderBy('schedules.id')
            ->pluck('schedules.id');

        if ($scheduleIds->isEmpty()) {
            return;
        }

        if ($scheduleIds->count() === 1) {
            Schedule::whereIn('id', $scheduleIds->all())->update(['is_overtime' => false]);

            return;
        }

        Schedule::whereIn('id', $scheduleIds->all())->update(['is_overtime' => true]);
        Schedule::where('id', $scheduleIds->first())->update(['is_overtime' => false]);
    }

    public function publishAll(): void
    {
        Schedule::when($this->schedule_set_id !== null, fn ($q) => $q->where('schedule_set_id', $this->schedule_set_id))
            ->whereBetween('date', [$this->date_from, $this->date_to])
            ->draft()
            ->update(['status' => 'published']);

        if ($this->schedule_set_id) {
            ScheduleSet::whereKey($this->schedule_set_id)->update(['status' => 'published']);
        }

        $this->toast('Jadwal berhasil dipublish.', true);
    }

    public function with(): array
    {
        $shifts = Shift::orderBy('sort_order')->get();
        $employees = Employee::orderBy('name')->get(['id', 'name', 'color']);

        $from = Carbon::parse($this->date_from);
        $to = Carbon::parse($this->date_to);

        // Generate date columns
        $dates = [];
        $current = $from->copy();
        while ($current->lte($to)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        // Load schedules indexed by [shift_id][date]
        $schedulesRaw = Schedule::with(['employee', 'shift'])
            ->when($this->schedule_set_id !== null, fn ($q) => $q->where('schedule_set_id', $this->schedule_set_id))
            ->whereBetween('date', [$this->date_from, $this->date_to])
            ->get();

        $hoursAndCountByEmployee = $schedulesRaw
            ->groupBy('employee_id')
            ->map(fn ($items) => [
                'shift_count' => $items->count(),
                'normal_count' => $items->where('is_overtime', false)->count(),
                'overtime_count' => $items->where('is_overtime', true)->count(),
                'total_hours' => (float) $items->sum(fn ($s) => (float) ($s->shift?->duration_hours ?? 0)),
            ]);

        $employeeSummaries = $employees
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'color' => $employee->color ?? '#00ADB5',
                'shift_count' => (int) ($hoursAndCountByEmployee->get($employee->id)['shift_count'] ?? 0),
                'normal_count' => (int) ($hoursAndCountByEmployee->get($employee->id)['normal_count'] ?? 0),
                'overtime_count' => (int) ($hoursAndCountByEmployee->get($employee->id)['overtime_count'] ?? 0),
                'total_hours' => (float) ($hoursAndCountByEmployee->get($employee->id)['total_hours'] ?? 0),
            ])
            ->values()
            ->all();

        $grid = [];
        foreach ($shifts as $shift) {
            foreach ($dates as $date) {
                $key = $date->toDateString();
                $grid[$shift->id][$key] = $schedulesRaw->filter(
                    fn ($s) => $s->shift_id === $shift->id && $s->date->toDateString() === $key
                )->values();
            }
        }

        $hasDrafts = $schedulesRaw->where('status', 'draft')->isNotEmpty();

        return compact('shifts', 'dates', 'grid', 'hasDrafts', 'employeeSummaries');
    }
};
