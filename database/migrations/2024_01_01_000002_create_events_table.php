<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->string('location');
            $table->enum('status', ['draft', 'published', 'cancelled'])->default('published');
            $table->foreignId('created_by')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for search, filter, and sort
            $table->index('date');
            $table->index('location');
            $table->index('status');
            $table->index('created_by');
            $table->index(['date', 'location']); // Composite for combined filters
           //$table->fullText(['title', 'description']); // Full-text search support
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
