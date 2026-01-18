<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduleHourToWorkShiftDetailsAndHistories extends Migration
{
    public function up()
    {
        // Add 'schedule_hour' to 'work_shift_details' table
        Schema::table('work_shift_details', function (Blueprint $table) {
            $table->decimal('schedule_hour', 8, 2)->nullable()->after('id'); // Adjust column type if needed
        });

        // Add 'schedule_hour' to 'work_shift_detail_histories' table
        Schema::table('work_shift_detail_histories', function (Blueprint $table) {
            $table->decimal('schedule_hour', 8, 2)->nullable()->after('id'); // Adjust column type if needed
        });
    }

    public function down()
    {
        // Drop 'schedule_hour' from 'work_shift_details' table
        Schema::table('work_shift_details', function (Blueprint $table) {
            $table->dropColumn('schedule_hour');
        });

        // Drop 'schedule_hour' from 'work_shift_detail_histories' table
        Schema::table('work_shift_detail_histories', function (Blueprint $table) {
            $table->dropColumn('schedule_hour');
        });
    }

}
