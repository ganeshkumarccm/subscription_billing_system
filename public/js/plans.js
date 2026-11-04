(function () {
    const loading = document.getElementById('plans-loading');
    const content = document.getElementById('plans-content');
    const error = document.getElementById('plans-error');
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };
    window.api('/plans').then((response) => {
        const table = document.getElementById('plans-table');
        const plans = response.data || [];
        document.getElementById('plan-count').textContent = plans.length;
        document.getElementById('active-plan-count').textContent = plans.filter((plan) => plan.is_active).length;
        document.getElementById('plan-count-label').textContent = `${plans.length} plan${plans.length === 1 ? '' : 's'}`;
        plans.forEach((plan) => {
            const row = document.createElement('tr');
            const name = cell('');
            const icon = document.createElement('span');
            icon.className = 'plan-row-icon';
            icon.innerHTML = '<i class="bi bi-box-seam"></i>';
            const nameText = document.createElement('span');
            nameText.className = 'fw-semibold';
            nameText.textContent = plan.name;
            name.append(icon, nameText);
            const cycle = cell('');
            const cycleBadge = document.createElement('span');
            cycleBadge.className = 'cycle-badge';
            cycleBadge.textContent = plan.billing_cycle;
            cycle.append(cycleBadge);
            const status = cell('');
            const statusBadge = document.createElement('span');
            statusBadge.className = `status-badge ${plan.is_active ? 'status-active' : 'status-inactive'}`;
            statusBadge.textContent = plan.is_active ? 'Active' : 'Inactive';
            status.append(statusBadge);
            row.append(name, cell(window.formatINR(plan.base_price_paise)), cycle,
                cell(Number(plan.included_units).toLocaleString('en-IN')), cell(window.formatINR(plan.overage_rate_paise_per_unit)),
                status);
            table.append(row);
        });
        loading.classList.add('d-none'); content.classList.remove('d-none');
    }).catch((requestError) => {
        loading.classList.add('d-none');
        error.textContent = requestError.message || 'Unable to load plans.';
        error.classList.remove('d-none');
    });
}());
