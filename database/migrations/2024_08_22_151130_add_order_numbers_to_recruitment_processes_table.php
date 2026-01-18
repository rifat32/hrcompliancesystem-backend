<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderNumbersToRecruitmentProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruitment_processes', function (Blueprint $table) {


            $table->unsignedBigInteger('employee_order_no')->default(0);
            $table->unsignedBigInteger('candidate_order_no')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruitment_processes', function (Blueprint $table) {
            $table->dropColumn(['employee_order_no', 'candidate_order_no']);
        });
    }
}
