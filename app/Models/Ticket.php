<?php

namespace App\Models;

use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'type',
        'price',
        'quantity',
        'quantity_sold',
    ];

    protected function casts(): array
    {
        return [
            'type'          => TicketType::class,
            'price'         => 'decimal:2',
            'quantity'      => 'integer',
            'quantity_sold' => 'integer',
            'deleted_at'    => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->quantity_sold;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_quantity > 0;
    }

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->whereColumn('quantity_sold', '<', 'quantity');
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
