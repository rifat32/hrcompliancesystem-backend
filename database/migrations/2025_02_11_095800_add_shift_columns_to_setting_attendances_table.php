<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftColumnsToSettingAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('setting_attendances', function (Blueprint $table) {
            $table->enum('single_day_work_shift', ['same_day', 'split_time'])->default("same_day"); // same day or split time for single-day shifts
            $table->enum('multi_day_work_shift', ['same_day', 'split_time'])->default("same_day"); // same day for multi-day shifts or no rule
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setting_attendances', function (Blueprint $table) {
            $table->dropColumn(['single_day_work_shift', 'multi_day_work_shift']);
        });
    }
}
