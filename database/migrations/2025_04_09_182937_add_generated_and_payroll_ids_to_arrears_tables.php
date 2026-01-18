<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneratedAndPayrollIdsToArrearsTables extends Migration
{
    public function up(): void {
        Schema::table('attendance_arrears', function (Blueprint $table) {
            $table->unsignedBigInteger('generated_payroll_id')->nullable()->after('id');
            $table->unsignedBigInteger('payroll_id')->nullable()->after('generated_payroll_id');
        });

        Schema::table('leave_record_arrears', function (Blueprint $table) {
            $table->unsignedBigInteger('generated_payroll_id')->nullable()->after('id');
            $table->unsignedBigInteger('payroll_id')->nullable()->after('generated_payroll_id');
        });
    }

    public function down(): void {
        Schema::table('attendance_arrears', function (Blueprint $table) {
            $table->dropColumn('generated_payroll_id');
            $table->dropColumn('payroll_id');
        });

        Schema::table('leave_record_arrears', function (Blueprint $table) {
            $table->dropColumn('generated_payroll_id');
            $table->dropColumn('payroll_id');
        });
    }

}
