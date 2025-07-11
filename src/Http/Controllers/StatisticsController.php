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

        $query = PageView::select('url')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('COUNT(*) as total_visits')
            ->selectRaw('MAX(created_at) as last_visit_at')
            ->groupBy('url');

        if ($search) {
            $query->where('url', 'like', '%' . $search . '%');
        }

        $allowedSorts = ['url', 'unique_visitors', 'total_visits', 'last_visit_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('last_visit_at', 'desc');
        }

        $pageViews = $query->paginate(25);
        $pageViews->appends($request->query());

        $totalStats = [
            'total_pages'           => PageView::select('url')->distinct()->count(),
            'total_unique_visitors' => PageView::select('visitor_hash')->distinct()->count(),
            'total_visits'          => PageView::count(),
            'visits_today'          => PageView::whereDate('created_at', today())->count(),
            'visits_this_week'      => PageView::where('created_at', '>=', now()->startOfWeek())->count(),
            'visits_this_month'     => PageView::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view(
            'findle::statistics.index',
            compact(
                'pageViews',
                'sortBy',
                'sortDirection',
                'search',
                'totalStats'
            )
        );
    }

    public function show(Request $request)
    {
        $url = $request->get('url');

        if (!$url) {
            return redirect()->route('findle.statistics.index');
        }

        $pageViewStats = PageView::where('url', $url)
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->selectRaw('COUNT(*) as total_visits')
            ->selectRaw('MAX(created_at) as last_visit_at')
            ->selectRaw('MIN(created_at) as first_visit_at')
            ->first();

        $topOtherPages = PageView::select('url')
            ->selectRaw('COUNT(*) as visit_count')
            ->whereIn('visitor_hash', function ($query) use ($url) {
                $query->select('visitor_hash')
                    ->from(config('findle.table_name', 'page_views'))
                    ->where('url', $url);
            })
            ->where('url', '!=', $url)
            ->groupBy('url')
            ->orderBy('visit_count', 'desc')
            ->limit(10)
            ->get();

        $userAgentStats = PageView::where('url', $url)
            ->selectRaw('user_agent')
            ->selectRaw('COUNT(*) as count')
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

        $queryDataStats = PageView::where('url', $url)
            ->whereNotNull('query_data')
            ->where('query_data', '!=', '[]')
            ->get()
            ->flatMap(function ($view) {
                return collect($view->query_data)->keys();
            })
            ->countBy()
            ->sortDesc()
            ->take(10);

        $dailyVisits = PageView::where('url', $url)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as visits')
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
