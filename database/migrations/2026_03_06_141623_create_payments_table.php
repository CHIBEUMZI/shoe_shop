<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('method'); // cod, vnpay, momo
            $table->string('provider')->nullable();
            $table->string('transaction_code')->nullable()->index();

            $table->string('status')->default('pending'); // pending, paid, failed, refunded
            $table->decimal('amount', 12, 2);

            $table->longText('response_payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};