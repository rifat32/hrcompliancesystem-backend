<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->text('description');
            $table->json('attachments')->nullable();

            $table->enum('status', ['open', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('visibility', ['public', 'private'])->default('public');

            $table->text('tags')->nullable();
            $table->text('resolution')->nullable();
            $table->json('feedback')->nullable();


            $table->text('hidden_note')->nullable();
              $table->json('history')->nullable();


              $table->unsignedBigInteger('project_id')->nullable();
              $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            $table->unsignedBigInteger('related_task_id')->nullable();
            $table->foreign('related_task_id')->references('id')->on('tasks');
            $table->unsignedBigInteger('task_id');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');



            $table->enum('type', ['comment', 'history'])->default("comment");

            $table->unsignedBigInteger("created_by")->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // $table->foreign('user_id')->references('id')->on('users');

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
        Schema::dropIfExists('comments');
    }
}
