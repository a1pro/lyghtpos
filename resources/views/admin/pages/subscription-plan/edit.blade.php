@extends('layouts.admin')

@section('title', 'Edit Subscription Plan')
@section('content-header', 'Edit Subscription Plan')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.subscription-plans.update', $plan->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="e.g., Basic, Pro, Enterprise" value="{{ old('name', $plan->name) }}">
                        @error('name')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" placeholder="0, 1, 2..." value="{{ old('sort_order', $plan->sort_order) }}">
                        @error('sort_order')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="badge_text">Badge Text</label>
                        <input type="text" name="badge_text" class="form-control @error('badge_text') is-invalid @enderror" id="badge_text" placeholder="e.g., Popular, Best Value" value="{{ old('badge_text', $plan->badge_text) }}">
                        @error('badge_text')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="badge_color">Badge Color</label>
                        <select name="badge_color" class="form-control @error('badge_color') is-invalid @enderror" id="badge_color">
                            <option value="secondary" {{ old('badge_color', $plan->badge_color) == 'secondary' ? 'selected' : '' }}>Secondary (Gray)</option>
                            <option value="primary" {{ old('badge_color', $plan->badge_color) == 'primary' ? 'selected' : '' }}>Primary (Blue)</option>
                            <option value="success" {{ old('badge_color', $plan->badge_color) == 'success' ? 'selected' : '' }}>Success (Green)</option>
                            <option value="danger" {{ old('badge_color', $plan->badge_color) == 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                            <option value="warning" {{ old('badge_color', $plan->badge_color) == 'warning' ? 'selected' : '' }}>Warning (Yellow)</option>
                            <option value="info" {{ old('badge_color', $plan->badge_color) == 'info' ? 'selected' : '' }}>Info (Cyan)</option>
                        </select>
                        @error('badge_color')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description</label>
                <input type="text" name="short_description" class="form-control @error('short_description') is-invalid @enderror" id="short_description" placeholder="Brief description shown in pricing card" value="{{ old('short_description', $plan->short_description) }}">
                @error('short_description')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Full Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Detailed description of the plan">{{ old('description', $plan->description) }}</textarea>
                @error('description')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Monthly Limits Section -->
            <div class="card card-primary collapsed-card mt-3">
                <div class="card-header">
                    <h5 class="card-title">Monthly Subscription Limits</h5>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="products_limit">Monthly Product Upload Limit <span class="text-danger">*</span></label>
                                <input type="number" name="products_limit" class="form-control @error('products_limit') is-invalid @enderror" id="products_limit" placeholder="e.g., 100" value="{{ old('products_limit', $plan->products_limit) }}">
                                <small class="form-text text-muted">Maximum number of products customer can upload on monthly plan. Use -1 for unlimited.</small>
                                @error('products_limit')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_limit">Monthly Customer Limit <span class="text-danger">*</span></label>
                                <input type="number" name="customer_limit" class="form-control @error('customer_limit') is-invalid @enderror" id="customer_limit" placeholder="e.g., 50" value="{{ old('customer_limit', $plan->customer_limit) }}">
                                <small class="form-text text-muted">Maximum number of customers customer can add on monthly plan. Use -1 for unlimited.</small>
                                @error('customer_limit')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yearly Limits Section -->
            <div class="card card-success collapsed-card mt-3">
                <div class="card-header">
                    <h5 class="card-title">Yearly Subscription Limits</h5>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yearly_products_limit">Yearly Product Upload Limit <span class="text-danger">*</span></label>
                                <input type="number" name="yearly_products_limit" class="form-control @error('yearly_products_limit') is-invalid @enderror" id="yearly_products_limit" placeholder="e.g., 1200" value="{{ old('yearly_products_limit', $plan->yearly_products_limit) }}">
                                <small class="form-text text-muted">Maximum number of products customer can upload on yearly plan. Use -1 for unlimited.</small>
                                @error('yearly_products_limit')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yearly_customer_limit">Yearly Customer Limit <span class="text-danger">*</span></label>
                                <input type="number" name="yearly_customer_limit" class="form-control @error('yearly_customer_limit') is-invalid @enderror" id="yearly_customer_limit" placeholder="e.g., 600" value="{{ old('yearly_customer_limit', $plan->yearly_customer_limit) }}">
                                <small class="form-text text-muted">Maximum number of customers customer can add on yearly plan. Use -1 for unlimited.</small>
                                @error('yearly_customer_limit')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="monthly_price">Monthly Price ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" id="monthly_price" placeholder="0.00" value="{{ old('monthly_price', $plan->monthly_price) }}">
                        @error('monthly_price')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="yearly_price">Yearly Price ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="yearly_price" class="form-control @error('yearly_price') is-invalid @enderror" id="yearly_price" placeholder="0.00" value="{{ old('yearly_price', $plan->yearly_price) }}">
                        @error('yearly_price')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

           

            <div class="form-group">
                <label for="features">Features (One per line)</label>
                <textarea name="features" class="form-control @error('features') is-invalid @enderror" id="features" rows="6" placeholder="Unlimited projects&#10;100GB storage&#10;24/7 support&#10;Advanced analytics">@if(old('features')){{ is_array(old('features')) ? implode("\n", old('features')) : old('features') }}@elseif($plan->features && is_array($plan->features)){{ implode("\n", $plan->features) }}@endif</textarea>
                @error('features')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
                <small class="form-text text-muted">Enter each feature on a new line.</small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_popular" class="custom-control-input" id="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_popular">Mark as Popular Plan</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active (Visible to users)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Plan</button>
                <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Initialize collapse/expand functionality
        $('[data-card-widget="collapse"]').on('click', function() {
            $(this).find('i').toggleClass('fa-plus fa-minus');
        });
    });
</script>
@endsection