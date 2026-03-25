<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? to_route('schedule.manage')
        : to_route('login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/employees', 'employees.index')->name('employees.index');
    Route::livewire('/shifts', 'shifts.index')->name('shifts.index');
    Route::livewire('/schedule/manage', 'schedule.manage')->name('schedule.manage');
    Route::livewire('/schedule/generate', 'schedule.generate')->name('schedule.generate');
    Route::livewire('/schedule/preview/{scheduleSet}', 'schedule.preview')->name('schedule.preview');
});
