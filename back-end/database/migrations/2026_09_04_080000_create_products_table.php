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
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('base_price', 12, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->integer('physical_inventory')->default(0);
            $table->integer('reserved_inventory')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->unsignedInteger('install_minutes_per_unit')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
