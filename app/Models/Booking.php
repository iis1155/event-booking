<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'ticket_id',
        'quantity',
        'total_amount',
        'status',
        'booking_reference',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status'           => BookingStatus::class,
            'total_amount'     => 'decimal:2',
            'quantity'         => 'integer',
            'confirmed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
            'deleted_at'       => 'datetime',
        ];
    }

    // ─── Boot ─────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate booking reference on creation
        static::creating(function (Booking $booking) {
            $booking->booking_reference = self::generateReference();
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === BookingStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::Cancelled;
    }

    public function confirm(): bool
    {
        return $this->update([
            'status'       => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(string $reason = ''): bool
    {
        return $this->update([
            'status'              => BookingStatus::Cancelled,
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        do {
            $ref = 'BK-' . date('Y') . '-' . strtoupper(Str::random(6));
        } while (self::where('booking_reference', $ref)->exists());

        return $ref;
    }
}
