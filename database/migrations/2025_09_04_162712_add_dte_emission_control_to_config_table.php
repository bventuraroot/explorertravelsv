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
        Schema::table('config', function (Blueprint $table) {
            // Agregar campos para control de emisión DTE
            $table->boolean('dte_emission_enabled')->default(true)->after('nameCountry');
            $table->text('dte_emission_notes')->nullable()->after('dte_emission_enabled');

            // Agregar índice para mejorar consultas
            $table->index(['company_id', 'dte_emission_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('config', function (Blueprint $table) {
            // Eliminar índice primero
            $table->dropIndex(['company_id', 'dte_emission_enabled']);

            // Eliminar columnas agregadas
            $table->dropColumn([
                'dte_emission_enabled',
                'dte_emission_notes'
            ]);
        });
    }
};
