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
            <button id="add-plan-button" type="button" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add plan</button>
            <span class="table-card-count" id="plan-count-label">0 plans</span>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Plan</th><th>Base price</th><th>Cycle</th><th>Included units</th><th>Overage / unit</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody id="plans-table"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="plan-modal" tabindex="-1" aria-labelledby="plan-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content plan-modal">
            <div class="modal-header">
                <div><h2 id="plan-modal-title" class="modal-title fs-5">Add plan</h2><p class="modal-subtitle mb-0">Configure pricing and usage limits</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="plan-form" novalidate>
                <div class="modal-body">
                    <div id="plan-form-alert" class="alert alert-danger d-none"></div>
                    <div class="mb-3"><label class="form-label" for="plan-name">Plan name</label><input id="plan-name" name="name" class="form-control" required><div class="invalid-feedback" data-error-for="name"></div></div>
                    <div class="row g-3">
                        <div class="col-sm-6"><label class="form-label" for="plan-price">Base price (paise)</label><input id="plan-price" name="base_price_paise" type="number" min="0" class="form-control" required><div class="invalid-feedback" data-error-for="base_price_paise"></div></div>
                        <div class="col-sm-6"><label class="form-label" for="plan-cycle">Billing cycle</label><select id="plan-cycle" name="billing_cycle" class="form-select" required><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select><div class="invalid-feedback" data-error-for="billing_cycle"></div></div>
                        <div class="col-sm-6"><label class="form-label" for="plan-included">Included units</label><input id="plan-included" name="included_units" type="number" min="0" class="form-control" required><div class="invalid-feedback" data-error-for="included_units"></div></div>
                        <div class="col-sm-6"><label class="form-label" for="plan-overage">Overage (paise/unit)</label><input id="plan-overage" name="overage_rate_paise_per_unit" type="number" min="0" class="form-control" required><div class="invalid-feedback" data-error-for="overage_rate_paise_per_unit"></div></div>
                    </div>
                    <div class="form-check mt-3"><input id="plan-active" name="is_active" type="checkbox" class="form-check-input" value="1" checked><label class="form-check-label" for="plan-active">Plan is active</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button id="save-plan-button" type="submit" class="btn btn-primary">Save plan</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/plans.js') }}"></script>
@endpush
