<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_shifts', function (Blueprint $table) {
            $table->decimal('total_schedule_hours', 10, 2)->after('break_hours')->nullable();
        });

        Schema::table('work_shift_histories', function (Blueprint $table) {
            $table->decimal('total_schedule_hours', 10, 2)->after('break_hours')->nullable();
        });
    }

    public function down()
    {
        Schema::table('work_shifts', function (Blueprint $table) {
            $table->dropColumn('total_schedule_hour');
        });

        Schema::table('work_shift_histories', function (Blueprint $table) {
            $table->dropColumn('total_schedule_hour');
        });
    }
};
