<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeLeaveAllowancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_leave_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('setting_leave_type_id')->constrained('setting_leave_types')->onDelete('cascade');
            $table->integer('total_leave_hours'); // Leave allowance for that year
            $table->integer('used_leave_hours')->default(0);
            $table->integer('carry_over_hours')->default(0);
            $table->date('leave_start_date'); // When the leave cycle starts
            $table->date('leave_expiry_date')->nullable(); // Expiry for carry-over leave
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_leave_allowances');
    }
}
