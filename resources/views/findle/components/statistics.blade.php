@php
  $days = $days ?? 90;
  $sinceDate = now()->subDays($days);

  $totalStats = [
      'total_visits' => \Dennenboom\Findle\Models\PageView::where('created_at', '>=', $sinceDate)->sum('visits_count'),
      'total_unique_visits' => \Dennenboom\Findle\Models\PageView::where('created_at', '>=', $sinceDate)->count(),
      'days' => $days,
  ];

  $topPages = \Dennenboom\Findle\Models\PageView::where('created_at', '>=', $sinceDate)
      ->selectRaw('SUBSTRING_INDEX(url, "?", 1) as url')
      ->selectRaw('SUM(visits_count) as visit_count')
      ->groupBy('url')
      ->orderBy('visit_count', 'desc')
      ->limit(5)
      ->get();

  $dailyStats = \Dennenboom\Findle\Models\PageView::where('created_at', '>=', $sinceDate)
      ->selectRaw('DATE(created_at) as date')
      ->selectRaw('SUM(visits_count) as total_visits')
      ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visits')
      ->groupBy('date')
      ->orderBy('date', 'asc')
      ->get()
      ->map(function ($item) {
          return [
              'date' => $item->date,
              'total_visits' => $item->total_visits,
              'unique_visits' => $item->unique_visits,
          ];
      });
@endphp

<div class="findle-statistics-component {{ $class ?? '' }}">
  <div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Website Statistics</h2>
    <p class="text-gray-600">Track and analyze your website's page views</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Total Visits ({{ $totalStats['days'] }} days)</p>
          <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStats['total_visits']) }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Total Unique Visits ({{ $totalStats['days'] }} days)</p>
          <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStats['total_unique_visits']) }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Avg Visits Per Page</p>
          <p class="text-2xl font-bold text-gray-900">
            {{ $totalStats['total_unique_visits'] > 0 ? round($totalStats['total_visits'] / $totalStats['total_unique_visits'], 2) : 0 }}
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Top 5 Most Viewed Pages</p>
          <div class="mt-2 space-y-1">
            @foreach($topPages as $index => $page)
              <div class="text-xs text-gray-600" title="{{ $page->url }}">
                {{ $index + 1 }}. {{ preg_replace('#^https?://[^/]+#', '', $page->url) }} ({{ $page->visit_count }})
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($showChart ?? true)
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Daily Visits (Last {{ $totalStats['days'] }} Days)</h3>
      </div>
      <div class="p-6">
        <div class="relative">
          <canvas id="findle-chart-{{ $chartId ?? 'default' }}" width="800" height="300"></canvas>
        </div>
      </div>
    </div>
  @endif

  @if($showFullLink ?? true)
    <div class="text-center">
      <a href="{{ route('findle.statistics.index') }}"
         class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition">
        View Full Statistics
      </a>
    </div>
  @endif
</div>

@if($showChart ?? true)
  @once
    <script src="{{ config('findle.assets.chartjs') }}"></script>
  @endonce

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const chartId = 'findle-chart-{{ $chartId ?? 'default' }}';
      const ctx = document.getElementById(chartId);

      if (!ctx) return;

      const dailyStats = @json($dailyStats);

      const labels = dailyStats.map(stat => {
        const date = new Date(stat.date);
        return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
      });

      const totalVisitsData = dailyStats.map(stat => stat.total_visits);
      const uniqueVisitsData = dailyStats.map(stat => stat.unique_visits);

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Visits',
              data: totalVisitsData,
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.1,
              fill: false
            },
            {
              label: 'Unique Visits',
              data: uniqueVisitsData,
              borderColor: 'rgb(16, 185, 129)',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.1,
              fill: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            },
            x: {
              display: true,
              ticks: {
                maxTicksLimit: 10
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          },
          interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
          }
        }
      });
    });
  </script>
@endif
