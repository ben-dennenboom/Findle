<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compare Pages - Findle</title>
  <script src="{{ config('findle.assets.tailwind_css') }}"></script>
  <script src="{{ config('findle.assets.chartjs') }}"></script>
</head>
<body @if(!$headless) class="bg-gray-100" @endif>

@php
  $seriesColors = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
  $selectedUrls = $pages->pluck('url');
  $headlessQuery = $headless ? ['headless' => '1'] : [];
@endphp

<div class="{{ $headless ? '' : 'container mx-auto px-4 py-8' }}">
  <div class="mb-8">
    <div class="flex items-center justify-between">
      <div>
        @if(!$headless)
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Compare Pages</h1>
        @endif
        <p class="text-gray-600">Compare the visits of up to {{ \Dennenboom\Findle\Http\Controllers\ComparisonsController::MAX_PAGES }} pages</p>
      </div>
      <a href="{{ route('findle.statistics.index', $headlessQuery) }}"
         class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
        Back to Overview
      </a>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow p-6 mb-8">
    <form method="GET" action="{{ route('findle.statistics.compare') }}" class="space-y-4">
      @if($headless)
        <input type="hidden" name="headless" value="1">
      @endif

      @foreach($selectedUrls as $selectedUrl)
        <input type="hidden" name="urls[]" value="{{ $selectedUrl }}">
      @endforeach

      @if($pages->isNotEmpty())
        <div class="flex flex-wrap gap-2">
          @foreach($pages as $index => $page)
            <span class="inline-flex items-center gap-2 bg-gray-100 rounded-full pl-3 pr-2 py-1 text-sm text-gray-800 max-w-full">
              <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $seriesColors[$index] }}"></span>
              <span class="truncate" title="{{ $page['url'] }}">{{ preg_replace('#^https?://[^/]+#', '', $page['url']) ?: '/' }}</span>
              <a href="{{ route('findle.statistics.compare', array_merge(['urls' => $selectedUrls->reject(fn ($url) => $url === $page['url'])->values()->all(), 'days' => $days], $headlessQuery)) }}"
                 class="text-gray-400 hover:text-gray-700 px-1"
                 title="Remove from comparison">&times;</a>
            </span>
          @endforeach
        </div>
      @endif

      <div class="flex flex-wrap items-center gap-2">
        @if($pages->count() < \Dennenboom\Findle\Http\Controllers\ComparisonsController::MAX_PAGES)
          <input
                  type="text"
                  name="urls[]"
                  list="findle-available-pages"
                  placeholder="Add a page URL..."
                  class="border border-gray-300 rounded-md px-3 py-2 text-sm flex-1 min-w-0"
          >
          <datalist id="findle-available-pages">
            @foreach($availablePages->diff($selectedUrls) as $availablePage)
              <option value="{{ $availablePage }}"></option>
            @endforeach
          </datalist>
        @endif

        <select name="days" class="border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="this.form.submit()">
          @foreach(\Dennenboom\Findle\Http\Controllers\ComparisonsController::PERIODS as $period)
            <option value="{{ $period }}" @selected($period === $days)>Last {{ $period }} days</option>
          @endforeach
        </select>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
          {{ $pages->count() < \Dennenboom\Findle\Http\Controllers\ComparisonsController::MAX_PAGES ? 'Add page' : 'Update' }}
        </button>
      </div>
    </form>
  </div>

  @if($pages->count() < 2)
    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
      Select at least two pages to compare, either above or with the checkboxes on the overview.
    </div>
  @else
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Daily Views (Last {{ $days }} Days)</h2>
      </div>
      <div class="p-6">
        <div class="relative h-80">
          <canvas id="compareChart"></canvas>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-{{ min($pages->count(), 4) }} gap-6">
      @foreach($pages as $index => $page)
        <div class="bg-white rounded-lg shadow p-6 border-t-4" style="border-top-color: {{ $seriesColors[$index] }}">
          <p class="text-sm font-medium text-gray-900 truncate mb-4" title="{{ $page['url'] }}">
            <a href="{{ route('findle.statistics.show', array_merge(['url' => $page['url']], $headlessQuery)) }}" class="hover:text-blue-600">
              {{ preg_replace('#^https?://[^/]+#', '', $page['url']) ?: '/' }}
            </a>
          </p>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Views ({{ $days }} days)</p>
              <p class="text-2xl font-bold text-gray-900">{{ number_format($page['total_views']) }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Last View</p>
              <p class="text-sm text-gray-900 mt-2">{{ $page['last_view_at'] ? \Carbon\Carbon::parse($page['last_view_at'])->format('Y-m-d H:i') : 'Never' }}</p>
            </div>
          </div>

          <p class="text-sm font-medium text-gray-600 mb-2">Top 5 Other Pages Visited</p>
          @if($page['top_other_pages']->isNotEmpty())
            <div class="space-y-1">
              @foreach($page['top_other_pages'] as $otherPage)
                <div class="flex justify-between items-center text-sm py-1 border-b border-gray-100" title="{{ $otherPage->url }}">
                  <span class="text-gray-900 truncate mr-2">{{ preg_replace('#^https?://[^/]+#', '', $otherPage->url) ?: '/' }}</span>
                  <span class="text-gray-500 flex-shrink-0">{{ number_format($otherPage->visit_count) }}</span>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-sm text-gray-500">No other pages visited</p>
          @endif
        </div>
      @endforeach
    </div>
  @endif
</div>

@if($pages->count() >= 2)
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const seriesColors = @json($seriesColors);
      const pages = @json($pages);

      const labels = @json($dates).map(date => new Date(`${date}T00:00:00`).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}));

      new Chart(document.getElementById('compareChart').getContext('2d'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: pages.map((page, index) => ({
            label: page.url.replace(/^https?:\/\/[^/]+/, '') || '/',
            data: page.daily_views,
            borderColor: seriesColors[index],
            backgroundColor: seriesColors[index],
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBorderWidth: 2,
            pointHoverBorderColor: '#ffffff',
            tension: 0.1,
            fill: false
          }))
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              },
              grid: {
                color: '#f3f4f6'
              }
            },
            x: {
              ticks: {
                maxTicksLimit: 10
              },
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'top',
              labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              usePointStyle: true
            }
          },
          interaction: {
            mode: 'index',
            axis: 'x',
            intersect: false
          }
        }
      });
    });
  </script>
@endif

</body>
</html>
