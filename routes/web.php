<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('schedule.manage');
});

Route::livewire('/employees', 'employees.index')->name('employees.index');
Route::livewire('/shifts', 'shifts.index')->name('shifts.index');
Route::livewire('/schedule/manage', 'schedule.manage')->name('schedule.manage');
Route::livewire('/schedule/generate', 'schedule.generate')->name('schedule.generate');
Route::livewire('/schedule/preview', 'schedule.preview')->name('schedule.preview')->middleware('signed');
