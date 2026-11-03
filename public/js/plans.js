(function () {
    const loading = document.getElementById('plans-loading');
    const content = document.getElementById('plans-content');
    const error = document.getElementById('plans-error');
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };
    window.api('/plans').then((response) => {
        const table = document.getElementById('plans-table');
        (response.data || []).forEach((plan) => {
            const row = document.createElement('tr');
            row.append(cell(plan.name), cell(window.formatINR(plan.base_price_paise)), cell(plan.billing_cycle),
                cell(plan.included_units), cell(window.formatINR(plan.overage_rate_paise_per_unit)),
                cell(plan.is_active ? 'Active' : 'Inactive'));
            table.append(row);
        });
        loading.classList.add('d-none'); content.classList.remove('d-none');
    }).catch((requestError) => {
        loading.classList.add('d-none');
        error.textContent = requestError.message || 'Unable to load plans.';
        error.classList.remove('d-none');
    });
}());
