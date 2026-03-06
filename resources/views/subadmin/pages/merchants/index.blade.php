@extends('subadmin.layouts.main')

@section('title', 'Merchants')

@section('content-header', 'Merchants')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .btn-icon {
        padding: 5px 10px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="card product-list">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Serial No.</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Business Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Website</th>
                        <th>Consent Language</th>
                        <th>Communyti Enrollment</th>
                        <th>Advocate Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($merchants as $merchant)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$merchant->role->name ?? 'N/A'}}</td>
                        <td>{{$merchant->first_name . ' ' . $merchant->last_name}}</td>
                        <td>{{$merchant->email}}</td>
                        <td>{{$merchant->business_name ?? 'N/A'}}</td>
                        <td>{{$merchant->phone ?? 'N/A'}}</td>
                        <td>{{$merchant->location ?? 'N/A'}}</td>
                        <td>
                            @if($merchant->website)
                                <a href="{{$merchant->website}}" target="_blank">{{$merchant->website}}</a>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{$merchant->consent_language_authorizing ?? 'N/A'}}</td>
                        <td>{{$merchant->communyti_enrollment ?? 'N/A'}}</td>
                        <td>{{$merchant->advocate_contact ?? 'N/A'}}</td>
                        <td>
                            <div class="action-buttons">
                                <!-- View Button -->
                                <a href="{{route('subadmin.representations.view', $merchant->id)}}" 
                                   class="btn btn-sm btn-primary btn-icon" 
                                   title="View Merchant">
                                    <i class="fas fa-eye"></i>
                                    <span class="d-none d-md-inline">View</span>
                                </a>
                                
                                <!-- Change Password Button -->
                                <a href="{{route('subadmin.merchants.change-password', $merchant->id)}}" 
                                   class="btn btn-sm btn-warning btn-icon" 
                                   title="Change Password">
                                    <i class="fas fa-key"></i>
                                    <span class="d-none d-md-inline">Password</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center">
                            <div class="alert alert-info mb-0">
                                No merchants found.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    // Auto-hide alerts after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
@endsection