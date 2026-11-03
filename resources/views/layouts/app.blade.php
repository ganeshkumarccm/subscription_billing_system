<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Subscription Billing')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="admin-body">
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>
    <aside id="admin-sidebar" class="admin-sidebar">
        <div class="sidebar-brand">
            <a href="/dashboard" class="brand-link">
                <span class="brand-mark">S</span>
                <span>
                    <strong>Subscript</strong>
                    <small>Billing Console</small>
                </span>
            </a>
        </div>
        <div class="sidebar-section-label">Workspace</div>
        <nav class="sidebar-nav" aria-label="Primary navigation">
            <a class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
                <span class="sidebar-icon">▦</span><span>Dashboard</span>
            </a>
            <a class="sidebar-link {{ request()->is('merchant') ? 'active' : '' }}" href="/merchant">
                <span class="sidebar-icon">◈</span><span>Merchant</span>
            </a>
            <a class="sidebar-link {{ request()->is('plans') ? 'active' : '' }}" href="/plans">
                <span class="sidebar-icon">◇</span><span>Plans</span>
            </a>
            <a class="sidebar-link {{ request()->is('customers*') ? 'active' : '' }}" href="/customers">
                <span class="sidebar-icon">◎</span><span>Customers</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="account-card">
                <span class="account-avatar">M</span>
                <span class="account-copy">
                    <strong id="sidebar-merchant-name">Loading...</strong>
                    <small>Administrator</small>
                </span>
            </div>
            <button id="logout-button" type="button" class="sidebar-logout">
                <span class="sidebar-icon">↪</span><span>Log out</span>
            </button>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-header">
            <div class="container-fluid d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button id="sidebar-toggle" class="sidebar-toggle" type="button" aria-label="Open navigation">
                        <span></span><span></span><span></span>
                    </button>
                    <div>
                        <div class="header-eyebrow">Admin workspace</div>
                        <h1 class="header-title">@yield('title', 'Dashboard')</h1>
                    </div>
                </div>
                <div class="header-status"><span class="status-dot"></span> System operational</div>
            </div>
        </header>

        <main class="admin-content container-fluid">
            @yield('content')
        </main>
    </div>

    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/api.js') }}"></script>
    <script>
        (async function () {
            if (!window.requireAuth()) {
                return;
            }

            const logoutButton = document.getElementById('logout-button');

            try {
                const response = await window.api('/auth/me');
                const merchant = response.data && response.data.merchant;
                const name = merchant && merchant.name
                    ? merchant.name
                    : (response.data && response.data.name ? response.data.name : 'Merchant');
                document.getElementById('sidebar-merchant-name').textContent = name;
                document.querySelector('.account-avatar').textContent = name.charAt(0).toUpperCase();
            } catch (error) {
                document.getElementById('sidebar-merchant-name').textContent = 'Merchant';
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

        document.getElementById('sidebar-toggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
        document.getElementById('sidebar-backdrop').addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    </script>
    @stack('scripts')
</body>
</html>
