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
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tipo_documento', 10);
            $table->string('codigo_establecimiento', 10);
            $table->string('codigo_punto_venta', 10);
            $table->integer('numero_actual')->default(1);
            $table->integer('numero_final')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('descripcion')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['company_id', 'tipo_documento', 'codigo_establecimiento', 'codigo_punto_venta'], 'unique_correlativo');
            $table->index(['company_id', 'activo']);
            $table->index(['tipo_documento']);

            // Clave foránea
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
