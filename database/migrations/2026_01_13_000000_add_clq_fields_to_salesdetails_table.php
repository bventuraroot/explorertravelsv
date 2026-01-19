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
        Schema::table('salesdetails', function (Blueprint $table) {
            $table->string('clq_tipo_documento', 10)->nullable()->after('detainedP')->comment('Código del tipo de documento relacionado (solo para CLQ)');
            $table->string('clq_tipo_generacion', 2)->nullable()->after('clq_tipo_documento')->comment('1=Físico, 2=Electrónico (solo para CLQ)');
            $table->string('clq_numero_documento', 100)->nullable()->after('clq_tipo_generacion')->comment('Código generación o correlativo del documento (solo para CLQ)');
            $table->date('clq_fecha_generacion')->nullable()->after('clq_numero_documento')->comment('Fecha de generación del documento (solo para CLQ)');
            $table->text('clq_observaciones')->nullable()->after('clq_fecha_generacion')->comment('Observaciones adicionales (solo para CLQ)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesdetails', function (Blueprint $table) {
            $table->dropColumn([
                'clq_tipo_documento',
                'clq_tipo_generacion',
                'clq_numero_documento',
                'clq_fecha_generacion',
                'clq_observaciones'
            ]);
        });
    }
};
