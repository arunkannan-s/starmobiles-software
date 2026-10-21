@extends('backend.backend_layout')
@section('page-title', __('Outlet Transfer Ledger Report'))
@push('page-css')
<link rel="stylesheet" href="{{ asset('public/backend_assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endpush
@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex flex-column justify-content-center">
            <h4 class="mb-0">{{ __('Outlet Transfer Ledger Report') }}</h4>
        </div>
        @include('backend.components.breadcrumb', [
            'breadcrumbs' => [
                ['label' => '<i class="ti tabler-home"></i>', 'link' => route('report.index')],
                ['label' => __('Report'), 'link' => route('report.index')],
                ['label' => __('Outlet Transfer Ledger Report'), 'active' => true]
            ]
        ])
    </div>

    @if(session('success'))
        {!! insertSuccess(session('success')) !!}
    @endif
    @if(session('error'))
        {!! insertFailed(session('error')) !!}
    @endif

    <!-- Filter Section -->
    <div class="card mb-4" id="filterSection" style="display: none;">
        <div class="card-body">
            <form id="filterForm" onsubmit="event.preventDefault(); return false;" action="javascript:void(0);">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Date From') }}</label>
                        <input type="text" class="form-control datePicker" id="date_from" name="date_from" value="{{ request('date_from') }}" placeholder="{{ __('Date From') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Date To') }}</label>
                        <input type="text" class="form-control datePicker" id="date_to" name="date_to" value="{{ request('date_to') }}" placeholder="{{ __('Date To') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Outlet') }} {!! requiredField() !!}</label>
                        <select class="form-select select2" id="outlet_id" name="outlet_id">
                            <option value="">{{ __('Select Outlet') }}</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('Pick any outlet. Debit = it owes more, Credit = it owes less or is owed. Closing balance: positive = it owes, negative = it is owed.') }}</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select class="form-select select2" id="type" name="type">
                            <option value="All" {{ request('type', 'All') == 'All' ? 'selected' : '' }}>{{ __('All') }}</option>
                            <option value="Debit" {{ request('type') == 'Debit' ? 'selected' : '' }}>{{ __('Debit') }}</option>
                            <option value="Credit" {{ request('type') == 'Credit' ? 'selected' : '' }}>{{ __('Credit') }}</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="button" class="btn btn-primary me-2" id="applyFilter">
                            <i class="ti tabler-filter me-1"></i>{{ __('Apply Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body filtered_information" id="filteredInformation" style="display:none;">
                    <h5 class="card-title">{{ __('Outlet Transfer Ledger Report') }}</h5>
                    <div id="filterInfoContent"></div>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic table report-table" id="outletTransferLedgerReportTable">
                        <thead>
                            <tr>
                                <th>{{ __('SN') }}</th>
                                <th>{{ __('Date Time') }}</th>
                                <th>{{ __('Transaction Type') }}</th>
                                <th>{{ __('Transaction No') }}</th>
                                <th>{{ __('Debit') }}</th>
                                <th>{{ __('Credit') }}</th>
                                <th>{{ __('Outlet') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">{{ __('Total') }}:</th>
                                <th id="totalDebit">0.00</th>
                                <th id="totalCredit">0.00</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">{{ __('Opening Balance') }}:</th>
                                <th colspan="2" id="openingBalance">0.00</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">{{ __('Closing Balance') }}:</th>
                                <th colspan="2" id="closingBalance">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('page-js')
@routes
<script src="{{ asset('public/backend_assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>
<script src="{{ asset('public/backend_assets/js/report/outlet_transfer_ledger_report.js') }}?v={{ @filemtime(public_path('backend_assets/js/report/outlet_transfer_ledger_report.js')) ?: time() }}"></script>
@endpush
