<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('categories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
      $table->string('name');
      $table->string('slug')->unique();
      $table->text('description')->nullable();
      $table->unsignedInteger('sort_order')->default(0);
      $table->tinyInteger('status')->default(1);
      $table->softDeletes();
      $table->timestamps();
      $table->index(['parent_id', 'status', 'sort_order']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('categories');
  }
};
