<div class="findle-statistics-detail">
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2 class="h4 mb-1">Page Details</h2>
        <p class="text-muted mb-0">{{ $url }}</p>
      </div>
      <a href="{{ route('findle.statistics.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Statistics
      </a>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card border-left-primary h-100">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-primary">Unique Visitors</div>
              <div class="h4 mb-0 font-weight-bold">{{ number_format($pageViewStats->unique_visitors) }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-success">Total Visits</div>
              <div class="h4 mb-0 font-weight-bold">{{ number_format($pageViewStats->total_visits) }}</div>
            </div>
            <div class="col-auto">
              <i class="fas fa-eye fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-info">First Visit</div>
              <div class="h6 mb-0 font-weight-bold">
                {{ $pageViewStats->first_visit_at ? \Carbon\Carbon::parse($pageViewStats->first_visit_at)->format('M d, Y') : 'Never' }}
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
              <div class="text-xs font-weight-bold text-uppercase mb-1 text-warning">Last Visit</div>
              <div class="h6 mb-0 font-weight-bold">
                {{ $pageViewStats->last_visit_at ? \Carbon\Carbon::parse($pageViewStats->last_visit_at)->format('M d, Y') : 'Never' }}
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Daily Visits (Last 30 Days)</h5>
        </div>
        <div class="card-body">
          <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="dailyVisitsChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Browser Usage</h5>
        </div>
        <div class="card-body">
          @if($userAgentStats->count() > 0)
            @foreach($userAgentStats as $browser => $count)
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="font-weight-medium">{{ $browser }}</span>
                <span class="badge badge-primary">{{ number_format($count) }}</span>
              </div>
            @endforeach
          @else
            <p class="text-muted text-center mb-0">No browser data available</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if($topOtherPages->count() > 0)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Other Pages Visited by Same Users</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Page URL</th>
                <th class="text-right">Visit Count</th>
              </tr>
            </thead>
            <tbody>
              @foreach($topOtherPages as $page)
                <tr>
                  <td>
                    <a href="{{ route('findle.statistics.show', ['url' => $page->url]) }}" class="text-decoration-none">
                      {{ $page->url }}
                    </a>
                  </td>
                  <td class="text-right">{{ number_format($page->visit_count) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if($queryDataStats->count() > 0)
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Most Used Query Parameters</h5>
        </div>
        <div class="card-body">
          <div class="row">
            @foreach($queryDataStats->take(10) as $param => $weight)
              <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                  <code class="small">{{ $param }}</code>
                  <span class="badge badge-secondary">{{ $weight }}</span>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('dailyVisitsChart').getContext('2d');

    const dailyVisits = @json($dailyVisits);

    const labels = dailyVisits.map(visit => {
        const date = new Date(visit.date);
        return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
    });

    const visitsData = dailyVisits.map(visit => visit.visits);
    const uniqueVisitsData = dailyVisits.map(visit => visit.unique_visits);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Visits',
                    data: visitsData,
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