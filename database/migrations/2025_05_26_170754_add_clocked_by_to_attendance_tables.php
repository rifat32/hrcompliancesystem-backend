<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClockedByToAttendanceTables extends Migration
{
     public function up(): void
    {
        Schema::table('attendance_history_records', function (Blueprint $table) {
            $table->unsignedBigInteger('clocked_in_by')->nullable()->after('out_ip_address');
            $table->unsignedBigInteger('clocked_out_by')->nullable()->after('clocked_in_by');

            $table->foreign('clocked_in_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('clocked_out_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedBigInteger('clocked_in_by')->nullable()->after('out_ip_address');
            $table->unsignedBigInteger('clocked_out_by')->nullable()->after('clocked_in_by');

            $table->foreign('clocked_in_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('clocked_out_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_history_records', function (Blueprint $table) {
            $table->dropForeign(['clocked_in_by']);
            $table->dropForeign(['clocked_out_by']);
            $table->dropColumn(['clocked_in_by', 'clocked_out_by']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['clocked_in_by']);
            $table->dropForeign(['clocked_out_by']);
            $table->dropColumn(['clocked_in_by', 'clocked_out_by']);
        });
    }



}
