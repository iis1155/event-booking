<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Simulate payment processing.
     * 80% success rate in mock mode.
     */
    public function processPayment(Booking $booking): array
    {
        // Simulate gateway call
        $isSuccess = $this->simulateGateway();

        return DB::transaction(function () use ($booking, $isSuccess) {
            if ($isSuccess) {
                return $this->handleSuccess($booking);
            }
            return $this->handleFailure($booking);
        });
    }

    private function handleSuccess(Booking $booking): array
    {
        $payment = Payment::create([
            'booking_id'       => $booking->id,
            'amount'           => $booking->total_amount,
            'status'           => PaymentStatus::Success,
            'payment_method'   => 'mock',
            'transaction_id'   => 'TXN-' . strtoupper(Str::uuid()),
            'gateway_response' => [
                'code'      => '00',
                'message'   => 'Transaction approved',
                'processor' => 'MockGateway',
                'timestamp' => now()->toIso8601String(),
            ],
            'paid_at' => now(),
        ]);

        // Confirm the booking
        $booking->confirm();

        // Send notification via queue
        $booking->user->notify(new BookingConfirmed($booking));

        return [
            'success' => true,
            'payment' => $payment,
        ];
    }

    private function handleFailure(Booking $booking): array
    {
        Payment::create([
            'booking_id'       => $booking->id,
            'amount'           => $booking->total_amount,
            'status'           => PaymentStatus::Failed,
            'payment_method'   => 'mock',
            'transaction_id'   => 'TXN-' . strtoupper(Str::uuid()),
            'gateway_response' => [
                'code'      => '05',
                'message'   => 'Do not honor',
                'processor' => 'MockGateway',
                'timestamp' => now()->toIso8601String(),
            ],
            'paid_at' => null,
        ]);

        return [
            'success' => false,
            'error'   => 'Payment declined by gateway. Please try again.',
        ];
    }

    /**
     * Simulate gateway: 80% success rate
     */
    private function simulateGateway(): bool
    {
        return rand(1, 10) <= 8;
    }
}
