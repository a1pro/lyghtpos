@extends('merchant.layouts.main')

@section('title', 'Merchant Profile')
@section('content-header', 'My Profile')

@section('css')
<style>
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .info-card {
        background: white;
        border-radius: 10px;
        padding: 0;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .info-card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
        border-radius: 10px 10px 0 0;
    }

    .info-card-header h5 {
        margin: 0;
        font-weight: 600;
        color: #333;
    }

    .info-card-body {
        padding: 20px;
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        width: 140px;
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }

    .info-value {
        flex: 1;
        color: #212529;
        font-size: 14px;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-active {
        background-color: #d4edda;
        color: #155724;
    }

    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .badge-subscribed {
        background-color: #28a745;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-not-subscribed {
        background-color: #dc3545;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }


    .btn-edit {
        float: right;
        margin-top: -5px;
    }

    .wallet-balance {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .wallet-balance .stat-number,
    .wallet-balance .stat-label,
    .wallet-balance .stat-icon {
        color: white;
    }

    .products-stat {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .products-stat .stat-number,
    .products-stat .stat-label,
    .products-stat .stat-icon {
        color: white;
    }

    .sales-stat {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .sales-stat .stat-number,
    .sales-stat .stat-label,
    .sales-stat .stat-icon {
        color: white;
    }

    .revenue-stat {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .revenue-stat .stat-number,
    .revenue-stat .stat-label,
    .revenue-stat .stat-icon {
        color: white;
    }

    .text-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Main Content Row -->
            <div class="row">
                <!-- Left Column - Profile Info -->
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-user-circle"></i> Profile Information</h5>
                        </div>
                        <div class="info-card-body text-center">
                            <img src="{{ $user->getAvatar() }}" alt="{{ $user->getFullname() }}" class="profile-avatar">
                            <h4 class="mb-1">{{ $user->getFullname() }}</h4>
                            <p class="text-muted mb-2">
                                <i class="fas fa-envelope"></i> {{ $user->email }}
                            </p>
                            <div class="mb-3">
                                @if($user->subscribed)
                                <span class="badge-subscribed">
                                    <i class="fas fa-check-circle"></i> Subscribed
                                </span>
                                @else
                                <span class="badge-not-subscribed">
                                    <i class="fas fa-times-circle"></i> Not Subscribed
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Details -->
                <div class="col-md-8">
                    <!-- Personal Details Card -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-address-card"></i> Personal Details</h5>
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <div class="info-label">Full Name:</div>
                                <div class="info-value">{{ $user->getFullname() }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email Address:</div>
                                <div class="info-value">{{ $user->email }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone Number:</div>
                                <div class="info-value">{{ $user->phone ?? 'Not provided' }}</div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm mt-3" data-toggle="modal" data-target="#editProfileModal">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('merchant.profile.update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $user->phone) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
