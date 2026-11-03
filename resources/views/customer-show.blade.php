@extends('layouts.app')

@section('title', 'Customer')

@section('content')
<a href="/customers" class="btn btn-link px-0 mb-3">&larr; Customers</a>
<div id="customer-error" class="alert alert-danger d-none"></div>
<div id="customer-not-found" class="text-center py-5 d-none">
    <h1 class="h3">Not found</h1><p class="text-muted">This customer does not exist or is not in your merchant account.</p>
</div>
<div id="customer-loading" class="placeholder-glow"><p class="placeholder col-6"></p><p class="placeholder col-10"></p></div>
<div id="customer-content" class="d-none">
    <h1 id="customer-name" class="h3"></h1><p id="customer-email" class="text-muted"></p>
    <div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Usage (30 days)</h2><div style="height:280px"><canvas id="customer-usage-chart"></canvas></div></div></div>
    <div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Segment history</h2><div class="table-responsive"><table class="table"><thead><tr><th>Plan</th><th>Starts</th><th>Ends</th></tr></thead><tbody id="segments-table"></tbody></table></div></div></div>
    <div class="card shadow-sm"><div class="card-body"><h2 class="h5">Invoices</h2><div class="table-responsive"><table class="table"><thead><tr><th>Period</th><th>Subtotal</th><th>Status</th></tr></thead><tbody id="invoices-table"></tbody></table></div></div></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const id = @json($id);
    let chart = null;
    const loading = document.getElementById('customer-loading');
    const content = document.getElementById('customer-content');
    const error = document.getElementById('customer-error');
    const notFound = document.getElementById('customer-not-found');
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };
    const date = (value) => value ? String(value).slice(0, 10) : '—';

    function render(data) {
        document.getElementById('customer-name').textContent = data.name || 'Customer';
        document.getElementById('customer-email').textContent = data.email || '';
        const segments = document.getElementById('segments-table');
        segments.replaceChildren();
        (data.subscriptions || []).forEach((subscription) => (subscription.segments || []).forEach((segment) => {
            const row = document.createElement('tr');
            row.append(cell(segment.plan ? segment.plan.name : `Plan #${segment.plan_id}`), cell(date(segment.starts_at)), cell(date(segment.ends_at)));
            segments.append(row);
        }));
        const invoices = document.getElementById('invoices-table');
        invoices.replaceChildren();
        (data.invoices || []).forEach((invoice) => {
            const row = document.createElement('tr');
            row.append(cell(`${date(invoice.period_start)} – ${date(invoice.period_end)}`), cell(window.formatINR(invoice.subtotal)), cell(invoice.status));
            invoices.append(row);
        });
        if (chart) chart.destroy();
        const usage = data.usage_30_days || [];
        chart = new Chart(document.getElementById('customer-usage-chart'), {
            type: 'line', data: { labels: usage.map((item) => date(item.usage_date)), datasets: [{ label: 'Units', data: usage.map((item) => item.units_total), borderColor: '#0d6efd', tension: .25 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } },
        });
    }

    window.api(`/customers/${id}`).then((response) => {
        render(response.data || {});
        loading.classList.add('d-none'); content.classList.remove('d-none');
    }).catch((requestError) => {
        loading.classList.add('d-none');
        if (requestError.status === 404) notFound.classList.remove('d-none');
        else { error.textContent = requestError.message || 'Unable to load customer.'; error.classList.remove('d-none'); }
    });
}());
</script>
@endpush
