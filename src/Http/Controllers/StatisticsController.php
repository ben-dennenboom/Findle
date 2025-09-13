<?php

namespace Dennenboom\Findle\Http\Controllers;

use Dennenboom\Findle\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'last_visit_at');
        $sortDirection = $request->get('direction', 'desc');
        $search = $request->get('search', '');

        $query = PageView::selectRaw('SUBSTRING_INDEX(url, "?", 1) as base_url')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('MAX(created_at) as last_visit_at')
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

        $days = $request->get('days', 90);
        $sinceDate = now()->subDays($days);

        $totalStats = [
            'total_visits'        => PageView::where('created_at', '>=', $sinceDate)->sum('visits_count'),
            'total_unique_visits' => PageView::where('created_at', '>=', $sinceDate)->count(),
            'avg_views_per_visit' => $this->calculateAvgViewsPerVisit($sinceDate),
            'top_pages'           => $this->getTopPages($sinceDate, 5),
            'days'                => $days,
        ];

        $dailyStats = $this->getDailyStats($sinceDate);

        return view(
            'findle::statistics.index',
            compact(
                'pageViews',
                'sortBy',
                'sortDirection',
                'search',
                'totalStats',
                'dailyStats'
            )
        );
    }

    private function calculateAvgViewsPerVisit($since)
    {
        $totalVisits = PageView::where('created_at', '>=', $since)->sum('visits_count');
        $uniqueVisits = PageView::where('created_at', '>=', $since)->count();

        return $uniqueVisits > 0 ? round($totalVisits / $uniqueVisits, 2) : 0;
    }

    private function getTopPages($since, $limit)
    {
        return PageView::where('created_at', '>=', $since)
            ->selectRaw('SUBSTRING_INDEX(url, "?", 1) as url')
            ->selectRaw('SUM(visits_count) as visit_count')
            ->groupBy('url')
            ->orderBy('visit_count', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getDailyStats($since)
    {
        return PageView::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visits')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date'          => $item->date,
                    'total_visits'  => $item->total_visits,
                    'unique_visits' => $item->unique_visits,
                ];
            });
    }

    public function show(Request $request)
    {
        $url = $request->get('url');

        if (!$url) {
            return redirect()->route('findle.statistics.index');
        }

        $pageViewStats = PageView::where('url', 'like', $url . '%')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('SUM(visits_count) as total_visits')
            ->selectRaw('MAX(created_at) as last_visit_at')
            ->selectRaw('MIN(created_at) as first_visit_at')
            ->first();

        $topOtherPages = PageView::selectRaw('SUBSTRING_INDEX(url, "?", 1) as url')
            ->selectRaw('SUM(visits_count) as visit_count')
            ->whereIn('visitor_hash', function ($query) use ($url) {
                $query->select('visitor_hash')
                    ->from(config('findle.table_name', 'page_views'))
                    ->where('url', 'like', $url . '%');
            })
            ->whereRaw('SUBSTRING_INDEX(url, "?", 1) != ?', [$url])
            ->groupBy('url')
            ->orderBy('visit_count', 'desc')
            ->limit(10)
            ->get();

        $userAgentStats = PageView::where('url', 'like', $url . '%')
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

        $queryDataStats = PageView::where('url', 'like', $url . '?%')
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

        $dailyVisits = PageView::where('url', 'like', $url . '%')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(visits_count) as visits')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visits')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return view(
            'findle::statistics.show',
            compact(
                'url',
                'pageViewStats',
                'topOtherPages',
                'userAgentStats',
                'queryDataStats',
                'dailyVisits'
            )
        );
    }
}
