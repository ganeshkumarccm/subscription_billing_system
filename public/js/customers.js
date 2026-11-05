(function () {
    let page = 1;
    let timer;
    const search = document.getElementById('customer-search');
    const loading = document.getElementById('customers-loading');
    const content = document.getElementById('customers-content');
    const error = document.getElementById('customers-error');
    const customerModal = new bootstrap.Modal(document.getElementById('customer-modal'));
    let editingCustomerId = null;
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };

    async function load() {
        loading.classList.remove('d-none'); content.classList.add('d-none'); error.classList.add('d-none');
        const params = new URLSearchParams({ page, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        try {
            const response = await window.api(`/customers?${params}`);
            const table = document.getElementById('customers-table');
            table.replaceChildren();
            const customers = response.data || [];
            const meta = response.meta || {};
            document.getElementById('customer-count').textContent = Number(meta.total || customers.length).toLocaleString('en-IN');
            document.getElementById('customer-page-label').textContent = `${meta.current_page || 1} / ${meta.last_page || 1}`;
            document.getElementById('customer-search-label').textContent = search.value.trim() || 'All customers';
            document.getElementById('customer-results-label').textContent =
                `Showing ${customers.length} of ${Number(meta.total || customers.length).toLocaleString('en-IN')} customers`;
            customers.forEach((customer) => {
                const row = document.createElement('tr');
                const link = document.createElement('a');
                link.href = `/customers/${encodeURIComponent(customer.id)}`;
                link.textContent = customer.name || `Customer #${customer.id}`;
                link.className = 'customer-name-link';
                const avatar = document.createElement('span');
                avatar.className = 'customer-avatar';
                avatar.textContent = (customer.name || 'C').charAt(0).toUpperCase();
                const name = document.createElement('td');
                name.append(avatar, link);
                const action = document.createElement('td');
                action.className = 'text-end';
                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'table-action me-1';
                edit.setAttribute('aria-label', `Edit ${customer.name || `customer ${customer.id}`}`);
                edit.title = 'Edit customer';
                const editIcon = document.createElement('i');
                editIcon.className = 'bi bi-pencil';
                editIcon.setAttribute('aria-hidden', 'true');
                edit.append(editIcon);
                edit.addEventListener('click', () => openCustomerForm(customer));
                const view = document.createElement('a');
                view.href = link.href;
                view.className = 'table-action';
                view.setAttribute('aria-label', `View ${customer.name || `customer ${customer.id}`}`);
                view.setAttribute('title', 'View customer');
                const arrow = document.createElement('i');
                arrow.className = 'bi bi-eye';
                arrow.setAttribute('aria-hidden', 'true');
                view.append(arrow);
                action.append(edit, view);
                row.append(name, cell(customer.email), action);
                table.append(row);
            });
            renderPagination(response.meta || {});
            loading.classList.add('d-none'); content.classList.remove('d-none');
        } catch (requestError) {
            loading.classList.add('d-none'); error.textContent = requestError.message || 'Unable to load customers.'; error.classList.remove('d-none');
        }
    }

    function openCustomerForm(customer = null) {
        editingCustomerId = customer ? customer.id : null;
        document.getElementById('customer-modal-title').textContent = customer ? 'Edit customer' : 'Add customer';
        document.getElementById('customer-name').value = customer?.name || '';
        document.getElementById('customer-email').value = customer?.email || '';
        clearFormErrors();
        customerModal.show();
    }

    function clearFormErrors() {
        document.querySelectorAll('#customer-form .is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        document.querySelectorAll('#customer-form [data-error-for]').forEach((field) => { field.textContent = ''; });
        document.getElementById('customer-form-alert').classList.add('d-none');
    }

    function renderPagination(meta) {
        const pagination = document.getElementById('customers-pagination');
        pagination.replaceChildren();
        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);
        for (let number = 1; number <= last; number += 1) {
            const item = document.createElement('li'); item.className = `page-item${number === current ? ' active' : ''}`;
            const button = document.createElement('button'); button.className = 'page-link'; button.textContent = number; button.type = 'button';
            button.addEventListener('click', () => { page = number; load(); });
            item.append(button); pagination.append(item);
        }
    }

    search.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => { page = 1; load(); }, 400); });
    document.getElementById('add-customer-button').addEventListener('click', () => openCustomerForm());
    document.getElementById('customer-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        const button = document.getElementById('save-customer-button');
        window.setLoading(button, true);
        try {
            await window.api(editingCustomerId ? `/customers/${editingCustomerId}` : '/customers', {
                method: editingCustomerId ? 'PUT' : 'POST',
                body: Object.fromEntries(new FormData(event.currentTarget).entries()),
            });
            customerModal.hide();
            await load();
        } catch (requestError) {
            if (requestError.status === 422) {
                Object.entries(requestError.errors || {}).forEach(([field, messages]) => {
                    const input = document.querySelector(`#customer-form [name="${field}"]`);
                    const feedback = document.querySelector(`#customer-form [data-error-for="${field}"]`);
                    if (input) input.classList.add('is-invalid');
                    if (feedback) feedback.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
                });
            } else {
                const alert = document.getElementById('customer-form-alert');
                alert.textContent = requestError.message || 'Unable to save customer.';
                alert.classList.remove('d-none');
            }
        } finally {
            window.setLoading(button, false);
        }
    });
    load();
}());
