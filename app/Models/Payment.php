<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'gateway_response',
        'paid_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status'           => PaymentStatus::class,
            'amount'           => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at'          => 'datetime',
            'refunded_at'      => 'datetime',
        ];
    }

    // ─── Boot ─────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = 'TXN-' . strtoupper(Str::uuid());
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Success;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::Refunded;
    }
}
