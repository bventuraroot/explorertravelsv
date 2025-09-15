<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dte', function (Blueprint $table) {
            // Agregar solo los campos que no existen
            if (!Schema::hasColumn('dte', 'jsonDte')) {
                $table->longText('jsonDte')->nullable()->after('json');
            }
            if (!Schema::hasColumn('dte', 'fecha_envio')) {
                $table->timestamp('fecha_envio')->nullable()->after('fhRecibido');
            }
            if (!Schema::hasColumn('dte', 'fecha_respuesta')) {
                $table->timestamp('fecha_respuesta')->nullable()->after('fecha_envio');
            }
            if (!Schema::hasColumn('dte', 'intentos_envio')) {
                $table->integer('intentos_envio')->default(0)->after('nSends');
            }
            if (!Schema::hasColumn('dte', 'proximo_reintento')) {
                $table->timestamp('proximo_reintento')->nullable()->after('intentos_envio');
            }
            if (!Schema::hasColumn('dte', 'necesita_contingencia')) {
                $table->boolean('necesita_contingencia')->default(false)->after('proximo_reintento');
            }

            // Índices para mejorar el rendimiento (solo si no existen)
            try {
                $table->index(['codEstado', 'estadoHacienda']);
            } catch (Exception $e) {
                // El índice ya existe
            }

            try {
                $table->index(['fecha_envio']);
            } catch (Exception $e) {
                // El índice ya existe
            }

            try {
                $table->index(['proximo_reintento']);
            } catch (Exception $e) {
                // El índice ya existe
            }

            try {
                $table->index(['necesita_contingencia']);
            } catch (Exception $e) {
                // El índice ya existe
            }

            // Clave foránea para sale_id (solo si no existe)
            try {
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            } catch (Exception $e) {
                // La clave foránea ya existe
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dte', function (Blueprint $table) {
            // Eliminar clave foránea
            $table->dropForeign(['sale_id']);

            // Eliminar índices
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['codEstado', 'estadoHacienda']);
            $table->dropIndex(['fecha_envio']);
            $table->dropIndex(['proximo_reintento']);
            $table->dropIndex(['necesita_contingencia']);

            // Eliminar columnas
            $table->dropColumn([
                'sale_id',
                'jsonDte',
                'estadoHacienda',
                'fecha_envio',
                'fecha_respuesta',
                'intentos_envio',
                'proximo_reintento',
                'necesita_contingencia'
            ]);
        });
    }
};
