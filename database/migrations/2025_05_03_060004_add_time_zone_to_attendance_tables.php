<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeZoneToAttendanceTables extends Migration
{
    public function up(): void {
        Schema::table('attendance_history_records', function (Blueprint $table) {
            $table->string('time_zone')->nullable()->after('updated_at');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('time_zone')->nullable()->after('updated_at');
        });
    }

    public function down(): void {
        Schema::table('attendance_history_records', function (Blueprint $table) {
            $table->dropColumn('time_zone');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('time_zone');
        });
    }
}
