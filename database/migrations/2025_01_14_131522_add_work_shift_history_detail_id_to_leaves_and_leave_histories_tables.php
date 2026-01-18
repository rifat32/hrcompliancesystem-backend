<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkShiftHistoryDetailIdToLeavesAndLeaveHistoriesTables extends Migration
{



    public function up()
    {

        Schema::table('leaves', function (Blueprint $table) {
            $table->string('work_shift_history_detail_id')->nullable()->after('id');
        });

        
        Schema::table('leave_histories', function (Blueprint $table) {
            $table->string('work_shift_history_detail_id')->nullable()->after('id');
        });



    }


    public function down()
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('work_shift_history_detail_id');
        });

        Schema::table('leave_histories', function (Blueprint $table) {
            $table->dropColumn('work_shift_history_detail_id');
        });
    }




}
