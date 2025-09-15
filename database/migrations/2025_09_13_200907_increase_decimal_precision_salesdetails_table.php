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
        Schema::table('salesdetails', function (Blueprint $table) {
            // Aumentar precisión de decimales de (5,2) a (10,8) para mayor precisión en cálculos
            $table->decimal('pricesale', 10, 8)->change();
            $table->decimal('priceunit', 10, 8)->change();
            $table->decimal('nosujeta', 10, 8)->change();
            $table->decimal('exempt', 10, 8)->change();
            $table->decimal('detained', 10, 8)->nullable()->change();
            $table->decimal('detained13', 10, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salesdetails', function (Blueprint $table) {
            // Revertir a la precisión original
            $table->decimal('pricesale', 5, 2)->change();
            $table->decimal('priceunit', 5, 2)->change();
            $table->decimal('nosujeta', 5, 2)->change();
            $table->decimal('exempt', 5, 2)->change();
            $table->decimal('detained', 5, 2)->nullable()->change();
            $table->decimal('detained13', 5, 2)->change();
        });
    }
};
