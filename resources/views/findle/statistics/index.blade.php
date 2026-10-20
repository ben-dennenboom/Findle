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

  @php
    $statRows = [
      [
        'stats'             => $todayStats,
        'period'            => 'today',
        'last_visits_title' => 'Last 10 Visits Today',
        'top_pages_title'   => 'Top 10 Most Viewed Pages Today',
      ],
      [
        'stats'             => $totalStats,
        'period'            => "{$totalStats['days']} days",
        'last_visits_title' => 'Last 10 Visits',
        'top_pages_title'   => 'Top 10 Most Viewed Pages',
      ],
    ];
  @endphp

  <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 mb-8">
    @foreach($statRows as $row)
      <div class="sm:col-span-2 bg-white rounded-lg shadow p-6 min-h-[200px]">
        <p class="text-sm font-medium text-gray-600 break-words">Total Views ({{ $row['period'] }})</p>
        <p class="text-2xl font-bold text-gray-900 break-words">{{ number_format($row['stats']['total_visits']) }}</p>
        <p class="text-sm font-medium text-gray-600 mt-10 break-words">Avg Views Per Visit ({{ $row['period'] }})</p>
        <p class="text-2xl font-bold text-gray-900 break-words">{{ $row['stats']['avg_views_per_visit'] }}</p>
      </div>

      <div class="sm:col-span-5 bg-white rounded-lg shadow p-6 min-h-[200px]">
        <p class="text-sm font-medium text-gray-600 break-words">{{ $row['last_visits_title'] }}</p>
        <div class="mt-2 space-y-2 max-h-40 overflow-y-auto">
          @forelse($row['stats']['last_visits'] as $visit)
            <div class="text-xs border-l-2 border-blue-200 pl-2" title="{{ $visit['url'] }}">
              <div class="font-medium text-gray-800 break-words">{{ $visit['page'] ?: '/' }}</div>
              <div class="text-gray-500 break-words">{{ $visit['timestamp'] }}</div>
            </div>
          @empty
            <p class="text-xs text-gray-500">No visits yet</p>
          @endforelse
        </div>
      </div>

      <div class="sm:col-span-5 bg-white rounded-lg shadow p-6 min-h-[200px]">
        <p class="text-sm font-medium text-gray-600 break-words">{{ $row['top_pages_title'] }}</p>
        <div class="mt-2 space-y-1 overflow-y-auto max-h-40">
          @forelse($row['stats']['top_pages'] as $index => $page)
            @php
              $displayUrl = preg_replace('#^https?://[^/]+#', '', $page->url);
              $displayUrl = $displayUrl ?: '/';
            @endphp
            <div class="text-xs text-gray-600 flex justify-between" title="{{ $page->url }}">
              <span class="break-words flex-1 mr-2">{{ $index + 1 }}. {{ $displayUrl }}</span>
              <span class="font-medium flex-shrink-0">({{ $page->visit_count }})</span>
            </div>
          @empty
            <p class="text-xs text-gray-500">No visits yet</p>
          @endforelse
        </div>
      </div>
    @endforeach
  </div>

  <div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Today's Visits (Per Half Hour)</h2>
    </div>
    <div class="p-6">
      <div class="relative">
        <canvas id="todayVisitsChart" width="800" height="300"></canvas>
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

  <div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">References</h2>
      <p class="text-sm text-gray-500">Visits through <span class="font-mono">{{ collect(config('findle.reference_parameters', ['ref', 'utm_ref']))->map(fn ($parameter) => "?{$parameter}=")->join(', ') }}</span></p>
    </div>

    <div class="overflow-x-auto max-h-96 overflow-y-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unique Visitors</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Views</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        @forelse($references as $reference)
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $reference->reference }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($reference->unique_visitors) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($reference->total_visits) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reference->last_visit_at ? \Carbon\Carbon::parse($reference->last_visit_at)->format('Y-m-d H:i') : 'Never' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No references recorded yet</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <h2 class="text-lg font-medium text-gray-900">Page Views</h2>

          <form id="findle-compare-form" method="GET" action="{{ route('findle.statistics.compare') }}">
            @if($headless)
              <input type="hidden" name="headless" value="1">
            @endif
            <button type="submit" id="findle-compare-button" disabled
                    class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
              Compare selected
            </button>
          </form>
        </div>

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
          <th class="pl-6 py-3 w-4">
            <span class="sr-only">Compare</span>
          </th>
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
              Total Views
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
            <td class="pl-6 py-4">
              <input type="checkbox" name="urls[]" value="{{ $pageView->base_url }}" form="findle-compare-form"
                     class="findle-compare-checkbox rounded border-gray-300" aria-label="Compare {{ $pageView->base_url }}">
            </td>
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
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const maxPages = {{ \Dennenboom\Findle\Http\Controllers\ComparisonsController::MAX_PAGES }};
    const checkboxes = Array.from(document.querySelectorAll('.findle-compare-checkbox'));
    const compareButton = document.getElementById('findle-compare-button');

    const updateCompareState = function () {
      const selectedCount = checkboxes.filter(checkbox => checkbox.checked).length;

      compareButton.disabled = selectedCount < 2;
      compareButton.textContent = selectedCount > 0 ? `Compare selected (${selectedCount})` : 'Compare selected';

      checkboxes.forEach(function (checkbox) {
        checkbox.disabled = !checkbox.checked && selectedCount >= maxPages;
      });
    };

    checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateCompareState));
    updateCompareState();
  });
</script>

<script src="{{ config('findle.assets.chartjs') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const renderVisitsChart = function (canvasId, labels, totalVisitsData, uniqueVisitsData) {
      new Chart(document.getElementById(canvasId).getContext('2d'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Views',
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
                maxTicksLimit: 12
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
    };

    const halfHourlyStats = @json($halfHourlyStats);

    renderVisitsChart(
      'todayVisitsChart',
      halfHourlyStats.map(stat => stat.time),
      halfHourlyStats.map(stat => stat.total_visits),
      halfHourlyStats.map(stat => stat.unique_visits)
    );

    const dailyStats = @json($dailyStats);

    renderVisitsChart(
      'visitsChart',
      dailyStats.map(stat => new Date(stat.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
      dailyStats.map(stat => stat.total_visits),
      dailyStats.map(stat => stat.unique_visits)
    );
  });
</script>

</body>
</html>
