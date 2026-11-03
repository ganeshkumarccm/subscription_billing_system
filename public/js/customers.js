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
            (response.data || []).forEach((customer) => {
                const row = document.createElement('tr');
                const link = document.createElement('a');
                link.href = `/customers/${encodeURIComponent(customer.id)}`;
                link.textContent = customer.name || `Customer #${customer.id}`;
                const name = document.createElement('td'); name.append(link);
                row.append(name, cell(customer.email));
                const action = document.createElement('td');
                const view = document.createElement('a'); view.href = link.href; view.textContent = 'View'; view.className = 'btn btn-sm btn-outline-primary';
                action.append(view); row.append(action); table.append(row);
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
