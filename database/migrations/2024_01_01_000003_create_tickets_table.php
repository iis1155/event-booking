<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->enum('type', ['VIP', 'Standard', 'Economy', 'Early Bird'])->default('Standard');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');          // Total seats
            $table->unsignedInteger('quantity_sold')->default(0); // Track sold count
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('event_id');
            $table->index('type');
            $table->index(['event_id', 'type']); // Composite for querying by event + type
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
