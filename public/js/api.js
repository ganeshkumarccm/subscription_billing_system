(function (window) {
    'use strict';

    const API_BASE = '/api/v1';
    const TOKEN_KEY = 'jwt';

    const tokenStore = {
        get() {
            return window.localStorage.getItem(TOKEN_KEY);
        },

        set(token) {
            window.localStorage.setItem(TOKEN_KEY, token);
        },

        clear() {
            window.localStorage.removeItem(TOKEN_KEY);
        },
    };

    class ApiError extends Error {
        constructor(message, status, details = null) {
            super(message);
            this.name = 'ApiError';
            this.status = status;
            this.errors = details;
        }
    }

    function requestUrl(path) {
        return path.startsWith('http') ? path : `${API_BASE}${path}`;
    }

    function requestOptions(options = {}) {
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        };
        const token = tokenStore.get();

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const request = {
            method: options.method || 'GET',
            headers,
        };

        if (options.body !== undefined) {
            request.body = typeof options.body === 'string'
                ? options.body
                : JSON.stringify(options.body);
        }

        return request;
    }

    async function responseData(response) {
        if (response.status === 204) {
            return null;
        }

        const contentType = response.headers.get('content-type') || '';
        return contentType.includes('application/json')
            ? response.json()
            : response.text();
    }

    async function refreshToken() {
        const response = await fetch(requestUrl('/auth/refresh'), requestOptions({
            method: 'POST',
        }));

        if (!response.ok) {
            throw new ApiError('Token refresh failed.', response.status);
        }

        const data = await responseData(response);
        if (!data || !data.access_token) {
            throw new ApiError('Token refresh returned no token.', response.status);
        }

        tokenStore.set(data.access_token);
        return data.access_token;
    }

    async function api(path, options = {}, canRefresh = true) {
        const response = await fetch(requestUrl(path), requestOptions(options));

        if (response.status === 401 && canRefresh && path !== '/auth/refresh') {
            let refreshed = false;
            try {
                await refreshToken();
                refreshed = true;
                return api(path, options, false);
            } catch (error) {
                if (!refreshed || error.status === 401) {
                    tokenStore.clear();
                    window.location.assign('/login?expired=1');
                }
                throw error;
            }
        }

        const data = await responseData(response);
        if (response.ok) {
            return data;
        }

        if (response.status === 422) {
            throw new ApiError(
                data && data.message ? data.message : 'Please correct the highlighted fields.',
                response.status,
                data && data.errors ? data.errors : data,
            );
        }

        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After');
            const error = new ApiError(
                data && data.message ? data.message : 'Too many requests.',
                response.status,
                data,
            );
            error.retryAfter = retryAfter;
            throw error;
        }

        throw new ApiError(
            data && data.message ? data.message : 'The request could not be completed.',
            response.status,
            data,
        );
    }

    function showToast(message, type = 'danger') {
        const container = document.getElementById('toast-container')
            || document.querySelector('.toast-container');

        if (!container) {
            window.alert(message);
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        toast.querySelector('.toast-body').textContent = message;
        container.appendChild(toast);

        if (window.bootstrap && window.bootstrap.Toast) {
            const instance = window.bootstrap.Toast.getOrCreateInstance(toast);
            toast.addEventListener('hidden.bs.toast', () => toast.remove(), { once: true });
            instance.show();
        } else {
            toast.classList.add('show');
            window.setTimeout(() => toast.remove(), 5000);
        }
    }

    function formatINR(paise) {
        const value = Number(paise);
        if (!Number.isSafeInteger(value)) {
            throw new TypeError('formatINR expects an integer paise amount.');
        }

        const negative = value < 0;
        const absolute = Math.abs(value);
        const rupees = Math.floor(absolute / 100);
        const cents = String(absolute % 100).padStart(2, '0');
        const formattedRupees = new Intl.NumberFormat('en-IN').format(rupees);

        return `${negative ? '-' : ''}₹${formattedRupees}.${cents}`;
    }

    function setLoading(button, loading) {
        if (!button) {
            return;
        }

        if (loading) {
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Loading...';
        } else {
            if (button.dataset.originalText !== undefined) {
                button.textContent = button.dataset.originalText;
                delete button.dataset.originalText;
            }
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }

    function requireAuth() {
        const token = tokenStore.get();
        if (!token) {
            window.location.assign('/login');
            return null;
        }

        return token;
    }

    window.API_BASE = API_BASE;
    window.ApiError = ApiError;
    window.tokenStore = tokenStore;
    window.api = api;
    window.showToast = showToast;
    window.formatINR = formatINR;
    window.setLoading = setLoading;
    window.requireAuth = requireAuth;
}(window));
