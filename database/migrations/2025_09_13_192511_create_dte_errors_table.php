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
        Schema::create('dte_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dte_id');
            $table->string('tipo_error');
            $table->string('codigo_error');
            $table->text('descripcion');
            $table->json('detalles')->nullable();
            $table->json('stack_trace')->nullable();
            $table->longText('json_completo')->nullable();
            $table->integer('intentos_realizados')->default(0);
            $table->integer('max_intentos')->default(3);
            $table->timestamp('proximo_reintento')->nullable();
            $table->boolean('resuelto')->default(false);
            $table->unsignedBigInteger('resuelto_por')->nullable();
            $table->timestamp('resuelto_en')->nullable();
            $table->string('solucion_aplicada')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['dte_id', 'tipo_error']);
            $table->index(['resuelto', 'tipo_error']);
            $table->index(['proximo_reintento']);
            $table->index(['created_at']);

            // Claves foráneas
            $table->foreign('dte_id')->references('id')->on('dte')->onDelete('cascade');
            $table->foreign('resuelto_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_errors');
    }
};
