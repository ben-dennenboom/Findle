<?php

namespace Dennenboom\Findle\Models;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PageViewVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_view_id',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(static::tableName());
    }

    public static function tableName(): string
    {
        return config('findle.visits_table_name', 'page_view_visits');
    }

    public function pageView(): BelongsTo
    {
        return $this->belongsTo(PageView::class);
    }

    public function scopeJoinPageViews(Builder $query): Builder
    {
        $pageViewsTable = PageView::tableName();
        $visitsTable = static::tableName();

        return $query->join($pageViewsTable, "{$pageViewsTable}.id", '=', "{$visitsTable}.page_view_id");
    }

    public function scopeSince(Builder $query, CarbonInterface $since): Builder
    {
        return $query->where(static::tableName() . '.visited_at', '>=', $since);
    }

    public function scopeForBaseUrl(Builder $query, string $url): Builder
    {
        return $query->where(PageView::tableName() . '.base_url', PageView::normalizeBaseUrl($url));
    }

    /**
     * Includes the days without visits, so charts don't draw a line between two distant days.
     *
     * @return Collection<int, array{
     *     date: string,
     *     total_visits: int,
     *     unique_visits: int
     * }>
     */
    public static function dailyStats(CarbonInterface $since, ?string $url = null): Collection
    {
        $pageViewsTable = PageView::tableName();
        $visitsTable = static::tableName();

        $statsPerDate = static::joinPageViews()
            ->since($since)
            ->when($url, fn (Builder $query) => $query->forBaseUrl($url))
            ->selectRaw("DATE({$visitsTable}.visited_at) as date")
            ->selectRaw('COUNT(*) as total_visits')
            ->selectRaw("COUNT(DISTINCT {$pageViewsTable}.visitor_hash) as unique_visits")
            ->groupBy('date')
            ->toBase()
            ->get()
            ->keyBy('date');

        return collect(CarbonPeriod::create($since->copy()->startOfDay(), now()->startOfDay()))
            ->map(fn (CarbonInterface $date) => $date->toDateString())
            ->map(fn (string $date) => [
                'date'          => $date,
                'total_visits'  => (int) ($statsPerDate[$date]->total_visits ?? 0),
                'unique_visits' => (int) ($statsPerDate[$date]->unique_visits ?? 0),
            ]);
    }

    /** @return Collection<int, object> */
    public static function topOtherPages(string $url, int $limit, ?CarbonInterface $since = null): Collection
    {
        $pageViewsTable = PageView::tableName();

        return static::joinPageViews()
            ->when($since, fn (Builder $query) => $query->since($since))
            ->whereIn("{$pageViewsTable}.visitor_hash", PageView::forBaseUrl($url)->select('visitor_hash'))
            ->where("{$pageViewsTable}.base_url", '!=', PageView::normalizeBaseUrl($url))
            ->selectRaw("{$pageViewsTable}.base_url as url")
            ->selectRaw('COUNT(*) as visit_count')
            ->groupBy("{$pageViewsTable}.base_url")
            ->orderBy('visit_count', 'desc')
            ->limit($limit)
            ->toBase()
            ->get();
    }
}
