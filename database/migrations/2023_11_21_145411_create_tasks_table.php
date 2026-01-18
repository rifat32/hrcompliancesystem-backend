<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string("unique_identifier");




            $table->string("name");

            $table->text("description")->nullable();


            $table->json("assets");

            $table->string("cover")->nullable();

            $table->date("start_date");
            $table->date("due_date")->nullable();
            $table->date("end_date")->nullable();
            $table->enum('status', ['pending','in_progress','done','in_review','approved','cancelled','rejected','draft'])->default("pending");






            $table->integer('order_no')->default(0);


            $table->unsignedBigInteger("assigned_to");
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("project_id");
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');


            $table->unsignedBigInteger("parent_task_id")->nullable();
            $table->foreign('parent_task_id')->references('id')->on('tasks')->onDelete('cascade');

            $table->unsignedBigInteger("task_category_id")->nullable();
            $table->foreign('task_category_id')->references('id')->on('task_categories')->onDelete('cascade');




            $table->unsignedBigInteger("assigned_by")->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');



            $table->boolean("is_active")->default(true);
            $table->unsignedBigInteger("business_id");
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            $table->unsignedBigInteger("created_by")->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
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
        Schema::dropIfExists('tasks');
    }
}
