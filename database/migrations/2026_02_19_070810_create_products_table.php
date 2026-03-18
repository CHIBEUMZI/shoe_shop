<?php

// create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('products', function (Blueprint $table) {
      $table->id();

      $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

      $table->string('name');
      $table->string('slug')->unique();
      $table->string('sku')->nullable()->unique();

      $table->string('short_description')->nullable();
      $table->longText('description')->nullable();

      $table->unsignedBigInteger('base_price')->nullable();
      $table->unsignedBigInteger('base_sale_price')->nullable();

      $table->string('thumbnail')->nullable();

      $table->boolean('is_featured')->default(false);
      $table->tinyInteger('status')->default(1);
      $table->unsignedInteger('views')->default(0);

      $table->softDeletes();
      $table->timestamps();

      $table->index(['status', 'is_featured']);
      $table->index('name');
      $table->index('brand_id');
    });
  }

  public function down(): void {
    Schema::dropIfExists('products');
  }
};

