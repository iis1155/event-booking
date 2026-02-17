<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id'       => Booking::factory()->confirmed(),
            'amount'           => fake()->numberBetween(50_000, 2_000_000),
            'status'           => PaymentStatus::Success,
            'payment_method'   => 'mock',
            'transaction_id'   => 'TXN-' . strtoupper(Str::uuid()),
            'gateway_response' => [
                'code'       => '00',
                'message'    => 'Transaction success',
                'processor'  => 'MockGateway',
                'timestamp'  => now()->toIso8601String(),
            ],
            'paid_at'          => now(),
            'refunded_at'      => null,
        ];
    }

    // ─── Status States ────────────────────────────────────────────────────────

    public function success(): static
    {
        return $this->state(fn() => [
            'status'  => PaymentStatus::Success,
            'paid_at' => now(),
            'gateway_response' => [
                'code'      => '00',
                'message'   => 'Transaction success',
                'processor' => 'MockGateway',
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => [
            'status'  => PaymentStatus::Failed,
            'paid_at' => null,
            'gateway_response' => [
                'code'      => '05',
                'message'   => 'Do not honor',
                'processor' => 'MockGateway',
            ],
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn() => [
            'status'       => PaymentStatus::Refunded,
            'refunded_at'  => now(),
            'gateway_response' => [
                'code'      => '00',
                'message'   => 'Refund processed',
                'processor' => 'MockGateway',
            ],
        ]);
    }
}
