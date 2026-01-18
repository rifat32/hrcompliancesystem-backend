<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskCategoryOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_category_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("task_category_id");
            $table->foreign('task_category_id')->references('id')->on('task_categories')->onDelete('cascade');


            $table->unsignedBigInteger("project_id")->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');



            $table->integer('order_no')->default(0);

            $table->unsignedBigInteger("business_id")->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');





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
        Schema::dropIfExists('task_category_orders');
    }
}
