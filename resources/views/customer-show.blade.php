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
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 id="customer-name" class="h3 mb-1"></h1><p id="customer-email" class="text-muted mb-0"></p></div>
        <button id="offer-plan-button" type="button" class="btn btn-primary"><i class="bi bi-gift me-1"></i>Offer plan</button>
    </div>
    <div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Usage (30 days)</h2><div style="height:280px"><canvas id="customer-usage-chart"></canvas></div></div></div>
    <div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Segment history</h2><div class="table-responsive"><table class="table"><thead><tr><th>Plan</th><th>Starts</th><th>Ends</th></tr></thead><tbody id="segments-table"></tbody></table></div></div></div>
    <div class="card shadow-sm"><div class="card-body"><h2 class="h5">Invoices</h2><div class="table-responsive"><table class="table"><thead><tr><th>Period</th><th>Subtotal</th><th>Status</th></tr></thead><tbody id="invoices-table"></tbody></table></div></div></div>
</div>

<div class="modal fade" id="offer-plan-modal" tabindex="-1" aria-labelledby="offer-plan-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content plan-modal">
            <div class="modal-header">
                <div><h2 id="offer-plan-title" class="modal-title fs-5">Offer a new plan</h2><p class="modal-subtitle mb-0">Change the active subscription plan</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="offer-plan-form" novalidate>
                <div class="modal-body">
                    <div id="offer-plan-alert" class="alert alert-danger d-none"></div>
                    <label class="form-label" for="offer-plan-select">Plan</label>
                    <select id="offer-plan-select" class="form-select" required></select>
                    <div class="invalid-feedback" id="offer-plan-feedback"></div>
                    <p class="small text-muted mt-3 mb-0">The change takes effect immediately in UTC and is prorated for the current billing period.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button id="offer-plan-submit" type="submit" class="btn btn-primary">Offer plan</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const id = @json($id);
    let chart = null;
    let customerData = null;
    let plans = [];
    const offerModal = new bootstrap.Modal(document.getElementById('offer-plan-modal'));
    const loading = document.getElementById('customer-loading');
    const content = document.getElementById('customer-content');
    const error = document.getElementById('customer-error');
    const notFound = document.getElementById('customer-not-found');
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };
    const date = (value) => value ? String(value).slice(0, 10) : '—';

    function render(data) {
        customerData = data;
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

    async function loadPlans() {
        const response = await window.api('/plans');
        plans = response.data || [];
        const select = document.getElementById('offer-plan-select');
        select.replaceChildren();
        plans.filter((plan) => plan.is_active).forEach((plan) => {
            const option = document.createElement('option');
            option.value = plan.id;
            option.textContent = `${plan.name} · ${window.formatINR(plan.base_price_paise)} / ${plan.billing_cycle}`;
            select.append(option);
        });
    }

    document.getElementById('offer-plan-button').addEventListener('click', async () => {
        const activeSubscription = (customerData?.subscriptions || []).find((subscription) => subscription.status === 'active');
        if (!activeSubscription) {
            window.showToast('This customer has no active subscription.', 'warning');
            return;
        }
        try {
            await loadPlans();
            if (!plans.some((plan) => plan.is_active)) {
                window.showToast('No active plans are available.', 'warning');
                return;
            }
            document.getElementById('offer-plan-alert').classList.add('d-none');
            offerModal.show();
        } catch (requestError) {
            window.showToast(requestError.message || 'Unable to load plans.', 'danger');
        }
    });

    document.getElementById('offer-plan-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const activeSubscription = (customerData?.subscriptions || []).find((subscription) => subscription.status === 'active');
        const planId = Number(document.getElementById('offer-plan-select').value);
        const button = document.getElementById('offer-plan-submit');
        window.setLoading(button, true);
        try {
            await window.api(`/merchants/${activeSubscription.merchant_id}/subscriptions/${activeSubscription.id}/change-plan`, {
                method: 'POST',
                body: { plan_id: planId },
            });
            offerModal.hide();
            window.showToast('Plan offered successfully.', 'success');
            window.location.reload();
        } catch (requestError) {
            const alert = document.getElementById('offer-plan-alert');
            alert.textContent = requestError.message || 'Unable to change plan.';
            alert.classList.remove('d-none');
        } finally {
            window.setLoading(button, false);
        }
    });

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
