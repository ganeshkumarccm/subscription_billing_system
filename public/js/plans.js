(function () {
    const loading = document.getElementById('plans-loading');
    const content = document.getElementById('plans-content');
    const error = document.getElementById('plans-error');
    const planModal = new bootstrap.Modal(document.getElementById('plan-modal'));
    let editingPlanId = null;
    let plans = [];
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };
    async function loadPlans() {
        loading.classList.remove('d-none');
        content.classList.add('d-none');
        error.classList.add('d-none');
        try {
        const response = await window.api('/plans');
        const table = document.getElementById('plans-table');
        table.replaceChildren();
        plans = response.data || [];
        document.getElementById('plan-count').textContent = plans.length;
        document.getElementById('active-plan-count').textContent = plans.filter((plan) => plan.is_active).length;
        document.getElementById('plan-count-label').textContent = `${plans.length} plan${plans.length === 1 ? '' : 's'}`;
        plans.forEach((plan) => {
            const row = document.createElement('tr');
            const name = cell('');
            const icon = document.createElement('span');
            icon.className = 'plan-row-icon';
            const iconGlyph = document.createElement('i');
            iconGlyph.className = 'bi bi-box-seam';
            iconGlyph.setAttribute('aria-hidden', 'true');
            icon.append(iconGlyph);
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
            const actions = document.createElement('td');
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'table-action';
            edit.setAttribute('aria-label', `Edit ${plan.name}`);
            edit.title = 'Edit plan';
            const editIcon = document.createElement('i');
            editIcon.className = 'bi bi-pencil';
            editIcon.setAttribute('aria-hidden', 'true');
            edit.append(editIcon);
            edit.addEventListener('click', () => openPlanForm(plan));
            actions.append(edit);
            row.append(name, cell(window.formatINR(plan.base_price_paise)), cycle,
                cell(Number(plan.included_units).toLocaleString('en-IN')), cell(window.formatINR(plan.overage_rate_paise_per_unit)),
                status, actions);
            table.append(row);
        });
        loading.classList.add('d-none');
        content.classList.remove('d-none');
        } catch (requestError) {
        error.textContent = requestError.message || 'Unable to load plans.';
        error.classList.remove('d-none');
        loading.classList.add('d-none');
        }
    }

    loadPlans();

    function openPlanForm(plan = null) {
        editingPlanId = plan ? plan.id : null;
        document.getElementById('plan-modal-title').textContent = plan ? 'Edit plan' : 'Add plan';
        document.getElementById('plan-name').value = plan?.name || '';
        document.getElementById('plan-price').value = plan?.base_price_paise ?? '';
        document.getElementById('plan-cycle').value = plan?.billing_cycle || 'monthly';
        document.getElementById('plan-included').value = plan?.included_units ?? '';
        document.getElementById('plan-overage').value = plan?.overage_rate_paise_per_unit ?? '';
        document.getElementById('plan-active').checked = plan ? Boolean(plan.is_active) : true;
        clearErrors();
        planModal.show();
    }

    function clearErrors() {
        document.querySelectorAll('#plan-form .is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        document.querySelectorAll('#plan-form [data-error-for]').forEach((field) => { field.textContent = ''; });
        document.getElementById('plan-form-alert').classList.add('d-none');
    }

    document.getElementById('add-plan-button').addEventListener('click', () => openPlanForm());
    document.getElementById('plan-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        const button = document.getElementById('save-plan-button');
        const body = Object.fromEntries(new FormData(event.currentTarget).entries());
        body.is_active = document.getElementById('plan-active').checked;
        window.setLoading(button, true);
        try {
            await window.api(editingPlanId ? `/plans/${editingPlanId}` : '/plans', {
                method: editingPlanId ? 'PUT' : 'POST',
                body,
            });
            planModal.hide();
            await loadPlans();
        } catch (requestError) {
            if (requestError.status === 422) {
                Object.entries(requestError.errors || {}).forEach(([field, messages]) => {
                    const input = document.querySelector(`#plan-form [name="${field}"]`);
                    const feedback = document.querySelector(`#plan-form [data-error-for="${field}"]`);
                    input?.classList.add('is-invalid');
                    if (feedback) feedback.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
                });
            } else {
                document.getElementById('plan-form-alert').textContent = requestError.message || 'Unable to save plan.';
                document.getElementById('plan-form-alert').classList.remove('d-none');
            }
        } finally {
            window.setLoading(button, false);
        }
    });
}());
