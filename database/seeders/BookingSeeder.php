<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer)->get();
        $tickets   = Ticket::with('event')->get();

        if ($customers->isEmpty() || $tickets->isEmpty()) {
            $this->command->warn('⚠️  Missing customers or tickets. Run prior seeders first.');
            return;
        }

        $bookingsCreated = 0;
        $targetBookings  = 20;

        // Distribution: 10 confirmed, 7 pending, 3 cancelled
        $statuses = array_merge(
            array_fill(0, 10, 'confirmed'),
            array_fill(0, 7, 'pending'),
            array_fill(0, 3, 'cancelled'),
        );
        shuffle($statuses);

        // Spread 20 bookings across customers, cycling through tickets
        for ($i = 0; $i < $targetBookings; $i++) {
            $customer = $customers[$i % $customers->count()];
            $ticket   = $tickets[$i % $tickets->count()];
            $status   = $statuses[$i];
            $quantity = rand(1, 3);

            // Avoid duplicate active booking (same user + ticket already exists?)
            $exists = Booking::where('user_id', $customer->id)
                ->where('ticket_id', $ticket->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($exists) {
                // Use next ticket to avoid constraint violation
                $ticket = $tickets[($i + 1) % $tickets->count()];
            }

            $booking = Booking::create([
                'user_id'            => $customer->id,
                'ticket_id'          => $ticket->id,
                'quantity'           => $quantity,
                'total_amount'       => $ticket->price * $quantity,
                'status'             => $status,
                'booking_reference'  => 'BK-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'confirmed_at'       => $status === 'confirmed' ? now() : null,
                'cancelled_at'       => $status === 'cancelled' ? now() : null,
                'cancellation_reason'=> $status === 'cancelled' ? 'Change of plans' : null,
            ]);

            // Update sold quantity on ticket
            if (in_array($status, ['confirmed', 'pending'])) {
                $ticket->increment('quantity_sold', $quantity);
            }

            // Create payment for confirmed bookings
            if ($status === 'confirmed') {
                Payment::create([
                    'booking_id'       => $booking->id,
                    'amount'           => $booking->total_amount,
                    'status'           => PaymentStatus::Success,
                    'payment_method'   => 'mock',
                    'transaction_id'   => 'TXN-' . strtoupper(Str::uuid()),
                    'gateway_response' => [
                        'code'      => '00',
                        'message'   => 'Transaction success',
                        'processor' => 'MockGateway',
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'paid_at'          => now(),
                ]);
            }

            $bookingsCreated++;
        }

        $this->command->info("✅ Bookings seeded: {$bookingsCreated} bookings (10 confirmed w/ payments, 7 pending, 3 cancelled)");
    }
}
