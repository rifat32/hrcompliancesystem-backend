<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCarryOverLimitAndLeaveRolloverTypeToSettingLeaveTypesTable extends Migration
{

    public function up()
    {
        Schema::table('setting_leave_types', function (Blueprint $table) {
            $table->integer('carry_over_limit')->default(0);  // Max number of hours to carry over (null for no limit)
            $table->enum('leave_rollover_type', ['none', 'partial', 'full'])->default('none');
        });
    }

    public function down()
    {
        Schema::table('setting_leave_types', function (Blueprint $table) {
            $table->dropColumn('carry_over_limit');
            $table->dropColumn('leave_rollover_type');
        });
    }











}
