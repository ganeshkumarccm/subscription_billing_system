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
            <div class="search-box"><i class="bi bi-search"></i><input id="customer-search" type="search" placeholder="Search name or email" aria-label="Search customers"></div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Customer</th><th>Email address</th><th>Customer ID</th><th class="text-end">Action</th></tr></thead>
                <tbody id="customers-table"></tbody>
            </table>
        </div>
        <div class="table-card-footer">
            <span id="customer-results-label" class="text-muted small"></span>
            <nav aria-label="Customer pagination"><ul id="customers-pagination" class="pagination pagination-sm mb-0"></ul></nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/customers.js') }}"></script>
@endpush
