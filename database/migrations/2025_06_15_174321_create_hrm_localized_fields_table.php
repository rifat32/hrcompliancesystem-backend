<?php


    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateHrmLocalizedFieldsTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return  void
         */
        public function up()
        {
            Schema::create('hrm_localized_fields', function (Blueprint $table) {
                $table->id();
                $table->string("country_code");
                $table->json("fields_json");

                $table->foreignId("reseller_id")
                ->constrained("users")
                ->onDelete("cascade");

                $table->foreignId("business_id")
                ->constrained("businesses")
                ->onDelete("cascade");

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
            Schema::dropIfExists('hrm_localized_fields');
        }
    }



