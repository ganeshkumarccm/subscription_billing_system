<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Subscription Billing')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="text-center mb-4">
                    <a href="/" class="text-decoration-none">
                        <h1 class="h3 text-dark">Subscription Billing</h1>
                    </a>
                </div>
                @yield('content')
            </div>
        </div>
    </main>

    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/api.js') }}"></script>
    @stack('scripts')
</body>
</html>
