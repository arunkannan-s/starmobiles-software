@extends('backend.backend_layout')
@section('page-title', __('Transfer Report'))
@push('page-css')
@endpush
@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex flex-column justify-content-center">
            <h4 class="mb-0">{{ __('Transfer Report') }}</h4>
        </div>
        @include('backend.components.breadcrumb', [
            'breadcrumbs' => [
                ['label' => '<i class="ti tabler-home"></i>', 'link' => route('report.index')],
                ['label' => __('Report'), 'link' => route('report.index')],
                ['label' => __('Transfer Report'), 'active' => true]
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
                        <label class="form-label">{{ __('Outlet') }}</label>
                        <select class="form-select select2" id="outlet_id" name="outlet_id">
                            <option value="">{{ __('All Outlets') }}</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('Shows transfers where this outlet is either the source or destination.') }}</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Payment Status') }}</label>
                        <select class="form-select select2" id="payment_status" name="payment_status">
                            <option value="">{{ __('All') }}</option>
                            <option value="Paid" {{ request('payment_status') == 'Paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                            <option value="Unpaid" {{ request('payment_status') == 'Unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
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
                <div class="card-body filtered_information" id="filteredInformation">
                    <h5 class="card-title">{{ __('Transfer Report') }}</h5>
                    <div id="filterInfoContent"></div>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic table report-table" id="transferReportTable">
                        <thead>
                            <tr>
                                <th>{{ __('SN') }}</th>
                                <th>{{ __('Reference No') }}</th>
                                <th>{{ __('Date Time') }}</th>
                                <th>{{ __('From Outlet') }}</th>
                                <th>{{ __('To Outlet') }}</th>
                                <th>{{ __('Direction') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Items') }}</th>
                                <th>{{ __('Total Qty') }}</th>
                                <th>{{ __('Total Amount') }}</th>
                                <th>{{ __('Payment Status') }}</th>
                                <th>{{ __('Created By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-end">{{ __('Total') }}:</th>
                                <th id="totalQty">0</th>
                                <th id="totalAmount">0.00</th>
                                <th></th>
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
<script src="{{ asset('public/backend_assets/js/report/transfer_report.js') }}?v={{ @filemtime(public_path('backend_assets/js/report/transfer_report.js')) ?: time() }}"></script>
@endpush
