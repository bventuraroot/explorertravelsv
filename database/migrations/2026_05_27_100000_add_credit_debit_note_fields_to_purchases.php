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
        Schema::table('purchases', function (Blueprint $table) {
            // Código MH del tipo de DTE: 01=Factura, 03=CCF, 05=NC Proveedor, 06=ND Proveedor, 14=FSE
            if (!Schema::hasColumn('purchases', 'document_tipo_dte')) {
                $table->string('document_tipo_dte', 2)->nullable()
                    ->after('document_id')
                    ->comment('Código Hacienda del tipo DTE: 01=Factura, 03=CCF, 05=NC, 06=ND, 14=FSE');
            }

            // FK a la compra original que afecta esta NC/ND
            if (!Schema::hasColumn('purchases', 'related_purchase_id')) {
                $table->unsignedBigInteger('related_purchase_id')->nullable()
                    ->after('document_tipo_dte')
                    ->comment('ID de la compra original afectada por esta NC/ND de proveedor');
            }

            // Código de generación (UUID) de la compra original afectada
            if (!Schema::hasColumn('purchases', 'related_codigo_generacion')) {
                $table->string('related_codigo_generacion', 36)->nullable()
                    ->after('related_purchase_id')
                    ->comment('Código de generación (UUID) de la compra original afectada por esta NC/ND de proveedor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'related_codigo_generacion')) {
                $table->dropColumn('related_codigo_generacion');
            }
            if (Schema::hasColumn('purchases', 'related_purchase_id')) {
                $table->dropColumn('related_purchase_id');
            }
            if (Schema::hasColumn('purchases', 'document_tipo_dte')) {
                $table->dropColumn('document_tipo_dte');
            }
        });
    }
};
