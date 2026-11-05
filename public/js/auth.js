(function (window) {
    'use strict';

    function showAlert(message, type) {
        const alert = document.getElementById('auth-alert');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.classList.remove('d-none');
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        form.querySelectorAll('[data-error-for]').forEach((element) => {
            element.textContent = '';
        });
    }

    function showValidationErrors(form, errors) {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = form.elements[field];
            const feedback = form.querySelector(`[data-error-for="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
            }
            if (feedback) {
                feedback.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
            }
        });
    }

    function retryMessage(retryAfter) {
        const seconds = Number.parseInt(retryAfter, 10);
        return Number.isFinite(seconds)
            ? `Too many attempts. Please retry in ${seconds} seconds.`
            : 'Too many attempts. Please try again later.';
    }

    window.initAuthForm = function (type) {
        const form = document.getElementById(`${type}-form`);
        if (!form) {
            return;
        }

        if (window.tokenStore.get()) {
            window.location.assign('/dashboard');
            return;
        }

        if (new URLSearchParams(window.location.search).get('expired') === '1') {
            const expiredAlert = document.getElementById('expired-alert');
            if (expiredAlert) {
                expiredAlert.classList.remove('d-none');
            }
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(form);
            document.getElementById('auth-alert').classList.add('d-none');

            const button = form.querySelector('button[type="submit"]');
            const body = Object.fromEntries(new FormData(form).entries());
            window.setLoading(button, true);

            try {
                const endpoint = type === 'login' ? '/auth/login' : '/auth/register';
                const response = await window.api(endpoint, { method: 'POST', body });
                window.tokenStore.set(response.access_token);
                window.location.assign('/dashboard');
            } catch (error) {
                if (error.status === 422) {
                    showValidationErrors(form, error.errors);
                } else if (error.status === 401) {
                    showAlert('The email or password is incorrect.', 'danger');
                } else if (error.status === 429) {
                    showAlert(retryMessage(error.retryAfter), 'warning');
                } else {
                    showAlert(error.message || 'Something went wrong. Please try again.', 'danger');
                }
            } finally {
                window.setLoading(button, false);
            }
        });
    };
}(window));
