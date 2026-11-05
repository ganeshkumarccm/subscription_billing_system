@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<h1 class="h3 mb-4">Customers</h1>
<div id="customers-error" class="alert alert-danger d-none"></div>
<div id="customers-loading" class="placeholder-glow customers-loading"><div class="placeholder col-12"></div><div class="placeholder col-9"></div></div>
<div id="customers-content" class="d-none">
    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-indigo"><i class="bi bi-people"></i></span><span><small>Total customers</small><strong id="customer-count">—</strong></span></div></div>
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-green"><i class="bi bi-person-check"></i></span><span><small>Current page</small><strong id="customer-page-label">—</strong></span></div></div>
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-amber"><i class="bi bi-search"></i></span><span><small>Search</small><strong id="customer-search-label">All customers</strong></span></div></div>
    </div>
    <div class="card table-card">
        <div class="table-card-header customer-toolbar">
            <div><h2 class="table-card-title">Customer directory</h2><p class="table-card-subtitle">Search and manage your customers</p></div>
            <button id="add-customer-button" type="button" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add customer</button>
            <div class="search-box"><i class="bi bi-search"></i><input id="customer-search" type="search" placeholder="Search name or email" aria-label="Search customers"></div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Customer</th><th>Email address</th><th class="text-end">Action</th></tr></thead>
                <tbody id="customers-table"></tbody>
            </table>
        </div>
        <div class="table-card-footer">
            <span id="customer-results-label" class="text-muted small"></span>
            <nav aria-label="Customer pagination"><ul id="customers-pagination" class="pagination pagination-sm mb-0"></ul></nav>
        </div>
    </div>
</div>

<div class="modal fade" id="customer-modal" tabindex="-1" aria-labelledby="customer-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content plan-modal">
            <div class="modal-header">
                <div><h2 id="customer-modal-title" class="modal-title fs-5">Add customer</h2><p class="modal-subtitle mb-0">Add a customer to your merchant account</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customer-form" novalidate>
                <div class="modal-body">
                    <div id="customer-form-alert" class="alert alert-danger d-none"></div>
                    <div class="mb-3"><label class="form-label" for="customer-name">Customer name</label><input id="customer-name" name="name" class="form-control" required><div class="invalid-feedback" data-error-for="name"></div></div>
                    <div><label class="form-label" for="customer-email">Email address</label><input id="customer-email" name="email" type="email" class="form-control" required><div class="invalid-feedback" data-error-for="email"></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button id="save-customer-button" type="submit" class="btn btn-primary">Save customer</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/customers.js') }}"></script>
@endpush
