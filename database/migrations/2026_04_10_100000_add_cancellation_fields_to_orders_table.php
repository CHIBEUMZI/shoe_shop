<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('cancellation_requested_at')->nullable()->after('note');
            $table->string('cancellation_reason', 500)->nullable()->after('cancellation_requested_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->string('admin_cancellation_reason', 500)->nullable()->after('cancelled_by');

            // Thêm khóa ngoại cho cancelled_by
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'cancellation_requested_at',
                'cancellation_reason',
                'cancelled_at',
                'cancelled_by',
                'admin_cancellation_reason',
            ]);
        });
    }
};