<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dte_errors')) {
            Schema::create('dte_errors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dte_id');
                $table->string('tipo_error', 50);
                $table->string('codigo_error', 50)->nullable();
                $table->text('descripcion')->nullable();
                $table->json('detalles')->nullable();
                $table->json('trace')->nullable();
                $table->boolean('resuelto')->default(false);
                $table->text('solucion')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->foreign('dte_id')->references('id')->on('dte')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_errors');
    }
};


