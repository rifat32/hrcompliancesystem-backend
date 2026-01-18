<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAttendancesForBreaks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add new columns for paid and unpaid breaks
            $table->double('paid_break_hours')->default(0)->after('break_hours');
            $table->double('unpaid_break_hours')->default(0)->after('paid_break_hours');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropColumn('paid_break_hours');
            $table->dropColumn('unpaid_break_hours');
        });
    }
}
