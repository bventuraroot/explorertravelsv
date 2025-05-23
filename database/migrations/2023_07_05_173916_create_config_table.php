<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('version');
            $table->string('ambiente');
            $table->string('typeModel');
            $table->string('typeTransmission');
            $table->string('typeContingencia');
            $table->string('versionJson');
            $table->string('passPrivateKey');
            $table->string('passkeyPublic');
            $table->string('passMH');
            $table->string('codeCountry');
            $table->string('nameCountry');
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
        Schema::dropIfExists('config');
    }
};
