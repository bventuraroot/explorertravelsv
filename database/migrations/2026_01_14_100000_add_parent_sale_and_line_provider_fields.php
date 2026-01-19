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
        // Agregar campos para ventas padre/hijo en sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('parent_sale_id')->nullable()->after('acuenta')->constrained('sales')->onDelete('set null');
            $table->boolean('is_parent')->default(0)->after('parent_sale_id');
        });

        // Agregar proveedor por línea en salesdetails
        Schema::table('salesdetails', function (Blueprint $table) {
            $table->foreignId('line_provider_id')->nullable()->after('product_id')->constrained('providers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['parent_sale_id']);
            $table->dropColumn(['parent_sale_id', 'is_parent']);
        });

        Schema::table('salesdetails', function (Blueprint $table) {
            $table->dropForeign(['line_provider_id']);
            $table->dropColumn('line_provider_id');
        });
    }
};
