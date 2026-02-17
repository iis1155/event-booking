<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── 2 Admins (deterministic emails for easy testing) ─────────────────
        User::factory()->admin()->create([
            'name'  => 'Super Admin',
            'email' => 'admin@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->admin()->create([
            'name'  => 'Admin Two',
            'email' => 'admin2@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        // ── 3 Organizers ─────────────────────────────────────────────────────
        User::factory()->organizer()->create([
            'name'  => 'Organizer One',
            'email' => 'organizer1@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->organizer()->create([
            'name'  => 'Organizer Two',
            'email' => 'organizer2@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->organizer()->create([
            'name'  => 'Organizer Three',
            'email' => 'organizer3@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        // ── 10 Customers ─────────────────────────────────────────────────────
        User::factory()->customer()->create([
            'name'  => 'Customer One',
            'email' => 'customer1@eventbooking.test',
            'password' => Hash::make('password'),
        ]);

        // Remaining 9 customers with random data (but still predictable pw)
        User::factory()->customer()->count(9)->create();

        $this->command->info('✅ Users seeded: 2 admins, 3 organizers, 10 customers');
    }
}
