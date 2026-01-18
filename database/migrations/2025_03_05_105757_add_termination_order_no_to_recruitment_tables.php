<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTerminationOrderNoToRecruitmentTables extends Migration
{
    public function up()
    {
        // Adding the termination_order_no column to the recruitment_processes table
        Schema::table('recruitment_processes', function (Blueprint $table) {
            $table->unsignedBigInteger('termination_order_no')->default(0);
        });

        // Adding the termination_order_no column to the recruitment_process_orders table
        Schema::table('recruitment_process_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('termination_order_no')->default(0);
        });
    }

    public function down()
    {
        // Dropping the termination_order_no column from the recruitment_processes table
        Schema::table('recruitment_processes', function (Blueprint $table) {
            $table->dropColumn('termination_order_no');
        });

        // Dropping the termination_order_no column from the recruitment_process_orders table
        Schema::table('recruitment_process_orders', function (Blueprint $table) {
            $table->dropColumn('termination_order_no');
        });
    }


}
