<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsiderOvertimeToAttendancesAndHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('consider_overtime')->default(1)->after('does_break_taken'); // Adjust 'after' as per your table structure
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->boolean('consider_overtime')->default(1)->after('does_break_taken'); // Adjust 'after' as per your table structure
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
            $table->dropColumn('consider_overtime');
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->dropColumn('consider_overtime');
        });

    }
}
