<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = User::where('role', UserRole::Organizer)->get();

        if ($organizers->isEmpty()) {
            $this->command->warn('⚠️  No organizers found. Run UserSeeder first.');
            return;
        }

        // 5 events distributed round-robin across the 3 organizers
        $eventData = [
            [
                'title'       => 'Jakarta Tech Conference 2025',
                'description' => 'Annual technology conference featuring the latest innovations in AI, cloud computing, and software engineering. Join 500+ tech professionals for 2 days of learning and networking.',
                'date'        => now()->addWeeks(4),
                'location'    => 'Jakarta Convention Center, Jakarta',
            ],
            [
                'title'       => 'Bandung Music Festival',
                'description' => 'A vibrant outdoor music festival celebrating indie and local music talent. Featuring 20+ artists across 3 stages with food bazaar and art installations.',
                'date'        => now()->addWeeks(6),
                'location'    => 'Lapangan Gasibu, Bandung',
            ],
            [
                'title'       => 'Startup Summit Indonesia 2025',
                'description' => 'Connect with investors, mentors, and fellow entrepreneurs. Pitch competitions, panel discussions, and workshops designed for early-stage startups.',
                'date'        => now()->addWeeks(8),
                'location'    => 'Bali International Convention Centre, Bali',
            ],
            [
                'title'       => 'Design & UX Workshop Series',
                'description' => 'Hands-on workshop series covering modern UX research, Figma advanced techniques, and design system implementation. Limited to 30 participants per session.',
                'date'        => now()->addWeeks(3),
                'location'    => 'Co-working Space Hub, Surabaya',
            ],
            [
                'title'       => 'E-Commerce & Digital Marketing Bootcamp',
                'description' => 'Intensive 3-day bootcamp on scaling e-commerce businesses through data-driven digital marketing, SEO, and performance ads.',
                'date'        => now()->addWeeks(5),
                'location'    => 'Menara BCA, Jakarta',
            ],
        ];

        foreach ($eventData as $index => $data) {
            $organizer = $organizers[$index % $organizers->count()];

            Event::factory()->forOrganizer($organizer)->create($data);
        }

        $this->command->info('✅ Events seeded: 5 events');
    }
}
