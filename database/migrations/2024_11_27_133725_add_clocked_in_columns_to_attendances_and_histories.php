<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClockedInColumnsToAttendancesAndHistories extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_self_clocked_in')->default(false)->after('id');
            $table->boolean('is_clocked_in')->default(false)->after('is_self_clocked_in');
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->boolean('is_self_clocked_in')->default(false)->after('id');
            $table->boolean('is_clocked_in')->default(false)->after('is_self_clocked_in');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_self_clocked_in', 'is_clocked_in']);
        });

        Schema::table('attendance_histories', function (Blueprint $table) {
            $table->dropColumn(['is_self_clocked_in', 'is_clocked_in']);
        });
    }
}
