<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->boolean('is_enabled')->default(false);

            $table->unsignedBigInteger("created_by")->nullable();



            $table->timestamps();
        });



        DB::table('modules')
        ->insert(array(
            [
                "name" => "job_board",
                "is_enabled" => 1,
            ],
            [
                "name" => "asset",
                "is_enabled" => 1,
            ],
            [
                "name" => "department",
                "is_enabled" => 1,
            ],
            [
                "name" => "project",
                "is_enabled" => 1,
            ],
            [
                "name" => "employee_map",
                "is_enabled" => 1,
            ],
            [
                "name" => "employee_calendar",
                "is_enabled" => 1,
            ],
           [
            "name" => "task_management",
            "is_enabled" => 1,
           ],

           [
            "name" => "user_activity",
            "is_enabled" => 0,
           ],
           [
            "name" => "employee_login",
            "is_enabled" => 1,
           ],

           [
            "name" => "employee_location_attendance",
            "is_enabled" => 1,
           ],
           [
            "name" => "flexible_shifts",
            "is_enabled" => 1,
           ],
           [
            "name" => "rota",
            "is_enabled" => 1,
           ],

        ));





    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
