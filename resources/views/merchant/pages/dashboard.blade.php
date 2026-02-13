@extends('merchant.layouts.main')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Dashboard</h3>
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
                                    <h5 class="card-title">External Links</h5>
                                    <p class="card-text text-muted">Visit external links page to access more of our features</p>
                                    <a href="{{ route('merchant.external-links') }}" class="btn btn-outline-primary">
                                        View &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection
