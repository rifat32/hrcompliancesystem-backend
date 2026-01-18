<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalLeaveHoursAndEmployeeLeaveAllowanceIdToLeaveHistoriesAndLeavesTables extends Migration
{
    public function up()
    {
        // Add to leave_histories table
        Schema::table('leave_histories', function (Blueprint $table) {
            $table->integer('total_leave_hours')->nullable(); // Adjust as per your requirement
            $table->unsignedBigInteger('employee_leave_allowance_id')->nullable(); // Assuming it's a foreign key
        });

        // Add to leaves table
        Schema::table('leaves', function (Blueprint $table) {
            $table->integer('total_leave_hours')->nullable();
            $table->unsignedBigInteger('employee_leave_allowance_id')->nullable();
        });
    }

    public function down()
    {
        // Rollback the changes
        Schema::table('leave_histories', function (Blueprint $table) {
            $table->dropColumn(['total_leave_hours', 'employee_leave_allowance_id']);
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['total_leave_hours', 'employee_leave_allowance_id']);
        });
    }

}
