<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_amount', 12, 2); // Snapshot: price * quantity at booking time
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->string('booking_reference', 20)->unique(); // Human-readable ref e.g. BK-2024-XXXXX
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('ticket_id');
            $table->index('status');
            $table->index('booking_reference');
            $table->index(['user_id', 'ticket_id']); // For double-booking middleware check

            // Prevent true duplicate: same user + same ticket (enforced at DB level too)
            $table->unique(['user_id', 'ticket_id', 'status'], 'unique_active_booking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
