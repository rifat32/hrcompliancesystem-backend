<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisabledAssetTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disabled_asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')
            ->constrained('asset_types')
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
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disabled_asset_types');
    }
}
