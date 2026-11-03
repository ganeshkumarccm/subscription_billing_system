@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Your subscription and usage overview.</p>
    </div>
    <button id="dashboard-retry" type="button" class="btn btn-outline-primary d-none">Retry</button>
</div>

<div id="dashboard-error" class="alert alert-danger d-none" role="alert"></div>

<div id="dashboard-loading" class="row g-3 mb-4">
    @for ($i = 0; $i < 3; $i++)
        <div class="col-12 col-md-4">
            <div class="card shadow-sm placeholder-glow">
                <div class="card-body">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder placeholder-lg col-5 mt-2"></span>
                </div>
            </div>
        </div>
    @endfor
</div>

<div id="dashboard-content" class="d-none">
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Current Cycle Usage</h2>
                    <p id="stat-usage" class="display-6 mb-0">—</p>
                    <small id="stat-usage-percent" class="text-muted"></small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Projected Overage Revenue</h2>
                    <p id="stat-revenue" class="display-6 mb-0">—</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Active Plan</h2>
                    <p id="stat-plan" class="display-6 mb-0">—</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Daily Usage</h2>
                    <div style="height: 300px">
                        <canvas id="usage-chart" aria-label="Daily usage for the last 30 days"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-danger h-100">
                <div class="card-body">
                    <h2 class="h5 text-danger">Churn Risk</h2>
                    <p id="churn-risk-count" class="display-6 text-danger mb-2">—</p>
                    <p class="text-muted mb-0">customers with usage down more than 50%.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Top 5 Customers</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Usage</th>
                            <th>Allowance</th>
                            <th style="min-width: 180px">Allowance used</th>
                        </tr>
                    </thead>
                    <tbody id="top-customers"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    let usageChart = null;
    const loading = document.getElementById('dashboard-loading');
    const content = document.getElementById('dashboard-content');
    const error = document.getElementById('dashboard-error');
    const retry = document.getElementById('dashboard-retry');

    function renderChart(dailyUsage) {
        if (usageChart) {
            usageChart.destroy();
        }

        usageChart = new Chart(document.getElementById('usage-chart'), {
            type: 'line',
            data: {
                labels: dailyUsage.map((item) => item.date),
                datasets: [{
                    label: 'Units',
                    data: dailyUsage.map((item) => item.units),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.12)',
                    fill: true,
                    tension: 0.25,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    function renderCustomers(customers) {
        const table = document.getElementById('top-customers');
        table.replaceChildren();

        if (!customers.length) {
            table.innerHTML = '<tr><td colspan="4" class="text-muted">No usage recorded yet.</td></tr>';
            return;
        }

        customers.forEach((customer) => {
            const percent = Math.min(100, Math.max(0, Number(customer.allowance_used_percent) || 0));
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>#${customer.customer_id}</td>
                <td>${customer.usage_units}</td>
                <td>${customer.allowance_units}</td>
                <td>
                    <div class="progress" role="progressbar" aria-valuenow="${percent}"
                        aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: ${percent}%">${percent}%</div>
                    </div>
                </td>
            `;
            table.appendChild(row);
        });
    }

    async function loadDashboard() {
        loading.classList.remove('d-none');
        content.classList.add('d-none');
        error.classList.add('d-none');
        retry.classList.add('d-none');

        try {
            const response = await window.api('/dashboard');
            const data = response.data || {};
            const cycle = data.current_cycle || {};
            const usage = Number(cycle.usage_units || 0);
            const allowance = Number(cycle.allowance_units || 0);

            document.getElementById('stat-usage').textContent = `${usage} / ${allowance}`;
            document.getElementById('stat-usage-percent').textContent =
                `${Number(cycle.usage_percent || 0)}% of included units`;
            document.getElementById('stat-revenue').textContent =
                window.formatINR(Number(data.projected_overage_revenue_paise || 0));
            document.getElementById('stat-plan').textContent = data.active_plan_name || '—';
            document.getElementById('churn-risk-count').textContent =
                Number((data.churn_risk || {}).count || 0);
            renderCustomers(data.top_customers || []);
            renderChart(data.daily_usage || []);
            loading.classList.add('d-none');
            content.classList.remove('d-none');
        } catch (requestError) {
            loading.classList.add('d-none');
            error.textContent = requestError.message || 'Unable to load the dashboard.';
            error.classList.remove('d-none');
            retry.classList.remove('d-none');
        }
    }

    retry.addEventListener('click', loadDashboard);
    loadDashboard();
    window.setInterval(loadDashboard, 60000);
}());
</script>
@endpush
