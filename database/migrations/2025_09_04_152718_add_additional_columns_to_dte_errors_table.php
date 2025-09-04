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
        Schema::table('dte_errors', function (Blueprint $table) {
            // Agregar solo las columnas que faltan
            $table->integer('intentos_realizados')->default(0)->after('resolved_at');
            $table->integer('max_intentos')->default(3)->after('intentos_realizados');

            // Agregar índices para mejorar el rendimiento
            $table->index(['tipo_error', 'resuelto']);
            $table->index(['dte_id', 'resuelto']);
            $table->index('resolved_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dte_errors', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['tipo_error', 'resuelto']);
            $table->dropIndex(['dte_id', 'resuelto']);
            $table->dropIndex(['resolved_by']);

            // Eliminar columnas agregadas
            $table->dropColumn([
                'intentos_realizados',
                'max_intentos'
            ]);
        });
    }
};
