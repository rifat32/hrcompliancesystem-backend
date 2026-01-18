<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeFieldsNullableInAttendancesAndHistories extends Migration
{
    public function up()
    {
        // Update attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('break_type')->nullable()->change();
            $table->unsignedBigInteger('work_shift_history_id')->nullable()->change();
            $table->boolean('is_weekend')->nullable()->change();
        });

        // Update attendance_histories table
        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->string('break_type')->nullable()->change();
            $table->unsignedBigInteger('work_shift_history_id')->nullable()->change();
            $table->boolean('is_weekend')->nullable()->change();
        });
    }

    public function down()
    {
        // Revert changes in attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('break_type')->nullable(false)->change();
            $table->unsignedBigInteger('work_shift_history_id')->nullable(false)->change();
            $table->boolean('is_weekend')->nullable(false)->change();
        });

        // Revert changes in attendance_histories table
        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->string('break_type')->nullable(false)->change();
            $table->unsignedBigInteger('work_shift_history_id')->nullable(false)->change();
            $table->boolean('is_weekend')->nullable(false)->change();
        });
    }
}
