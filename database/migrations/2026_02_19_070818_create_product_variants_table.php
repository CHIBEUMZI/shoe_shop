<?php

// create_product_variants_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('product_variants', function (Blueprint $table) {
      $table->id();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();

      $table->string('color');
      $table->string('size');

      $table->string('sku')->nullable()->unique();

      $table->unsignedBigInteger('price');
      $table->unsignedBigInteger('sale_price')->nullable();

      $table->integer('stock')->default(0);

      $table->boolean('is_active')->default(true);

      $table->timestamps();

      $table->unique(['product_id', 'color', 'size']);
      $table->index(['product_id', 'is_active']);
      $table->index(['product_id', 'color']);
      $table->index(['product_id', 'size']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('product_variants');
  }
};

