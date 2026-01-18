<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePlanModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_plan_modules', function (Blueprint $table) {
            $table->id();



            $table->boolean('is_enabled')->default(false);

            $table->unsignedBigInteger("service_plan_id")->nullable();
            $table->foreign('service_plan_id')->references('id')->on('service_plans')->onDelete('cascade');

            $table->unsignedBigInteger("module_id")->nullable();
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');

            $table->unsignedBigInteger("created_by")->nullable();




            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_plan_modules');
    }
}
