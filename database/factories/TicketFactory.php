<?php

namespace Database\Factories;

use App\Enums\TicketType;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    // Realistic price ranges per ticket type
    private array $priceRanges = [
        'VIP'        => [500_000,  2_000_000],
        'Standard'   => [150_000,  500_000],
        'Economy'    => [50_000,   150_000],
        'Early Bird' => [75_000,   200_000],
    ];

    public function definition(): array
    {
        $type  = fake()->randomElement(TicketType::values());
        $range = $this->priceRanges[$type];

        return [
            'event_id'      => Event::factory(),
            'type'          => $type,
            'price'         => fake()->numberBetween($range[0], $range[1]),
            'quantity'      => fake()->numberBetween(50, 500),
            'quantity_sold' => 0,
        ];
    }

    // ─── Type States ──────────────────────────────────────────────────────────

    public function vip(): static
    {
        return $this->state(fn() => [
            'type'     => TicketType::VIP,
            'price'    => fake()->numberBetween(500_000, 2_000_000),
            'quantity' => fake()->numberBetween(10, 50),
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn() => [
            'type'  => TicketType::Standard,
            'price' => fake()->numberBetween(150_000, 500_000),
        ]);
    }

    public function economy(): static
    {
        return $this->state(fn() => [
            'type'  => TicketType::Economy,
            'price' => fake()->numberBetween(50_000, 150_000),
        ]);
    }

    public function earlyBird(): static
    {
        return $this->state(fn() => [
            'type'  => TicketType::EarlyBird,
            'price' => fake()->numberBetween(75_000, 200_000),
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(function (array $attrs) {
            return ['quantity_sold' => $attrs['quantity'] ?? 100];
        });
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn() => ['event_id' => $event->id]);
    }
}
