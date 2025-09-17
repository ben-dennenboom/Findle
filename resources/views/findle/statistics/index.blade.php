<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Statistics - Findle</title>
  <script src="{{ config('findle.assets.tailwind_css') }}"></script>
</head>
<body @if(!$headless) class="bg-gray-100" @endif>

<div class="{{ $headless ? '' : 'container mx-auto px-4 py-8' }}">
  @if(!$headless)
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Page View Statistics</h1>
      <p class="text-gray-600">Track and analyze your website's page views</p>
    </div>
  @endif

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
          <p class="text-sm font-medium text-gray-600">Avg Views Per Visit ({{ $totalStats['days'] }} days)</p>
          <p class="text-2xl font-bold text-gray-900">{{ $totalStats['avg_views_per_visit'] }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Top 5 Most Viewed Pages</p>
          <div class="mt-2 space-y-1">
            @foreach($totalStats['top_pages'] as $index => $page)
              <div class="text-xs text-gray-600" title="{{ $page->url }}">
                {{ $index + 1 }}. {{ preg_replace('#^https?://[^/]+#', '', $page->url) }} ({{ $page->visit_count }})
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Daily Visits (Last {{ $totalStats['days'] }} Days)</h2>
    </div>
    <div class="p-6">
      <div class="relative">
        <canvas id="visitsChart" width="800" height="300"></canvas>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900">Page Views</h2>

        <form method="GET" class="flex items-center space-x-2">
          <input type="hidden" name="sort" value="{{ $sortBy }}">
          <input type="hidden" name="direction" value="{{ $sortDirection }}">
          @if($headless)
            <input type="hidden" name="headless" value="1">
          @endif
          <input
                  type="text"
                  name="search"
                  value="{{ $search }}"
                  placeholder="Search URLs..."
                  class="border border-gray-300 rounded-md px-3 py-2 text-sm"
          >
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
            Search
          </button>
          @if($search)
            <a href="{{ route('findle.statistics.index', $headless ? ['headless' => '1'] : []) }}" class="text-gray-500 text-sm hover:text-gray-700">
              Clear
            </a>
          @endif
        </form>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <a href="{{ request()->fullUrlWithQuery(array_merge(['sort' => 'base_url', 'direction' => $sortBy === 'base_url' && $sortDirection === 'asc' ? 'desc' : 'asc'], $headless ? ['headless' => '1'] : [])) }}"
               class="hover:text-gray-700">
              URL
              @if($sortBy === 'base_url')
                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
              @endif
            </a>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <a href="{{ request()->fullUrlWithQuery(array_merge(['sort' => 'unique_visitors', 'direction' => $sortBy === 'unique_visitors' && $sortDirection === 'asc' ? 'desc' : 'asc'], $headless ? ['headless' => '1'] : [])) }}"
               class="hover:text-gray-700">
              Unique Visitors
              @if($sortBy === 'unique_visitors')
                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
              @endif
            </a>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <a href="{{ request()->fullUrlWithQuery(array_merge(['sort' => 'total_visits', 'direction' => $sortBy === 'total_visits' && $sortDirection === 'asc' ? 'desc' : 'asc'], $headless ? ['headless' => '1'] : [])) }}"
               class="hover:text-gray-700">
              Total Visits
              @if($sortBy === 'total_visits')
                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
              @endif
            </a>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <a href="{{ request()->fullUrlWithQuery(array_merge(['sort' => 'last_visit_at', 'direction' => $sortBy === 'last_visit_at' && $sortDirection === 'asc' ? 'desc' : 'asc'], $headless ? ['headless' => '1'] : [])) }}"
               class="hover:text-gray-700">
              Last Visit
              @if($sortBy === 'last_visit_at')
                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
              @endif
            </a>
          </th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        @forelse($pageViews as $pageView)
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $pageView->base_url }}">
                {{ $pageView->base_url }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ number_format($pageView->unique_visitors) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ number_format($pageView->total_visits) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ $pageView->last_visit_at ? \Carbon\Carbon::parse($pageView->last_visit_at)->format('Y-m-d H:i') : 'Never' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <a href="{{ route('findle.statistics.show', array_merge(['url' => $pageView->base_url], $headless ? ['headless' => '1'] : [])) }}"
                 class="text-blue-600 hover:text-blue-900">
                View Details
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
              @if($search)
                No page views found matching "{{ $search }}"
              @else
                No page views recorded yet
              @endif
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($pageViews->hasPages())
      <div class="px-6 py-4 border-t border-gray-200">
        {{ $pageViews->links() }}
      </div>
    @endif
  </div>
</div>

<script src="{{ config('findle.assets.chartjs') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('visitsChart').getContext('2d');

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

</body>
</html>
