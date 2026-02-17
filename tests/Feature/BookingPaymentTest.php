<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    // ── Ticket Tests ──────────────────────────────────────────────────────

    public function test_organizer_can_create_ticket_for_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event     = Event::factory()->forOrganizer($organizer)->create();

        $response = $this->withToken($organizer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/events/{$event->id}/tickets", [
                'type'     => 'VIP',
                'price'    => 500000,
                'quantity' => 100,
            ]);

        $response->assertStatus(201)
            ->assertJson(['data' => ['type' => 'VIP']]);

        $this->assertDatabaseHas('tickets', ['event_id' => $event->id, 'type' => 'VIP']);
    }

    public function test_organizer_cannot_create_ticket_for_other_event(): void
    {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event      = Event::factory()->forOrganizer($organizer1)->create();

        $response = $this->withToken($organizer2->createToken('test')->plainTextToken)
            ->postJson("/api/v1/events/{$event->id}/tickets", [
                'type'     => 'Standard',
                'price'    => 100000,
                'quantity' => 50,
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $event    = Event::factory()->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/events/{$event->id}/tickets", [
                'type'     => 'VIP',
                'price'    => 500000,
                'quantity' => 100,
            ]);

        $response->assertStatus(403);
    }

    // ── Booking Tests ─────────────────────────────────────────────────────

    public function test_customer_can_book_a_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket   = Ticket::factory()->create(['quantity' => 100, 'quantity_sold' => 0]);

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/tickets/{$ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson(['data' => ['quantity' => 2]]);

        $this->assertDatabaseHas('bookings', [
            'user_id'   => $customer->id,
            'ticket_id' => $ticket->id,
            'status'    => 'pending',
        ]);
    }

    public function test_customer_cannot_book_sold_out_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket   = Ticket::factory()->soldOut()->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/tickets/{$ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_customer_cannot_double_book_same_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket   = Ticket::factory()->create(['quantity' => 100, 'quantity_sold' => 0]);

        // First booking
        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/tickets/{$ticket->id}/bookings", ['quantity' => 1]);

        // Second booking — should be blocked by middleware
        $response = $this->withToken($customer->tokens()->first()->token ?? $customer->createToken('test2')->plainTextToken)
            ->postJson("/api/v1/tickets/{$ticket->id}/bookings", ['quantity' => 1]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'You already have an active booking for this ticket.']);
    }

    public function test_customer_can_view_own_bookings(): void
    {
        $customer = User::factory()->customer()->create();
        Booking::factory()->forUser($customer)->count(3)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson('/api/v1/bookings');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = Booking::factory()->pending()->forUser($customer)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->putJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_confirmed_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = Booking::factory()->confirmed()->forUser($customer)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->putJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertStatus(422);
    }

    // ── Payment Tests ─────────────────────────────────────────────────────

    public function test_customer_can_pay_for_pending_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket   = Ticket::factory()->create();
        $booking  = Booking::factory()->pending()->forUser($customer)->forTicket($ticket)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/bookings/{$booking->id}/payment");

        // Either success or failed (mock gateway)
        $this->assertContains($response->status(), [200, 422]);

        // Payment record should always be created
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id]);
    }

    public function test_cannot_pay_for_already_confirmed_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = Booking::factory()->confirmed()->forUser($customer)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/bookings/{$booking->id}/payment");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_pay_for_cancelled_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = Booking::factory()->cancelled()->forUser($customer)->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/v1/bookings/{$booking->id}/payment");

        $response->assertStatus(422);
    }

    public function test_customer_cannot_pay_other_customer_booking(): void
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $booking   = Booking::factory()->pending()->forUser($customer1)->create();

        $response = $this->withToken($customer2->createToken('test')->plainTextToken)
            ->postJson("/api/v1/bookings/{$booking->id}/payment");

        $response->assertStatus(403);
    }
}
