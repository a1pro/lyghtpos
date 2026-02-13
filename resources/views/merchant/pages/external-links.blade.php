@extends('merchant.layouts.main')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Quick Links</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <i class="fas fa-file-invoice-dollar fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="card-title">Cyclos logging</h5>
                                    <p class="card-text text-muted">View your performance metrics and insights</p>
                                    <a href="https://square.communyti.org/ui/login" class="btn btn-outline-primary" target="_blank">
                                        Visit Site &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <img src="{{ asset('images/logos/logo-hookmeuptv.png') }}" alt="TheWhyKnot Logo" width="180px">
                                    </div>
                                    <h5 class="card-title">HookMeUpTV</h5>
                                    <p class="card-text text-muted">Hook Me Up TV helps local businesses bring customers through the door.</p>
                                    <a href="https://www.hookmeuptv.com/login" class="btn btn-outline-secondary" target="_blank">
                                        Visit Site &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <img src="{{ asset('images/logos/logo-thewhyknot.png') }}" alt="TheWhyKnot Logo" width="180px">
                                    </div>
                                    <h5 class="card-title">TheWhyKnot</h5>
                                    <p class="card-text text-muted">WhyKnot exists to prevent that outcome by ensuring that the Covenant Stack™ remains coherent.</p>
                                    <a href="http://thewhyknot.org/" class="btn btn-outline-danger" target="_blank">
                                        Visit Site &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <img src="{{ asset('images/logos/logo-communyti-org.png') }}" alt="TheWhyKnot Logo" width="160px">
                                    </div>
                                    <h5 class="card-title">Communyti Org</h5>
                                    <p class="card-text text-muted">Communyti helps members and merchants issue and exchange vouchers.</p>
                                    <a href="https://communyti.org/" class="btn btn-outline-success" target="_blank">
                                        Visit Site &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
