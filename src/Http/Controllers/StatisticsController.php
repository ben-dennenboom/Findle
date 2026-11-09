<?php

namespace Dennenboom\Findle\Http\Controllers;

use Carbon\CarbonInterface;
use Dennenboom\Findle\Models\PageView;
use Dennenboom\Findle\Models\PageViewVisit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection as BaseCollection;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'last_visit_at');
        $sortDirection = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $search = $request->get('search', '');
        $headless = $request->get('headless', false);

        $query = PageView::select('base_url')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('MAX(updated_at) as last_visit_at')
            ->groupBy('base_url');

        if ($search) {
            $query->where('url', 'like', '%' . $search . '%');
        }

        $allowedSorts = ['base_url', 'unique_visitors', 'total_visits', 'last_visit_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('last_visit_at', 'desc');
        }

        $pageViews = $query->paginate(25);
        $pageViews->appends($request->query());

        $days = (int) $request->get('days', 90);
        $sinceDate = now()->subDays($days);

        $totalStats = array_merge($this->getVisitCounts($sinceDate), [
            'top_pages'   => $this->getTopPages($sinceDate, 10),
            'last_visits' => $this->getLastVisits(10),
            'days'        => $days,
        ]);

        $startOfToday = now()->startOfDay();

        $todayStats = array_merge($this->getVisitCounts($startOfToday), [
            'top_pages'   => $this->getTopPages($startOfToday, 10),
            'last_visits' => $this->getLastVisits(10, $startOfToday),
        ]);

        $dailyStats = PageViewVisit::dailyStats($sinceDate);

        $halfHourlyStats = PageViewVisit::halfHourlyStats($startOfToday);

        $references = $this->getReferences();

        $data = compact(
            'pageViews',
            'sortBy',
            'sortDirection',
            'search',
            'totalStats',
            'todayStats',
            'dailyStats',
            'halfHourlyStats',
            'references',
            'headless'
        );

        if ($request->wantsJson() || $request->has('embedded')) {
            return response()->json($data);
        }

        return view('findle::statistics.index', $data);
    }

    /** @return array{
        total_visits: int,
        total_unique_visits: int,
        avg_views_per_visit: float|int
    } */
    private function getVisitCounts(CarbonInterface $since): array
    {
        $totalVisits = PageViewVisit::since($since)->count();
        $totalUniqueVisits = PageViewVisit::since($since)->distinct()->count('page_view_id');

        return [
            'total_visits'        => $totalVisits,
            'total_unique_visits' => $totalUniqueVisits,
            'avg_views_per_visit' => $totalUniqueVisits > 0 ? round($totalVisits / $totalUniqueVisits, 2) : 0,
        ];
    }

    /** @return BaseCollection<int, object> */
    private function getTopPages(CarbonInterface $since, int $limit): BaseCollection
    {
        $pageViewsTable = PageView::tableName();

        return PageViewVisit::joinPageViews()
            ->since($since)
            ->selectRaw("{$pageViewsTable}.base_url as url")
            ->selectRaw('COUNT(*) as visit_count')
            ->groupBy("{$pageViewsTable}.base_url")
            ->orderBy('visit_count', 'desc')
            ->limit($limit)
            ->toBase()
            ->get();
    }

    private function getLastVisits(int $limit, ?CarbonInterface $since = null): BaseCollection
    {
        $pageViewsTable = PageView::tableName();
        $visitsTable = PageViewVisit::tableName();

        return PageViewVisit::joinPageViews()
            ->when($since, fn ($query) => $query->since($since))
            ->select("{$pageViewsTable}.url", "{$visitsTable}.visited_at")
            ->orderBy("{$visitsTable}.visited_at", 'desc')
            ->orderBy("{$visitsTable}.id", 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (PageViewVisit $visit) => [
                'url'       => $visit->url,
                'page'      => preg_replace('#^https?://[^/]+#', '', $visit->url),
                'timestamp' => $visit->visited_at->format('M j, H:i'),
            ]);
    }

    /** @return Collection<int, PageView> */
    private function getReferences(?string $url = null): Collection
    {
        return PageView::whereNotNull('reference')
            ->when($url, fn ($query) => $query->forBaseUrl($url))
            ->select('reference')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('MAX(updated_at) as last_visit_at')
            ->groupBy('reference')
            ->orderBy('total_visits', 'desc')
            ->get();
    }

    public function show(Request $request)
    {
        $url = $request->get('url');
        $headless = $request->get('headless', false);

        if (! is_string($url) || $url === '') {
            return redirect()->route('findle.statistics.index');
        }

        $url = PageView::normalizeBaseUrl($url);

        $pageViewStats = PageView::forBaseUrl($url)
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('MAX(updated_at) as last_visit_at')
            ->selectRaw('MIN(created_at) as first_visit_at')
            ->first();

        $topOtherPages = PageViewVisit::topOtherPages($url, 10);

        $userAgentStats = PageView::forBaseUrl($url)
            ->selectRaw('user_agent')
            ->selectRaw('SUM(visits_count) as count')
            ->whereNotNull('user_agent')
            ->groupBy('user_agent')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $ua = $item->user_agent;
                if (stripos($ua, 'Chrome') !== false) {
                    $browser = 'Chrome';
                } elseif (stripos($ua, 'Firefox') !== false) {
                    $browser = 'Firefox';
                } elseif (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
                    $browser = 'Safari';
                } elseif (stripos($ua, 'Edge') !== false) {
                    $browser = 'Edge';
                } else {
                    $browser = 'Other';
                }

                return [
                    'browser'    => $browser,
                    'count'      => $item->count,
                    'user_agent' => $ua,
                ];
            })
            ->groupBy('browser')
            ->map(function ($group) {
                return $group->sum('count');
            });

        $queryDataStats = PageView::forBaseUrl($url)
            ->where('url', 'like', '%?%')
            ->whereNotNull('query_data')
            ->where('query_data', '!=', '[]')
            ->get()
            ->flatMap(function ($view) {
                $stats = [];
                foreach ($view->query_data as $key => $values) {
                    if (is_array($values)) {
                        foreach ($values as $value) {
                            $stats[] = [
                                'param' => $key . '=' . $value,
                                'weight' => $view->visits_count
                            ];
                        }
                    } else {
                        $stats[] = [
                            'param' => $key . '=' . $values,
                            'weight' => $view->visits_count
                        ];
                    }
                }
                return $stats;
            })
            ->groupBy('param')
            ->map(function ($group) {
                return $group->sum('weight');
            })
            ->sortDesc()
            ->take(10);

        $dailyVisits = PageViewVisit::dailyStats(now()->subDays(30), $url)
            ->map(fn (array $day) => [
                'date'          => $day['date'],
                'visits'        => $day['total_visits'],
                'unique_visits' => $day['unique_visits'],
            ])
            ->reverse()
            ->values();

        $references = $this->getReferences($url);

        $data = compact(
            'url',
            'pageViewStats',
            'topOtherPages',
            'userAgentStats',
            'queryDataStats',
            'dailyVisits',
            'references',
            'headless'
        );

        if ($request->wantsJson() || $request->has('embedded')) {
            return response()->json($data);
        }

        return view('findle::statistics.show', $data);
    }
}
