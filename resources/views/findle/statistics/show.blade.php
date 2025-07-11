<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Details - Findle</title>
  <script src="{{ config('findle.assets.tailwind_css') }}"></script>
  <script src="{{ config('findle.assets.chartjs') }}"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
  <div class="mb-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Page Details</h1>
        <p class="text-gray-600 break-all">{{ $url }}</p>
      </div>
      <a href="{{ route('findle.statistics.index') }}"
         class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
        Back to Overview
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Unique Visitors</p>
          <p class="text-2xl font-bold text-gray-900">{{ number_format($pageViewStats->unique_visitors) }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Total Visits</p>
          <p class="text-2xl font-bold text-gray-900">{{ number_format($pageViewStats->total_visits) }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">First Visit</p>
          <p class="text-sm text-gray-900">{{ $pageViewStats->first_visit_at ? \Carbon\Carbon::parse($pageViewStats->first_visit_at)->format('Y-m-d H:i') : 'Unknown' }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">Last Visit</p>
          <p class="text-sm text-gray-900">{{ $pageViewStats->last_visit_at ? \Carbon\Carbon::parse($pageViewStats->last_visit_at)->format('Y-m-d H:i') : 'Unknown' }}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Visits (Last 30 Days)</h3>
      <canvas id="dailyVisitsChart" width="400" height="200"></canvas>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Browser Statistics</h3>
      @if($userAgentStats->count() > 0)
        <div class="space-y-3">
          @foreach($userAgentStats as $browser => $count)
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ $browser }}</span>
              <span class="text-sm font-medium text-gray-900">{{ number_format($count) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full"
                   style="width: {{ ($count / $pageViewStats->total_visits) * 100 }}%"></div>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-gray-500">No browser data available</p>
      @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Other Pages Visited by Same Users</h3>
      @if($topOtherPages->count() > 0)
        <div class="space-y-2">
          @foreach($topOtherPages as $page)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 truncate" title="{{ $page->url }}">
                  {{ $page->url }}
                </p>
              </div>
              <span class="text-sm text-gray-500 ml-2">{{ number_format($page->visit_count) }}</span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-gray-500">No related pages found</p>
      @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Most Common Query Parameters</h3>
      @if($queryDataStats->count() > 0)
        <div class="space-y-2">
          @foreach($queryDataStats as $param => $count)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-sm text-gray-900 font-mono">{{ $param }}</span>
              <span class="text-sm text-gray-500">{{ number_format($count) }}</span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-gray-500">No query parameters recorded</p>
      @endif
    </div>
  </div>
</div>

<script>
  const ctx = document.getElementById('dailyVisitsChart').getContext('2d');
  const dailyData = @json($dailyVisits);

  const labels = dailyData.map(item => item.date).reverse();
  const visits = dailyData.map(item => item.visits).reverse();
  const uniqueVisits = dailyData.map(item => item.unique_visits).reverse();

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Total Visits',
          data: visits,
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.1
        },
        {
          label: 'Unique Visits',
          data: uniqueVisits,
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.1
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>
</body>
</html>