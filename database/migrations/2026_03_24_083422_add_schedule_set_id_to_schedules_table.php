<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedules') || Schema::hasColumn('schedules', 'schedule_set_id')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_set_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedules') || ! Schema::hasColumn('schedules', 'schedule_set_id')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_set_id');
        });
    }
};
