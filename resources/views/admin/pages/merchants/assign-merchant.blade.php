@extends('layouts.admin')

@section('title', 'Assign Merchant to Customer')

@section('content-header', 'Assign Merchant')

@section('css')
<style>
    .customer-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .info-row {
        margin-bottom: 10px;
        padding: 5px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .info-label {
        font-weight: 600;
        color: #495057;
        width: 120px;
        display: inline-block;
    }
    .info-value {
        color: #212529;
    }
    .merchant-card {
        transition: all 0.3s ease;
    }
    .merchant-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .merchant-option {
        display: flex;
        flex-direction: column;
    }
    .merchant-business-name {
        font-weight: 600;
    }
    .merchant-personal-name {
        font-size: 12px;
        color: #6c757d;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-tie"></i> Assign Merchant to Customer
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.merchants.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Customer Information -->
                <div class="customer-info">
                    <h5><i class="fas fa-user-circle"></i> Customer Information</h5>
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $customer->email }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $customer->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value">{{ $customer->location ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            @if($customer->status)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- Current Assignment -->
                @if($assignedMerchant)
                <div class="alert alert-info">
                    <strong><i class="fas fa-store"></i> Currently Assigned Merchant:</strong><br>
                    @if(isset($assignedMerchant->business_name) && $assignedMerchant->business_name)
                        <strong>{{ $assignedMerchant->business_name }}</strong>
                        <br><small>{{ $assignedMerchant->first_name }} {{ $assignedMerchant->last_name }}</small>
                    @else
                        <strong>{{ $assignedMerchant->first_name }} {{ $assignedMerchant->last_name }}</strong>
                    @endif
                    @if($assignedMerchant->email)
                        <br><small><i class="fas fa-envelope"></i> {{ $assignedMerchant->email }}</small>
                    @endif
                    @if($assignedMerchant->phone)
                        <br><small><i class="fas fa-phone"></i> {{ $assignedMerchant->phone }}</small>
                    @endif
                </div>
                @endif
                
                <!-- Assignment Form -->
                <form action="{{ route('admin.merchants.assign-merchant', $customer->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="merchant_id">
                            <i class="fas fa-store"></i> Select Merchant 
                            <span class="text-muted">(Optional - Leave empty to remove assignment)</span>
                        </label>
                        <select name="merchant_id" id="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror">
                            <option value="">-- No Merchant (Remove Assignment) --</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}" 
                                    {{ $assignedMerchant && $assignedMerchant->id == $merchant->id ? 'selected' : '' }}>
                                    @if(isset($merchant->business_name) && $merchant->business_name)
                                        {{ $merchant->business_name }} ({{ $merchant->first_name }} {{ $merchant->last_name }})
                                    @else
                                        {{ $merchant->first_name }} {{ $merchant->last_name }}
                                    @endif
                                    - {{ $merchant->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('merchant_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Assignment
                        </button>
                        <a href="{{ route('admin.merchants.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
                
                @if($merchants->isEmpty())
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>No merchants available!</strong><br>
                    Please create merchant accounts first before assigning them to customers.
                    <hr>
                    <a href="{{ route('admin.merchants.index') }}" class="btn btn-sm btn-info">
                        <i class="fas fa-plus"></i> Create Merchant Account
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Information
                </h3>
            </div>
            <div class="card-body">
                <p>Assigning a merchant to a customer allows:</p>
                <ul>
                    <li><i class="fas fa-chart-line text-success"></i> Tracking customer purchases per merchant</li>
                    <li><i class="fas fa-chart-bar text-info"></i> Merchant-specific reporting and analytics</li>
                    <li><i class="fas fa-handshake text-primary"></i> Customer-merchant relationship management</li>
                    <li><i class="fas fa-tags text-warning"></i> Targeted promotions and offers</li>
                    <li><i class="fas fa-percent text-danger"></i> Merchant-specific discounts and loyalty programs</li>
                </ul>
                <hr>
                <div class="alert alert-light">
                    <i class="fas fa-clock"></i> <strong>Note:</strong><br>
                    You can change or remove this assignment at any time. The changes will take effect immediately.
                </div>
            </div>
        </div>
        
        @if($assignedMerchant)
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <h3 class="card-title">
                    <i class="fas fa-check-circle"></i> Assignment Status
                </h3>
            </div>
            <div class="card-body">
                <p class="text-success">
                    <i class="fas fa-check"></i> Customer is currently assigned to a merchant.
                </p>
                <p class="text-muted small">
                    Last updated: {{ $customer->updated_at->format('F j, Y g:i A') }}
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection