<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    public function test_payment_service_returns_array_with_success_key(): void
    {
        $booking = $this->createPendingBooking();

        $result = $this->paymentService->processPayment($booking);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_successful_payment_creates_payment_record(): void
    {
        $booking = $this->createPendingBooking();

        // Force success by mocking
        $service = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['processPayment'])
            ->getMock();

        // Directly test handleSuccess via processPayment with forced success
        // Run multiple times to get at least one success (80% rate)
        $attempts = 0;
        $success  = false;

        while ($attempts < 10 && !$success) {
            $freshBooking = $this->createPendingBooking();
            $result       = $this->paymentService->processPayment($freshBooking);

            if ($result['success']) {
                $success = true;

                // Assert payment was created with success status
                $this->assertDatabaseHas('payments', [
                    'booking_id' => $freshBooking->id,
                    'status'     => PaymentStatus::Success->value,
                ]);

                // Assert booking was confirmed
                $this->assertDatabaseHas('bookings', [
                    'id'     => $freshBooking->id,
                    'status' => BookingStatus::Confirmed->value,
                ]);

                // Assert payment record returned
                $this->assertInstanceOf(Payment::class, $result['payment']);
                $this->assertEquals(PaymentStatus::Success, $result['payment']->status);
            }
            $attempts++;
        }

        $this->assertTrue($success, 'Expected at least one successful payment in 10 attempts');
    }

    public function test_failed_payment_creates_failed_payment_record(): void
    {
        $booking  = $this->createPendingBooking();
        $attempts = 0;
        $failed   = false;

        while ($attempts < 20 && !$failed) {
            $freshBooking = $this->createPendingBooking();
            $result       = $this->paymentService->processPayment($freshBooking);

            if (!$result['success']) {
                $failed = true;

                // Assert failed payment was recorded
                $this->assertDatabaseHas('payments', [
                    'booking_id' => $freshBooking->id,
                    'status'     => PaymentStatus::Failed->value,
                ]);

                // Booking should stay pending
                $this->assertDatabaseHas('bookings', [
                    'id'     => $freshBooking->id,
                    'status' => BookingStatus::Pending->value,
                ]);

                $this->assertArrayHasKey('error', $result);
            }
            $attempts++;
        }

        $this->assertTrue($failed, 'Expected at least one failed payment in 20 attempts');
    }

    public function test_payment_amount_matches_booking_total(): void
    {
        $booking  = $this->createPendingBooking();
        $attempts = 0;

        while ($attempts < 10) {
            $freshBooking = $this->createPendingBooking();
            $result       = $this->paymentService->processPayment($freshBooking);

            if ($result['success']) {
                $this->assertEquals(
                    $freshBooking->total_amount,
                    $result['payment']->amount
                );
                break;
            }
            $attempts++;
        }
    }

    public function test_payment_has_transaction_id(): void
    {
        $booking  = $this->createPendingBooking();
        $attempts = 0;

        while ($attempts < 10) {
            $freshBooking = $this->createPendingBooking();
            $result       = $this->paymentService->processPayment($freshBooking);

            if ($result['success']) {
                $this->assertNotNull($result['payment']->transaction_id);
                $this->assertStringStartsWith('TXN-', $result['payment']->transaction_id);
                break;
            }
            $attempts++;
        }
    }

    public function test_payment_has_gateway_response(): void
    {
        $booking  = $this->createPendingBooking();
        $attempts = 0;

        while ($attempts < 10) {
            $freshBooking = $this->createPendingBooking();
            $result       = $this->paymentService->processPayment($freshBooking);

            if ($result['success']) {
                $this->assertNotNull($result['payment']->gateway_response);
                $this->assertArrayHasKey('code', $result['payment']->gateway_response);
                $this->assertArrayHasKey('message', $result['payment']->gateway_response);
                break;
            }
            $attempts++;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function createPendingBooking(): Booking
    {
        $customer = User::factory()->customer()->create();
        $ticket   = Ticket::factory()->create([
            'price'    => 100000,
            'quantity' => 100,
        ]);

        return Booking::factory()->pending()->create([
            'user_id'      => $customer->id,
            'ticket_id'    => $ticket->id,
            'quantity'     => 1,
            'total_amount' => 100000,
        ]);
    }
}
