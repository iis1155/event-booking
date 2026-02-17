<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    // ── List Events ───────────────────────────────────────────────────────

    public function test_anyone_can_list_events(): void
    {
        Event::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_events_can_be_searched_by_title(): void
    {
        Event::factory()->create(['title' => 'Laravel Conference']);
        Event::factory()->create(['title' => 'React Summit']);

        $response = $this->getJson('/api/v1/events?search=Laravel');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_events_can_be_filtered_by_location(): void
    {
        Event::factory()->create(['location' => 'Jakarta']);
        Event::factory()->create(['location' => 'Bali']);

        $response = $this->getJson('/api/v1/events?location=Jakarta');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_events_can_be_filtered_by_date(): void
    {
        Event::factory()->create(['date' => now()->addDays(5)]);
        Event::factory()->create(['date' => now()->addDays(30)]);

        $response = $this->getJson('/api/v1/events?date_from=' . now()->addDays(1)->toDateString() . '&date_to=' . now()->addDays(10)->toDateString());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    // ── Show Event ────────────────────────────────────────────────────────

    public function test_anyone_can_view_single_event(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/v1/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $event->id]]);
    }

    public function test_show_returns_404_for_nonexistent_event(): void
    {
        $response = $this->getJson('/api/v1/events/9999');
        $response->assertStatus(404);
    }

    // ── Create Event ──────────────────────────────────────────────────────

    public function test_organizer_can_create_event(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->withToken($organizer->createToken('test')->plainTextToken)
            ->postJson('/api/v1/events', [
                'title'       => 'New Event',
                'description' => 'Test description',
                'date'        => now()->addMonth()->toDateTimeString(),
                'location'    => 'Jakarta',
            ]);

        $response->assertStatus(201)
            ->assertJson(['data' => ['title' => 'New Event']]);

        $this->assertDatabaseHas('events', ['title' => 'New Event']);
    }

    public function test_customer_cannot_create_event(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson('/api/v1/events', [
                'title'    => 'New Event',
                'date'     => now()->addMonth()->toDateTimeString(),
                'location' => 'Jakarta',
            ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_event(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'title'    => 'New Event',
            'date'     => now()->addMonth()->toDateTimeString(),
            'location' => 'Jakarta',
        ]);

        $response->assertStatus(401);
    }

    // ── Update Event ──────────────────────────────────────────────────────

    public function test_organizer_can_update_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event     = Event::factory()->forOrganizer($organizer)->create();

        $response = $this->withToken($organizer->createToken('test')->plainTextToken)
            ->putJson("/api/v1/events/{$event->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['title' => 'Updated Title']]);
    }

    public function test_organizer_cannot_update_other_organizer_event(): void
    {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event      = Event::factory()->forOrganizer($organizer1)->create();

        $response = $this->withToken($organizer2->createToken('test')->plainTextToken)
            ->putJson("/api/v1/events/{$event->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    // ── Delete Event ──────────────────────────────────────────────────────

    public function test_organizer_can_delete_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event     = Event::factory()->forOrganizer($organizer)->create();

        $response = $this->withToken($organizer->createToken('test')->plainTextToken)
            ->deleteJson("/api/v1/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_admin_can_delete_any_event(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create();

        $response = $this->withToken($admin->createToken('test')->plainTextToken)
            ->deleteJson("/api/v1/events/{$event->id}");

        $response->assertStatus(200);
    }
}
