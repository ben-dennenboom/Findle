<?php

namespace Dennenboom\Findle\Http\Controllers;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Dennenboom\Findle\Models\PageView;
use Dennenboom\Findle\Models\PageViewVisit;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ComparisonsController extends Controller
{
    public const MAX_PAGES = 8;

    public const PERIODS = [7, 30, 90, 365];

    public function index(Request $request)
    {
        $headless = $request->get('headless', false);

        $days = (int) $request->get('days', 30);

        if (! in_array($days, self::PERIODS)) {
            $days = 30;
        }

        $urls = collect($request->input('urls', []))
            ->filter(fn ($url) => is_string($url) && trim($url) !== '')
            ->map(fn (string $url) => PageView::normalizeBaseUrl(trim($url)))
            ->unique()
            ->take(self::MAX_PAGES)
            ->values();

        $sinceDate = now()->subDays($days - 1)->startOfDay();

        $pages = $urls->map(fn (string $url) => [
            'url'             => $url,
            'total_views'     => PageViewVisit::joinPageViews()->forBaseUrl($url)->since($sinceDate)->count(),
            'last_view_at'    => PageView::forBaseUrl($url)->max('updated_at'),
            'top_other_pages' => PageViewVisit::topOtherPages($url, 5, $sinceDate),
            'daily_views'     => PageViewVisit::dailyStats($sinceDate, $url)->pluck('total_visits'),
        ]);

        $availablePages = PageView::select('base_url')
            ->groupBy('base_url')
            ->orderByRaw('SUM(visits_count) desc')
            ->limit(500)
            ->pluck('base_url');

        $dates = collect(CarbonPeriod::create($sinceDate, now()->startOfDay()))
            ->map(fn (CarbonInterface $date) => $date->toDateString());

        $data = compact(
            'pages',
            'dates',
            'days',
            'availablePages',
            'headless'
        );

        if ($request->wantsJson() || $request->has('embedded')) {
            return response()->json($data);
        }

        return view('findle::statistics.compare', $data);
    }
}
