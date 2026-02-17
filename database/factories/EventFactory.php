<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $eventTypes = [
            'Tech Conference', 'Music Festival', 'Art Exhibition',
            'Sports Tournament', 'Food & Beverage Fair', 'Business Summit',
            'Charity Gala', 'Workshop', 'Hackathon', 'Networking Event',
        ];

        return [
            'title'       => fake()->randomElement($eventTypes) . ' ' . fake()->year(),
            'description' => fake()->paragraphs(3, true),
            'date'        => fake()->dateTimeBetween('+1 week', '+6 months'),
            'location'    => fake()->city() . ', ' . fake()->country(),
            'status'      => EventStatus::Published,
            'created_by'  => User::factory()->organizer(),
        ];
    }

    // ─── States ───────────────────────────────────────────────────────────────

    public function published(): static
    {
        return $this->state(fn() => ['status' => EventStatus::Published]);
    }

    public function draft(): static
    {
        return $this->state(fn() => ['status' => EventStatus::Draft]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => ['status' => EventStatus::Cancelled]);
    }

    public function upcoming(): static
    {
        return $this->state(fn() => [
            'date' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn() => [
            'date' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function forOrganizer(User $organizer): static
    {
        return $this->state(fn() => ['created_by' => $organizer->id]);
    }
}
