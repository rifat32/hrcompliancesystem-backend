<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveHoursToAttendancesAndAttendanceHistories extends Migration
{
   /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('leave_hours', 8, 2)->after('overtime_hours')->default(0);
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->decimal('leave_hours', 8, 2)->after('overtime_hours')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('leave_hours');
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->dropColumn('leave_hours');
        });
    }
}
