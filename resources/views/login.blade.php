@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
<div class="auth-card">
    <a href="/" class="auth-logo" aria-label="Subscription Billing home">S</a>
    <h1 class="auth-title">Log in</h1>

    <div id="auth-alert" class="alert d-none" role="alert"></div>
    <div id="expired-alert" class="alert alert-warning d-none" role="alert">
        Your session expired. Please log in again.
    </div>

    <form id="login-form" novalidate>
        <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required>
            <div class="invalid-feedback" data-error-for="email"></div>
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <div class="invalid-feedback" data-error-for="password"></div>
        </div>

        <button type="submit" class="auth-submit">Log in</button>
    </form>

    <p class="auth-register">Don't have an account? <a href="{{ route('register') }}">Register</a></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth.js') }}"></script>
<script>initAuthForm('login');</script>
@endpush
