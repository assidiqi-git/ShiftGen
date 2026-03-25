<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSet extends Model
{
    protected $fillable = ['name', 'date_from', 'date_to', 'status'];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
