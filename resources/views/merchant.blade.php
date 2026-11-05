@extends('layouts.app')

@section('title', 'Merchant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Merchant</h1>
    <button id="regenerate-key" class="btn btn-primary" type="button">Regenerate API key</button>
</div>
<div id="merchant-error" class="alert alert-danger d-none"></div>
<div id="merchant-loading" class="placeholder-glow">
    <p class="placeholder col-6"></p><p class="placeholder col-4"></p><p class="placeholder col-8"></p>
</div>
<div id="merchant-content" class="card shadow-sm d-none">
    <div class="card-body">
        <h2 id="merchant-title" class="h4"></h2>
        <p>Masked API key: <code id="masked-api-key"></code></p>
        <h3 class="h6">Counts</h3>
        <div id="merchant-counts" class="row g-3"></div>
    </div>
</div>

<div class="modal fade" id="api-key-modal" tabindex="-1" aria-labelledby="api-key-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="api-key-modal-title" class="modal-title fs-5">New API key</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-warning">Copy this key now. It will not be shown again.</p>
                <div class="input-group">
                    <input id="new-api-key" class="form-control" readonly>
                    <button id="copy-api-key" class="btn btn-outline-secondary" type="button">Copy</button>
                </div>
                <div id="copy-status" class="small text-success mt-2"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="regenerate-confirm-modal" tabindex="-1" aria-labelledby="regenerate-confirm-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="regenerate-confirm-title" class="modal-title fs-5">Regenerate API key?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">The current API key will stop working immediately.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="confirm-regenerate-key" type="button" class="btn btn-danger">Regenerate</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/merchant.js') }}"></script>
@endpush
