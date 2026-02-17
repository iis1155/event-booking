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
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['success', 'failed', 'refunded'])->default('failed');
            $table->string('payment_method', 50)->default('mock'); // e.g. credit_card, bank_transfer
            $table->string('transaction_id', 100)->nullable()->unique(); // External gateway ref
            $table->json('gateway_response')->nullable(); // Store full mock/real response
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('booking_id');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
