<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = ['name', 'color'];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
