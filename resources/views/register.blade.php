@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h4 mb-4">Create your merchant account</h2>

        <div id="auth-alert" class="alert d-none" role="alert"></div>

        <form id="register-form" novalidate>
            <div class="mb-3">
                <label for="merchant_name" class="form-label">Merchant name</label>
                <input id="merchant_name" name="merchant_name" type="text" class="form-control"
                    autocomplete="organization" required>
                <div class="invalid-feedback" data-error-for="merchant_name"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Your name</label>
                <input id="name" name="name" type="text" class="form-control" autocomplete="name" required>
                <div class="invalid-feedback" data-error-for="name"></div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" class="form-control" autocomplete="email" required>
                <div class="invalid-feedback" data-error-for="email"></div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" class="form-control"
                    autocomplete="new-password" required>
                <div class="invalid-feedback" data-error-for="password"></div>
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    class="form-control" autocomplete="new-password" required>
                <div class="invalid-feedback" data-error-for="password_confirmation"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create account</button>
        </form>

        <p class="text-center text-muted mt-4 mb-0">
            Already registered? <a href="{{ route('login') }}">Log in</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth.js') }}"></script>
<script>initAuthForm('register');</script>
@endpush
