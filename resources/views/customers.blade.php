@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<h1 class="h3 mb-4">Customers</h1>
<div id="customers-error" class="alert alert-danger d-none"></div>
<div class="mb-3">
    <label for="customer-search" class="form-label">Search customers</label>
    <input id="customer-search" class="form-control" type="search" placeholder="Name or email">
</div>
<div id="customers-loading" class="placeholder-glow"><p class="placeholder col-10"></p><p class="placeholder col-8"></p></div>
<div id="customers-content" class="card shadow-sm d-none">
    <div class="card-body table-responsive">
        <table class="table align-middle mb-3">
            <thead><tr><th>Name</th><th>Email</th><th></th></tr></thead>
            <tbody id="customers-table"></tbody>
        </table>
        <nav aria-label="Customer pagination"><ul id="customers-pagination" class="pagination mb-0"></ul></nav>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/customers.js') }}"></script>
@endpush
