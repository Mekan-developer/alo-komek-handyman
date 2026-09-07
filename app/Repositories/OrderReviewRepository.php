<?php

namespace App\Repositories;

use App\Models\OrderReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderReviewRepository
{
    /** Relations every admin-facing review listing needs. */
    private const LISTING_RELATIONS = [
        'master:id,name,photo',
        'client:id,name,phone',
        'order:id,client_name,client_phone,category_id,final_price,completed_at',
        'order.category:id,name_ru,name_tk',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(self::LISTING_RELATIONS)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Latest reviews of a single master — for the quick-look panel. */
    public function forMaster(int $masterId, int $limit = 50): Collection
    {
        return OrderReview::query()
            ->where('master_id', $masterId)
            ->with(self::LISTING_RELATIONS)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Aggregate picture of the filtered reviews.
     *
     * The star distribution deliberately ignores the `rating` filter, so the
     * histogram keeps showing the whole spread while a single star is selected.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, average: float|null, with_comment: int, negative: int, distribution: array<int, int>}
     */
    public function stats(array $filters = []): array
    {
        $aggregate = $this->filtered($filters)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(rating) as average')
            ->selectRaw("SUM(CASE WHEN comment IS NOT NULL AND comment <> '' THEN 1 ELSE 0 END) as with_comment")
            ->selectRaw('SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative')
            ->first();

        $counts = $this->filtered($filters, withRating: false)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'average' => $aggregate->average !== null ? round((float) $aggregate->average, 1) : null,
            'with_comment' => (int) ($aggregate->with_comment ?? 0),
            'negative' => (int) ($aggregate->negative ?? 0),
            'distribution' => collect(range(5, 1))
                ->mapWithKeys(fn (int $star) => [$star => (int) ($counts[$star] ?? 0)])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<OrderReview>
     */
    private function filtered(array $filters, bool $withRating = true): Builder
    {
        return OrderReview::query()
            ->when($filters['master_id'] ?? null, fn ($q, $masterId) => $q->where('master_id', $masterId))
            ->when($withRating ? ($filters['rating'] ?? null) : null, fn ($q, $rating) => $q->where('rating', $rating))
            ->when(
                filter_var($filters['only_with_comment'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($q) => $q->whereNotNull('comment')->where('comment', '<>', '')
            )
            ->when($filters['search'] ?? null, function ($q, $search) {
                $escaped = addcslashes($search, '%_\\');

                $q->where(fn ($sub) => $sub
                    ->where('comment', 'like', "%{$escaped}%")
                    ->orWhereHas('client', fn ($c) => $c
                        ->where('name', 'like', "%{$escaped}%")
                        ->orWhere('phone', 'like', "%{$escaped}%")
                    )
                    ->orWhereHas('master', fn ($m) => $m->where('name', 'like', "%{$escaped}%"))
                );
            })
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }
}
