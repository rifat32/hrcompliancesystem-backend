<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCurrentToEmployeeModels extends Migration
{
    public function up()
    {
        Schema::table('employee_pension_histories', function (Blueprint $table) {
            $table->boolean('is_current')->default(0);
        });

        Schema::table('employee_right_to_work_histories', function (Blueprint $table) {
            $table->boolean('is_current')->default(0);
        });

        Schema::table('employee_sponsorship_histories', function (Blueprint $table) {
            $table->boolean('is_current')->default(0);
        });

        Schema::table('employee_passport_detail_histories', function (Blueprint $table) {
            $table->boolean('is_current')->default(0);
        });

        Schema::table('employee_visa_detail_histories', function (Blueprint $table) {
            $table->boolean('is_current')->default(0);
        });
    }

    public function down()
    {
        Schema::table('employee_pension_histories', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });

        Schema::table('employee_right_to_work_histories', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });

        Schema::table('employee_sponsorship_histories', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });

        Schema::table('employee_passport_detail_histories', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });

        Schema::table('employee_visa_detail_histories', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });
    }
}
