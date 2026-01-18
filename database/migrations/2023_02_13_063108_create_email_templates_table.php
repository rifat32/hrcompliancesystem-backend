<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->string("type");
            $table->text("template");
            $table->string("template_variables")->nullable();

            $table->boolean("is_active");


            $table->unsignedBigInteger("wrapper_id");
            $table->foreign('wrapper_id')->references('id')->on('email_template_wrappers')->onDelete('restrict');


            $table->boolean("is_default");
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
        Schema::dropIfExists('email_templates');
    }
}
