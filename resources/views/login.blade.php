@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h4 mb-4">Log in</h2>

        <div id="auth-alert" class="alert d-none" role="alert"></div>
        <div id="expired-alert" class="alert alert-warning d-none" role="alert">
            Your session expired. Please log in again.
        </div>

        <form id="login-form" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" class="form-control" autocomplete="email" required>
                <div class="invalid-feedback" data-error-for="email"></div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" class="form-control"
                    autocomplete="current-password" required>
                <div class="invalid-feedback" data-error-for="password"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log in</button>
        </form>

        <p class="text-center text-muted mt-4 mb-0">
            New merchant? <a href="{{ route('register') }}">Create an account</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth.js') }}"></script>
<script>initAuthForm('login');</script>
@endpush
