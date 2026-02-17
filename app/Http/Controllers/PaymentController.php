<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * POST /api/v1/bookings/{id}/payment
     * Process mock payment for a booking
     */
    public function store(int $id): JsonResponse
    {
        $booking = Booking::with('ticket')->findOrFail($id);

        // Only booking owner or admin can pay
        if (auth()->user()->isCustomer() && $booking->user_id !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. This is not your booking.',
                code: 403
            );
        }

        // Already paid
        if ($booking->isConfirmed()) {
            return $this->errorResponse(
                message: 'This booking has already been paid.',
                code: 422
            );
        }

        // Already cancelled
        if ($booking->isCancelled()) {
            return $this->errorResponse(
                message: 'Cannot pay for a cancelled booking.',
                code: 422
            );
        }

        // Process payment via service
        $result = $this->paymentService->processPayment($booking);

        if ($result['success']) {
            return $this->successResponse(
                data: [
                    'booking' => $booking->fresh('ticket.event'),
                    'payment' => $result['payment'],
                ],
                message: 'Payment successful. Booking confirmed!'
            );
        }

        return $this->errorResponse(
            message: 'Payment failed. Please try again.',
            errors: $result['error'],
            code: 422
        );
    }

    /**
     * GET /api/v1/payments/{id}
     * View payment details
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with('booking.user:id,name,email')->findOrFail($id);

        // Customer can only view their own payments
        if (auth()->user()->isCustomer() && $payment->booking->user_id !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. This is not your payment.',
                code: 403
            );
        }

        return $this->successResponse(
            data: $payment,
            message: 'Payment retrieved successfully.'
        );
    }
}
