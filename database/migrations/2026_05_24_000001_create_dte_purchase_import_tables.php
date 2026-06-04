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
        // 1. Cabecera de Importación DTE
        Schema::create('email_purchase_imports', function (Blueprint $table) {
            $table->id();
            
            // Identificación en el servidor de correos
            $table->string('email_uid')->unique()->comment('UID del mensaje en el servidor de correos');
            $table->string('email_subject')->nullable();
            $table->string('email_from')->nullable();
            $table->datetime('email_date')->nullable();
            $table->string('filename')->nullable()->comment('Nombre del adjunto .json');
            $table->string('pdf_path')->nullable()->comment('Ruta de almacenamiento local del PDF original');
            
            // Campos de Hacienda El Salvador
            $table->string('dte_codigo_generacion', 36)->nullable()->unique()->comment('UUID de Hacienda (codigoGeneracion)');
            $table->string('dte_numero_control', 50)->nullable()->comment('numeroControl del DTE');
            $table->string('dte_sello_recepcion', 100)->nullable()->comment('Sello MH de recepcion');
            $table->string('dte_tipo_dte', 2)->nullable()->comment('tipoDte: 01=Factura, 03=CCF, etc.');
            $table->string('dte_tipo_nombre')->nullable();
            
            // Estados y Relaciones
            $table->enum('status', ['pending', 'processed', 'error', 'skipped'])->default('pending');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            
            // Errores e Historial
            $table->text('error_message')->nullable();
            $table->json('validation_errors')->nullable();
            $table->longText('raw_json')->nullable()->comment('JSON original limpio sin firma');
            
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('company_id');
            $table->index(['status', 'company_id']);
        });

        // 2. Agregar columnas a la tabla purchases
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'import_id')) {
                    $table->unsignedBigInteger('import_id')->nullable();
                }
                if (!Schema::hasColumn('purchases', 'codigo_generacion')) {
                    $table->string('codigo_generacion', 36)->nullable()->unique()->after('number');
                }
                if (!Schema::hasColumn('purchases', 'sello_recepcion')) {
                    $table->string('sello_recepcion', 100)->nullable()->after('codigo_generacion');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'import_id')) {
                    $table->dropColumn('import_id');
                }
                if (Schema::hasColumn('purchases', 'codigo_generacion')) {
                    $table->dropColumn('codigo_generacion');
                }
                if (Schema::hasColumn('purchases', 'sello_recepcion')) {
                    $table->dropColumn('sello_recepcion');
                }
            });
        }

        Schema::dropIfExists('email_purchase_imports');
    }
};
