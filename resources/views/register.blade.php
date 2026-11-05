@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
<div class="auth-card auth-card-register">
    <a href="/" class="auth-logo" aria-label="Subscription Billing home">S</a>
    <h1 class="auth-title">Create account</h1>

    <div id="auth-alert" class="alert d-none" role="alert"></div>

    <form id="register-form" novalidate>
        <div class="auth-field">
            <label for="merchant_name">Merchant name</label>
            <input id="merchant_name" name="merchant_name" type="text"
                autocomplete="organization" required>
            <div class="invalid-feedback" data-error-for="merchant_name"></div>
        </div>
        <div class="auth-field">
            <label for="name">Your name</label>
            <input id="name" name="name" type="text" autocomplete="name" required>
            <div class="invalid-feedback" data-error-for="name"></div>
        </div>
        <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required>
            <div class="invalid-feedback" data-error-for="email"></div>
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password"
                autocomplete="new-password" required>
            <div class="invalid-feedback" data-error-for="password"></div>
        </div>
        <div class="auth-field auth-field-last">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                autocomplete="new-password" required>
            <div class="invalid-feedback" data-error-for="password_confirmation"></div>
        </div>
        <button type="submit" class="auth-submit">Create account</button>
    </form>

    <p class="auth-register">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth.js') }}"></script>
<script>initAuthForm('register');</script>
@endpush
