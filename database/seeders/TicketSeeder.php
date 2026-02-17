<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        if ($events->isEmpty()) {
            $this->command->warn('⚠️  No events found. Run EventSeeder first.');
            return;
        }

        // Ticket template per tier — 3 tiers × 5 events = 15 tickets exactly
        $tiers = [
            [
                'type'     => 'VIP',
                'price'    => 750_000,
                'quantity' => 50,
            ],
            [
                'type'     => 'Standard',
                'price'    => 250_000,
                'quantity' => 200,
            ],
            [
                'type'     => 'Economy',
                'price'    => 100_000,
                'quantity' => 300,
            ],
        ];

        foreach ($events as $event) {
            foreach ($tiers as $tier) {
                Ticket::factory()->forEvent($event)->create($tier);
            }
        }

        $this->command->info('✅ Tickets seeded: 15 tickets (3 tiers × 5 events)');
    }
}
