<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CommonQueryScopes
{
    /**
     * Filter records by date range.
     * Accepts: date_from, date_to query params.
     */
    public function scopeFilterByDate(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        if ($from) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        return $query;
    }

    /**
     * Search by title using LIKE (works on all DBs).
     * For MySQL/MariaDB production, consider FULLTEXT for large datasets.
     */
    public function scopeSearchByTitle(Builder $query, ?string $search = null): Builder
    {
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        return $query;
    }
}
