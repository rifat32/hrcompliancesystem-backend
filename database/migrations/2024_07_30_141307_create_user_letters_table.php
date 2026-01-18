<?php






use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('user_letters', function (Blueprint $table) {
            $table->id();
            $table->date('issue_date');
            $table->text('letter_content');
            $table->string('status');
            $table->boolean('sign_required');
            $table->foreignId('user_id')
            ->constrained('users')
            ->onDelete('cascade');
            $table->json('attachments');
            $table->foreignId('business_id')
            ->constrained('businesses')
            ->onDelete('cascade');

            $table->unsignedBigInteger("created_by");
            $table->softDeletes();
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
        Schema::dropIfExists('user_letters');
    }
}



