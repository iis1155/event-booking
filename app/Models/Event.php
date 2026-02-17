<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Traits\CommonQueryScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes, CommonQueryScopes;

    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'       => 'datetime',
            'status'     => EventStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function getTotalCapacityAttribute(): int
    {
        return $this->tickets()->sum('quantity');
    }

    public function getAvailableCapacityAttribute(): int
    {
        return $this->tickets()->sum('quantity') - $this->tickets()->sum('quantity_sold');
    }

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', EventStatus::Published);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now());
    }

    public function scopeFilterByLocation($query, ?string $location)
    {
        if ($location) {
            return $query->where('location', 'like', "%{$location}%");
        }
        return $query;
    }
}
