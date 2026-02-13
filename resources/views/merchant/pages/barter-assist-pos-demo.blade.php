@extends('merchant.layouts.main')

@section('title', __('Communyti Barter Assist | Third-Party Value Intervention'))
@section('content-header', __('Communyti Barter Assist | Third-Party Value Intervention'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <!-- Barter Assist Data -->
                    <div id="barter-assist" data-merchant-id="MERCHANT_001" data-terminal-id="TERMINAL_A1" data-transaction-id="TXN_88421" data-amount-due="125.00" data-currency="USD" data-assist-enabled="true" data-assist-type="barter" data-assist-sources="church,nonprofit,sponsor,community-member" data-settlement-mode="pledge" data-release-condition="governed">
                    </div>

                    <!-- Point of Sale | Barter Assist Example -->
                    <div class="pox-checkout">
                        <h5 class="text-center mb-4">Amount Due: $125.00</h5>

                        <div class="form-group row">
                            <div class="col-md-12 text-center">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary">Pay Cash</button>
                                    <button type="button" class="btn btn-outline-secondary">Pay Card</button>
                                    <button type="button" class="btn btn-outline-success" onclick="toggleAssist()">Request Barter Assist</button>
                                </div>
                            </div>
                        </div>

                        <div id="assist-panel" style="display:none; margin-top:30px;">
                            <h5 class="text-center mb-3">Community Barter Assist</h5>

                            <div class="alert alert-info">
                                A church, nonprofit, sponsor, or approved community member may pledge value
                                toward this transaction.
                            </div>

                            <div class="form-group row">
                                <label for="assist-source" class="col-md-4 col-form-label text-md-right">{{ __('Assist Source') }}</label>
                                <div class="col-md-6">
                                    <select id="assist-source" class="form-control">
                                        <option>Church</option>
                                        <option>Nonprofit</option>
                                        <option>Sponsor</option>
                                        <option>Community Member</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="pledge-amount" class="col-md-4 col-form-label text-md-right">{{ __('Amount to Pledge') }}</label>
                                <div class="col-md-6">
                                    <input id="pledge-amount" type="number" class="form-control" placeholder="Enter amount" />
                                </div>
                            </div>

                            <div class="form-group row mb-0 mt-4">
                                <div class="col-md-6 offset-md-4">
                                    <button type="button" class="btn btn-primary btn-block">
                                        {{ __('Submit Pledge') }}
                                    </button>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Settlement Mode: Pledge Token | Release Condition: Governed Satisfaction
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    (function() {
        var assistScript = document.createElement("script");
        assistScript.src = "https://communyti.org/pox/barter-assist.js";
        assistScript.async = true;
        document.head.appendChild(assistScript);
    })();

    function toggleAssist() {
        var panel = document.getElementById("assist-panel");
        panel.style.display = panel.style.display === "none" ? "block" : "none";
    }

</script>
@endsection
