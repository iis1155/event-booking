<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $price    = fake()->numberBetween(50_000, 500_000);

        return [
            'user_id'            => User::factory()->customer(),
            'ticket_id'          => Ticket::factory(),
            'quantity'           => $quantity,
            'total_amount'       => $price * $quantity,
            'status'             => BookingStatus::Pending,
            'booking_reference'  => 'BK-' . date('Y') . '-' . strtoupper(Str::random(6)),
            'confirmed_at'       => null,
            'cancelled_at'       => null,
            'cancellation_reason'=> null,
        ];
    }

    // ─── Status States ────────────────────────────────────────────────────────

    public function pending(): static
    {
        return $this->state(fn() => [
            'status'       => BookingStatus::Pending,
            'confirmed_at' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status'       => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status'              => BookingStatus::Cancelled,
            'cancelled_at'        => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn() => ['user_id' => $user->id]);
    }

    public function forTicket(Ticket $ticket): static
    {
        return $this->state(fn() => [
            'ticket_id'    => $ticket->id,
            'total_amount' => $ticket->price * 1, // qty defaults to 1
        ]);
    }
}
