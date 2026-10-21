@extends('backend.backend_layout')
@section('page-title', isset($transfer_payment) ? __('Update') . ' ' . __('Transfer Payment') : __('Add') . ' ' . __('Transfer Payment') )
@push('page-css')
@endpush
@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex flex-column justify-content-center">
            <h4 class="mb-0">{{ isset($transfer_payment) ? __('Update') . ' ' . __('Transfer Payment') : __('Add') . ' ' . __('Transfer Payment') }}</h4>
        </div>
        @include('backend.components.breadcrumb', [
            'breadcrumbs' => [
                ['label' => '<i class="ti tabler-home"></i>', 'link' => '#'],
                ['label' => __('Transfer'), 'link' => route('transfer.index')],
                ['label' => isset($transfer_payment) ? __('Update') . ' ' . __('Transfer_Payment') : __('Add') . ' ' . __('Transfer_Payment'), 'active' => true]
            ]
        ])
    </div>

    @if(session('success'))
        {!! insertSuccess(session('success')) !!}
    @endif
    @if(session('error'))
        {!! insertFailed(session('error')) !!}
    @endif

    <div class="row">
        <div class="col-12">
            <form action="{{ isset($transfer_payment) ? route('transfer-payment.update', encrypt($transfer_payment->id)) : route('transfer-payment.store') }}" method="POST">
                @csrf
                @if(isset($transfer_payment))
                    @method('PUT')
                @endif
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="reference_no">{{ __('Reference No') }} {!! requiredField() !!}</label>
                                    <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                                        placeholder="{{ __('Reference No') }}" name="reference_no" id="reference_no"
                                        value="{{ old('reference_no', isset($transfer_payment) ? $transfer_payment->reference_no : (isset($reference_no) ? $reference_no : '')) }}" />
                                    @error('reference_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="date">{{ __('Date') }} {!! requiredField() !!}</label>
                                    <input type="text" class="form-control datePicker @error('date') is-invalid @enderror"
                                        placeholder="{{ __('Date') }}" name="date" id="date"
                                        value="{{ old('date', isset($transfer_payment) ? $transfer_payment->date : date('Y-m-d')) }}" />
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="payment_method_id">{{ __('Payment Method') }} {!! requiredField() !!}</label>
                                    <select class="form-select select2 @error('payment_method_id') is-invalid @enderror"
                                        name="payment_method_id" id="payment_method_id" data-placeholder="{{ __('Select') }} {{ __('Payment Method') }}">
                                        <option value="">{{ __('Select') }} {{ __('Payment Method') }}</option>
                                        @foreach($payment_methods as $method)
                                            <option value="{{ $method->id }}"
                                                {{ old('payment_method_id', $transfer_payment->payment_method_id ?? '') == $method->id ? 'selected' : '' }}>
                                                {{ $method->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_method_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="from_outlet_id">{{ __('From Outlet') }} {!! requiredField() !!}</label>
                                    <small class="text-muted d-block mb-1">{{ __('The outlet that hands over the money (e.g. Head Branch giving cash to a branch, or a branch repaying Head Branch).') }}</small>
                                    <select class="form-select select2 @error('from_outlet_id') is-invalid @enderror"
                                        name="from_outlet_id" id="from_outlet_id" data-placeholder="{{ __('Select') }} {{ __('Outlet') }}">
                                        <option value="">{{ __('Select') }} {{ __('Outlet') }}</option>
                                        @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}"
                                                {{ old('from_outlet_id', $transfer_payment->from_outlet_id ?? request('from_outlet_id', '')) == $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('from_outlet_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="to_outlet_id">{{ __('To Outlet') }} {!! requiredField() !!}</label>
                                    <small class="text-muted d-block mb-1">{{ __('The outlet that receives the money.') }}</small>
                                    <select class="form-select select2 @error('to_outlet_id') is-invalid @enderror"
                                        name="to_outlet_id" id="to_outlet_id" data-placeholder="{{ __('Select') }} {{ __('Outlet') }}">
                                        <option value="">{{ __('Select') }} {{ __('Outlet') }}</option>
                                        @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}"
                                                {{ old('to_outlet_id', $transfer_payment->to_outlet_id ?? request('to_outlet_id', '')) == $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('to_outlet_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div id="outlet_balance_display" class="mt-2" style="display: none;">
                                        <small class="text-muted">
                                            <strong>{{ __('Current Balance') }}:</strong>
                                            <span id="outlet_balance_amount" class="fw-bold"></span>
                                            <span id="outlet_balance_type" class="badge ms-1"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="amount">{{ __('Amount') }} {!! requiredField() !!}</label>
                                    <input type="text" class="form-control number-input @error('amount') is-invalid @enderror"
                                        placeholder="{{ __('Amount') }}" name="amount" id="amount"
                                        value="{{ old('amount', isset($transfer_payment) ? $transfer_payment->amount : '') }}" />
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="mb-5">
                                    <label class="form-label" for="note">{{ __('Note') }}</label>
                                    <textarea type="text" class="form-control @error('note') is-invalid @enderror"
                                        placeholder="{{ __('Note') }}" name="note" id="note"
                                        >{{ old('note', isset($transfer_payment) ? $transfer_payment->note : '') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary waves-effect waves-light me-4">
                                {!! submitIconWithText(isset($transfer_payment) ? $transfer_payment : '') !!}
                            </button>
                            <a href="{{ route('transfer-payment.index') }}" class="btn btn-primary waves-effect waves-light">
                                {!! backIconWithText() !!}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('page-js')
@routes
<script src="{{ asset('public/backend_assets/js/pages_js/add_transfer_payment.js') }}"></script>
@endpush
