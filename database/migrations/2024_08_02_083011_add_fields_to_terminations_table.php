<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToTerminationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('terminations', function (Blueprint $table) {

            $table->date('final_paycheck_date')->nullable();
            $table->decimal('final_paycheck_amount', 10, 2)->nullable();


            $table->decimal('severance_pay_amount', 10, 2)->nullable();
            $table->date('benefits_termination_date')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('terminations', function (Blueprint $table) {
            $table->dropColumn([
                'final_paycheck_date',
                'final_paycheck_amount',

   
                'severance_pay_amount',
                'benefits_termination_date'
            ]);
        });
    }



}
