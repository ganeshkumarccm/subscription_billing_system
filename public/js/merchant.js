(function () {
    const loading = document.getElementById('merchant-loading');
    const content = document.getElementById('merchant-content');
    const error = document.getElementById('merchant-error');
    const regenerate = document.getElementById('regenerate-key');
    const modal = new bootstrap.Modal(document.getElementById('api-key-modal'));
    const confirmModal = new bootstrap.Modal(document.getElementById('regenerate-confirm-modal'));

    function showError(message) {
        error.textContent = message;
        error.classList.remove('d-none');
    }

    window.api('/merchant').then((response) => {
        const merchant = response.data || {};
        document.getElementById('merchant-title').textContent = merchant.name || 'Merchant';
        document.getElementById('masked-api-key').textContent = merchant.masked_api_key || 'No active key';
        const counts = document.getElementById('merchant-counts');
        Object.entries(merchant.counts || {}).forEach(([name, value]) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'col-6 col-md-3';
            const card = document.createElement('div');
            card.className = 'border rounded p-3';
            const label = document.createElement('div');
            label.className = 'small text-muted text-capitalize';
            label.textContent = name;
            const number = document.createElement('strong');
            number.className = 'fs-4';
            number.textContent = value;
            card.append(label, number);
            wrapper.append(card);
            counts.append(wrapper);
        });
        loading.classList.add('d-none');
        content.classList.remove('d-none');
    }).catch((requestError) => {
        loading.classList.add('d-none');
        showError(requestError.message || 'Unable to load merchant details.');
    });

    regenerate.addEventListener('click', () => confirmModal.show());
    document.getElementById('confirm-regenerate-key').addEventListener('click', async () => {
        confirmModal.hide();
        window.setLoading(regenerate, true);
        try {
            const response = await window.api('/merchant/api-key/regenerate', { method: 'POST' });
            document.getElementById('new-api-key').value = response.api_key || '';
            document.getElementById('copy-status').textContent = '';
            modal.show();
        } catch (requestError) {
            showError(requestError.message || 'Unable to regenerate API key.');
        } finally {
            window.setLoading(regenerate, false);
        }
    });

    document.getElementById('copy-api-key').addEventListener('click', async () => {
        await navigator.clipboard.writeText(document.getElementById('new-api-key').value);
        document.getElementById('copy-status').textContent = 'Copied.';
    });
}());
