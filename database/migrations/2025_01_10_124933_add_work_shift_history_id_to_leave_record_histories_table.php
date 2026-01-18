<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkShiftHistoryIdToLeaveRecordHistoriesTable extends Migration
{


    public function up()
    {
        Schema::table('leave_record_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('work_shift_history_id')->nullable()->after('leave_hours');
        });
    }


    public function down()
    {
        Schema::table('leave_record_histories', function (Blueprint $table) {
            $table->dropColumn('work_shift_history_id');
        });
    }





    
}
