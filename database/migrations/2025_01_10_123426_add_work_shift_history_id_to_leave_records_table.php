<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_records', function (Blueprint $table) {
            
            $table->unsignedBigInteger('work_shift_history_id')->nullable()->after('leave_hours');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_records', function (Blueprint $table) {
            $table->dropColumn('work_shift_history_id');
        });
    }
};
