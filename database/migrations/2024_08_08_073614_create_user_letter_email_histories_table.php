<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLetterEmailHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_letter_email_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_letter_id')
                ->constrained('user_letters')
                ->onDelete('cascade');
            $table->timestamp('sent_at')->nullable();
            $table->string('recipient_email');
            $table->text('email_content')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
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
        Schema::dropIfExists('user_letter_email_histories');
    }
}
