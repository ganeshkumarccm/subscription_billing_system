@extends('layouts.app')

@section('title', 'Plans')

@section('content')
<h1 class="h3 mb-4">Plans</h1>
<div id="plans-error" class="alert alert-danger d-none"></div>
<div id="plans-loading" class="placeholder-glow"><p class="placeholder col-10"></p><p class="placeholder col-8"></p></div>
<div id="plans-content" class="card shadow-sm d-none">
    <div class="card-body table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Base price</th><th>Cycle</th><th>Included units</th><th>Overage/unit</th><th>Status</th></tr></thead>
            <tbody id="plans-table"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/plans.js') }}"></script>
@endpush
