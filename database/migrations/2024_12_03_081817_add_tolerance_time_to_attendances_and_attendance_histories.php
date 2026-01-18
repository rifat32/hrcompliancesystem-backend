<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToleranceTimeToAttendancesAndAttendanceHistories extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('tolerance_time', 10, 2)->nullable(); // Add the tolerance_time column as DECIMAL(10, 2)
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->decimal('tolerance_time', 10, 2)->nullable(); // Add the tolerance_time column as DECIMAL(10, 2)
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('tolerance_time'); // Drop the column if rolling back
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->dropColumn('tolerance_time'); // Drop the column if rolling back
        });
    }
}
