<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCodeFieldInServicePlanDiscountCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_plan_discount_codes', function (Blueprint $table) {
            // Drop the unique index from the code column
            $table->dropUnique('service_plan_discount_codes_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_plan_discount_codes', function (Blueprint $table) {
            // Re-add the unique index to the code column in case of rollback
            $table->unique('code');
        });
    }
}
