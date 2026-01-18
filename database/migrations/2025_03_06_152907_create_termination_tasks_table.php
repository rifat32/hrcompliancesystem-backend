<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminationTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('termination_tasks', function (Blueprint $table) {
            $table->id();


            $table->foreignId('task_owner_id')->nullable()->constrained("users")->onDelete("set null");

            $table->foreignId('termination_process_id')->nullable()->constrained("termination_processes")->onDelete("CASCADE");


            $table->enum('task_status', ['not_started', 'in_progress', 'completed'])->default('not_started');


            $table->date('assigned_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('remarks')->nullable();


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
        Schema::dropIfExists('termination_tasks');
    }
}
