@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <span>{{ __('Enter Card Details') }}</span>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('merchant.subscription.demo.card.submit') }}">
                        @csrf

                        <div class="form-group row mb-3">
                            <label class="col-md-4 col-form-label text-md-right">Card Number</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="card_number" value="4242 4242 4242 4242" required>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-md-4 col-form-label text-md-right">Card Holder</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="card_holder" value="John Doe" required>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-md-4 col-form-label text-md-right">Expiry</label>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="expiry" value="12/25" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="cvc" value="123" required>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    Pay $29.00
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
