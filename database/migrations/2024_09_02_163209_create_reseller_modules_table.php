<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResellerModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reseller_modules', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_enabled')->default(false);

            $table->unsignedBigInteger("reseller_id")->nullable();
            $table->foreign('reseller_id')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('reseller_modules');
    }
}
