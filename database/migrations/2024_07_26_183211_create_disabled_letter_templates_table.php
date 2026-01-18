<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisabledLetterTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('disabled_letter_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_template_id')
            ->constrained('letter_templates')
            ->onDelete('cascade');

            $table->foreignId('business_id')
            ->constrained('businesses')
            ->onDelete('cascade');

            $table->foreignId('created_by')
            ->nullable()
            ->constrained('users')
            ->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('disabled_letter_templates');
    }
}




