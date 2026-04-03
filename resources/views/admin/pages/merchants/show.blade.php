@extends('layouts.admin')

@section('title', 'Customer Details')

@section('content-header', 'Customer Details')

@section('css')
<style>
    .detail-card {
        margin-bottom: 20px;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    .detail-value {
        color: #212529;
        margin-bottom: 15px;
    }
    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card detail-card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                        
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">{{ $customer->email }}</div>
                        
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value">{{ $customer->phone ?? 'N/A' }}</div>
                        
                        <div class="detail-label">Location</div>
                        <div class="detail-value">{{ $customer->location ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Business Name</div>
                        <div class="detail-value">{{ $customer->business_name ?? 'N/A' }}</div>
                        
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            @if($customer->status)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </div>
                        
                        <div class="detail-label">Registered Date</div>
                        <div class="detail-value">{{ $customer->created_at->format('F d, Y h:i A') }}</div>
                        
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">{{ $customer->updated_at->format('F d, Y h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card detail-card">
            <div class="card-header">
                <h3 class="card-title">Merchant Information</h3>
            </div>
            <div class="card-body">
                @if($customer->parentMerchant)
                    <div class="info-box">
                        <div class="detail-label">Merchant Name</div>
                        <div class="detail-value">
                            <strong>{{ $customer->parentMerchant->first_name }} {{ $customer->parentMerchant->last_name }}</strong>
                        </div>
                        
                        <div class="detail-label">Business Name</div>
                        <div class="detail-value">{{ $customer->parentMerchant->business_name ?? 'N/A' }}</div>
                        
                        <div class="detail-label">Merchant Email</div>
                        <div class="detail-value">{{ $customer->parentMerchant->email }}</div>
                        
                        <div class="detail-label">Merchant Phone</div>
                        <div class="detail-value">{{ $customer->parentMerchant->phone ?? 'N/A' }}</div>
                        
                        <a href="{{ route('admin.customers.index', ['merchant_id' => $customer->parentMerchant->id]) }}" 
                           class="btn btn-info btn-sm mt-2">
                            <i class="fas fa-users"></i> View All Customers of this Merchant
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No merchant assigned to this customer.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection