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
        Schema::table('contingencias', function (Blueprint $table) {
            // Agregar columnas para compatibilidad con el dashboard DTE
            $table->boolean('activa')->default(true)->after('codEstado');
            $table->date('fecha_inicio')->nullable()->after('fInicio');
            $table->date('fecha_fin')->nullable()->after('fFin');
            $table->unsignedBigInteger('company_id')->nullable()->after('idEmpresa');
            $table->string('nombre')->nullable()->after('codInterno');
            $table->integer('documentos_afectados')->default(0)->after('observacionesMsg');

            // Agregar índices para mejorar el rendimiento
            $table->index(['activa', 'fecha_fin']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contingencias', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['activa', 'fecha_fin']);
            $table->dropIndex(['company_id']);

            // Eliminar columnas agregadas
            $table->dropColumn([
                'activa',
                'fecha_inicio',
                'fecha_fin',
                'company_id',
                'nombre',
                'documentos_afectados'
            ]);
        });
    }
};
