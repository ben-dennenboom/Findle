<div class="findle-statistics-content">
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2 class="h4 mb-1">Page View Statistics</h2>
        <p class="text-muted mb-0">Track and analyze your website's page views</p>
      </div>
      <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route('findle.statistics.index', ['days' => 7]) }}" 
           class="btn {{ request('days', 90) == 7 ? 'btn-primary' : 'btn-outline-primary' }}">7 days</a>
        <a href="{{ route('findle.statistics.index', ['days' => 30]) }}" 
           class="btn {{ request('days', 90) == 30 ? 'btn-primary' : 'btn-outline-primary' }}">30 days</a>
        <a href="{{ route('findle.statistics.index', ['days' => 90]) }}" 
           class="btn {{ request('days', 90) == 90 ? 'btn-primary' : 'btn-outline-primary' }}">90 days</a>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card border-left-primary h-100">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-primary">
                Total Visits ({{ $totalStats['days'] }} days)
              </div>
              <div class="h4 mb-0 font-weight-bold">{{ number_format($totalStats['total_visits']) }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-eye fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card border-left-success h-100">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-success">
                Total Unique Visits ({{ $totalStats['days'] }} days)
              </div>
              <div class="h4 mb-0 font-weight-bold">{{ number_format($totalStats['total_unique_visits']) }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card border-left-info h-100">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-info">
                Avg Views Per Visit ({{ $totalStats['days'] }} days)
              </div>
              <div class="h4 mb-0 font-weight-bold">{{ $totalStats['avg_views_per_visit'] }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-chart-line fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card border-left-warning h-100">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-warning">
                Top Pages
              </div>
              <div class="mt-2">
                @foreach($totalStats['top_pages']->take(3) as $index => $page)
                  <div class="text-xs text-muted mb-1" title="{{ $page->url }}">
                    {{ $index + 1 }}. {{ preg_replace('#^https?://[^/]+#', '', $page->url) }} ({{ $page->visit_count }})
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Daily Visits (Last {{ $totalStats['days'] }} Days)</h5>
        </div>
        <div class="card-body">
          <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="visitsChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Page Views</h5>
        <form method="GET" class="d-flex">
          <input type="hidden" name="sort" value="{{ $sortBy }}">
          <input type="hidden" name="direction" value="{{ $sortDirection }}">
          <input type="hidden" name="days" value="{{ request('days', 90) }}">
          <div class="input-group input-group-sm">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search URLs..." class="form-control">
            <div class="input-group-append">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
              </button>
              @if($search)
                <a href="{{ route('findle.statistics.index', ['days' => request('days', 90)]) }}" class="btn btn-outline-secondary">
                  <i class="fas fa-times"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
        <tr>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'base_url', 'direction' => $sortBy === 'base_url' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
               class="text-decoration-none">
              URL
              @if($sortBy === 'base_url')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
              @endif
            </a>
          </th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'unique_visitors', 'direction' => $sortBy === 'unique_visitors' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
               class="text-decoration-none">
              Unique Visitors
              @if($sortBy === 'unique_visitors')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
              @endif
            </a>
          </th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_visits', 'direction' => $sortBy === 'total_visits' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
               class="text-decoration-none">
              Total Visits
              @if($sortBy === 'total_visits')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
              @endif
            </a>
          </th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_visit_at', 'direction' => $sortBy === 'last_visit_at' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
               class="text-decoration-none">
              Last Visit
              @if($sortBy === 'last_visit_at')
                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
              @endif
            </a>
          </th>
          <th class="text-right">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($pageViews as $pageView)
          <tr>
            <td>
              <div class="text-truncate" style="max-width: 300px;" title="{{ $pageView->base_url }}">
                {{ $pageView->base_url }}
              </div>
            </td>
            <td>{{ number_format($pageView->unique_visitors) }}</td>
            <td>{{ number_format($pageView->total_visits) }}</td>
            <td>{{ $pageView->last_visit_at ? \Carbon\Carbon::parse($pageView->last_visit_at)->format('M d, Y H:i') : 'Never' }}</td>
            <td class="text-right">
              <a href="{{ route('findle.statistics.show', ['url' => $pageView->base_url]) }}"
                 class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i> Details
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              @if($search)
                <i class="fas fa-search mb-2"></i><br>
                No page views found matching "{{ $search }}"
              @else
                <i class="fas fa-chart-bar mb-2"></i><br>
                No page views recorded yet
              @endif
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($pageViews->hasPages())
      <div class="card-footer">
        {{ $pageViews->appends(request()->query())->links() }}
      </div>
    @endif
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Unique Visits',
                    data: uniqueVisitsData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
                }
            }
        }
    });
});
</script>

<style>
.border-left-primary {
    border-left: 4px solid #007bff;
}
.border-left-success {
    border-left: 4px solid #28a745;
}
.border-left-info {
    border-left: 4px solid #17a2b8;
}
.border-left-warning {
    border-left: 4px solid #ffc107;
}
.text-gray-300 {
    color: #dee2e6;
}
.chart-container {
    position: relative;
    height: 300px;
}
</style>