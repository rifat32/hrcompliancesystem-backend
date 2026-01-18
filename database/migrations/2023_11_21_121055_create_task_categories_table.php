<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_categories', function (Blueprint $table) {



            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('color')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);




            $table->unsignedBigInteger("project_id")->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');


            $table->integer('order_no')->default(0);



            $table->unsignedBigInteger("business_id")->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unsignedBigInteger("created_by");


            $table->softDeletes();
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
        Schema::dropIfExists('task_categories');
    }
}
