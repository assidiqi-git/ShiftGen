<?php

use App\Exceptions\ScheduleConflictException;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Services\ScheduleGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create employees
    Employee::create(['name' => 'Alice']);
    Employee::create(['name' => 'Bob']);
    Employee::create(['name' => 'Charlie']);

    // Create 3 shifts with gap in sort_order (sort_order: 0, 1, 2)
    Shift::create(['name' => 'Pagi',   'start_time' => '06:00', 'end_time' => '14:00', 'duration_hours' => 8, 'sort_order' => 0]);
    Shift::create(['name' => 'Siang',  'start_time' => '14:00', 'end_time' => '22:00', 'duration_hours' => 8, 'sort_order' => 1]);
    Shift::create(['name' => 'Malam',  'start_time' => '22:00', 'end_time' => '06:00', 'duration_hours' => 8, 'sort_order' => 2]);
});

it('generates correct number of schedule records for a single day', function () {
    $date = Carbon::parse('2026-04-01');
    app(ScheduleGeneratorService::class)->generate($date->copy(), $date->copy());

    $count = Schedule::where('date', 'like', '2026-04-01%')->count();
    expect($count)->toBe(3); // 3 shifts → 3 records
});

it('generates schedules for a date range', function () {
    $from = Carbon::parse('2026-04-01');
    $to = Carbon::parse('2026-04-07');
    app(ScheduleGeneratorService::class)->generate($from, $to);

    expect(Schedule::count())->toBe(21); // 7 days × 3 shifts
});

it('all generated schedules have status draft', function () {
    $date = Carbon::parse('2026-04-01');
    app(ScheduleGeneratorService::class)->generate($date->copy(), $date->copy());

    $nonDraft = Schedule::where('status', '!=', 'draft')->count();
    expect($nonDraft)->toBe(0);
});

it('no two consecutive shifts are assigned to the same employee on the same day', function () {
    $date = Carbon::parse('2026-04-01');
    app(ScheduleGeneratorService::class)->generate($date->copy(), $date->copy());

    $shifts = Shift::orderBy('sort_order')->pluck('id')->toArray();

    $schedules = Schedule::where('date', 'like', '2026-04-01%')
        ->with('employee')
        ->get();

    foreach ($schedules as $s) {
        $empId = $s->employee_id;
        $thisIdx = array_search($s->shift_id, $shifts, false);

        // get all other shifts this employee has today
        $othersIdx = $schedules
            ->where('employee_id', $empId)
            ->where('id', '!=', $s->id)
            ->map(fn ($x) => array_search($x->shift_id, $shifts, false));

        foreach ($othersIdx as $otherIdx) {
            expect(abs($thisIdx - $otherIdx))->not()->toBe(1);
        }
    }
});

it('triggers overtime when employees < shifts per day but distribution is possible', function () {
    // 2 employees, 3 shifts.
    // E1 -> Shift 0, E2 -> Shift 1, E1 -> Shift 2 (overtime for E1, gap rule satisfied: Shift 0 & Shift 2 are not adjacent)
    Employee::where('name', 'Charlie')->delete();

    $date = Carbon::parse('2026-04-01');
    app(ScheduleGeneratorService::class)->generate($date->copy(), $date->copy());

    $overtimeCount = Schedule::where('is_overtime', true)->where('date', 'like', '2026-04-01%')->count();
    expect($overtimeCount)->toBeGreaterThan(0);
});

it('throws ScheduleConflictException when no distribution is possible', function () {
    // 1 employee + 3 consecutive shifts with gap rule means slot 0,1,2
    // Employee cannot fill shift 0+1 or 1+2 (consecutive). With 1 employee + 3 shifts
    // and gap rule, this is possible (0 & 2 ok, but shift 1 needs a gap from both).
    // Make this impossible: 2 employees, 4 shifts where gap rule can't be satisfied
    // Use 1 employee and 2 shifts where shifts are all adjacent → impossible
    Employee::where('name', '!=', 'Alice')->delete();
    Shift::query()->delete();

    // 2 adjacent shifts — 1 employee cannot fill both (they're consecutive)
    Shift::create(['name' => 'S1', 'start_time' => '00:00', 'end_time' => '06:00', 'duration_hours' => 6, 'sort_order' => 0]);
    Shift::create(['name' => 'S2', 'start_time' => '06:00', 'end_time' => '12:00', 'duration_hours' => 6, 'sort_order' => 1]);

    expect(fn () => app(ScheduleGeneratorService::class)->generate(Carbon::today(), Carbon::today()))
        ->toThrow(ScheduleConflictException::class);
});

it('throws ScheduleConflictException when no employees exist', function () {
    Employee::query()->delete();

    expect(fn () => app(ScheduleGeneratorService::class)->generate(Carbon::today(), Carbon::today()))
        ->toThrow(ScheduleConflictException::class);
});

it('throws ScheduleConflictException when no shifts exist', function () {
    Shift::query()->delete();

    expect(fn () => app(ScheduleGeneratorService::class)->generate(Carbon::today(), Carbon::today()))
        ->toThrow(ScheduleConflictException::class);
});

it('does not exceed 24 hours total shift validation', function () {
    Shift::query()->delete();

    // Create shifts totalling > 24 hours
    Shift::create(['name' => 'Long1', 'start_time' => '00:00', 'end_time' => '13:00', 'duration_hours' => 13, 'sort_order' => 0]);
    Shift::create(['name' => 'Long2', 'start_time' => '13:00', 'end_time' => '02:00', 'duration_hours' => 13, 'sort_order' => 1]);

    expect(fn () => app(ScheduleGeneratorService::class)->generate(Carbon::today(), Carbon::today()))
        ->toThrow(ScheduleConflictException::class);
});
