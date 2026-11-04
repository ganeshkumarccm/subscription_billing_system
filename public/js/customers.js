(function () {
    let page = 1;
    let timer;
    const search = document.getElementById('customer-search');
    const loading = document.getElementById('customers-loading');
    const content = document.getElementById('customers-content');
    const error = document.getElementById('customers-error');
    const cell = (value) => { const element = document.createElement('td'); element.textContent = value ?? '—'; return element; };

    function load() {
        loading.classList.remove('d-none'); content.classList.add('d-none'); error.classList.add('d-none');
        const params = new URLSearchParams({ page, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        window.api(`/customers?${params}`).then((response) => {
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
                const id = cell(`#${customer.id}`);
                id.className = 'text-muted small';
                const action = document.createElement('td');
                action.className = 'text-end';
                const view = document.createElement('a');
                view.href = link.href;
                view.className = 'table-action';
                view.textContent = 'View ';
                const arrow = document.createElement('i');
                arrow.className = 'bi bi-arrow-up-right';
                view.append(arrow);
                action.append(view);
                row.append(name, cell(customer.email), id, action);
                table.append(row);
            });
            renderPagination(response.meta || {});
            loading.classList.add('d-none'); content.classList.remove('d-none');
        }).catch((requestError) => {
            loading.classList.add('d-none'); error.textContent = requestError.message || 'Unable to load customers.'; error.classList.remove('d-none');
        });
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
    load();
}());
