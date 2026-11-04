@extends('layouts.app')

@section('title', 'Plans')

@section('content')
<h1 class="h3 mb-4">Plans</h1>
<div id="plans-error" class="alert alert-danger d-none"></div>
<div id="plans-loading" class="placeholder-glow plans-loading">
    <div class="placeholder col-12"></div><div class="placeholder col-10"></div>
</div>
<div id="plans-content" class="d-none">
    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-indigo"><i class="bi bi-layers"></i></span><span><small>Total plans</small><strong id="plan-count">—</strong></span></div></div>
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-green"><i class="bi bi-check-circle"></i></span><span><small>Active plans</small><strong id="active-plan-count">—</strong></span></div></div>
        <div class="col-sm-4"><div class="mini-stat"><span class="mini-stat-icon bg-amber"><i class="bi bi-lightning-charge"></i></span><span><small>Billing model</small><strong>Monthly / Yearly</strong></span></div></div>
    </div>
    <div class="card table-card">
        <div class="table-card-header">
            <div><h2 class="table-card-title">Plan catalog</h2><p class="table-card-subtitle">Manage pricing and usage allowances</p></div>
            <span class="table-card-count" id="plan-count-label">0 plans</span>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Plan</th><th>Base price</th><th>Cycle</th><th>Included units</th><th>Overage / unit</th><th>Status</th></tr></thead>
                <tbody id="plans-table"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/plans.js') }}"></script>
@endpush
