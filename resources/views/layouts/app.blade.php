<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Subscription Billing')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">Subscription Billing</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#app-navbar"
                aria-controls="app-navbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="app-navbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/merchant">Merchant</a></li>
                    <li class="nav-item"><a class="nav-link" href="/plans">Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="/customers">Customers</a></li>
                </ul>
                <span id="merchant-name" class="navbar-text me-lg-3">Loading...</span>
                <button id="logout-button" type="button" class="btn btn-outline-light btn-sm">Logout</button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @yield('content')
    </main>

    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/api.js') }}"></script>
    <script>
        (async function () {
            if (!window.requireAuth()) {
                return;
            }

            const merchantName = document.getElementById('merchant-name');
            const logoutButton = document.getElementById('logout-button');

            try {
                const response = await window.api('/auth/me');
                const merchant = response.data && response.data.merchant;
                merchantName.textContent = merchant && merchant.name
                    ? merchant.name
                    : (response.data && response.data.name ? response.data.name : 'Merchant');
            } catch (error) {
                merchantName.textContent = 'Merchant';
            }

            logoutButton.addEventListener('click', async function () {
                window.setLoading(logoutButton, true);
                try {
                    await window.api('/auth/logout', { method: 'POST' });
                } catch (error) {
                    if (error.status !== 401) {
                        window.showToast('Could not log out cleanly.', 'warning');
                    }
                } finally {
                    window.tokenStore.clear();
                    window.location.assign('/login');
                }
            });
        }());
    </script>
    @stack('scripts')
</body>
</html>
