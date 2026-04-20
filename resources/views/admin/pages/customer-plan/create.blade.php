@extends('layouts.admin')

@section('title', 'Customer Create Subscription Plan')
@section('content-header', 'Customer Create Subscription Plan')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.customer-plans.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="e.g., Basic, Pro, Enterprise" value="{{ old('name') }}">
                        @error('name')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" placeholder="0, 1, 2..." value="{{ old('sort_order', 0) }}">
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
                        <input type="text" name="badge_text" class="form-control @error('badge_text') is-invalid @enderror" id="badge_text" placeholder="e.g., Popular, Best Value" value="{{ old('badge_text') }}">
                        @error('badge_text')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="badge_color">Badge Color</label>
                        <select name="badge_color" class="form-control @error('badge_color') is-invalid @enderror" id="badge_color">
                            <option value="secondary" {{ old('badge_color') == 'secondary' ? 'selected' : '' }}>Secondary (Gray)</option>
                            <option value="primary" {{ old('badge_color') == 'primary' ? 'selected' : '' }}>Primary (Blue)</option>
                            <option value="success" {{ old('badge_color') == 'success' ? 'selected' : '' }}>Success (Green)</option>
                            <option value="danger" {{ old('badge_color') == 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                            <option value="warning" {{ old('badge_color') == 'warning' ? 'selected' : '' }}>Warning (Yellow)</option>
                            <option value="info" {{ old('badge_color') == 'info' ? 'selected' : '' }}>Info (Cyan)</option>
                        </select>
                        @error('badge_color')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description</label>
                <input type="text" name="short_description" class="form-control @error('short_description') is-invalid @enderror" id="short_description" placeholder="Brief description shown in pricing card" value="{{ old('short_description') }}">
                @error('short_description')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Full Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Detailed description of the plan">{{ old('description') }}</textarea>
                @error('description')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="monthly_price">Monthly Price ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" id="monthly_price" placeholder="0.00" value="{{ old('monthly_price') }}">
                        @error('monthly_price')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="yearly_price">Yearly Price ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="yearly_price" class="form-control @error('yearly_price') is-invalid @enderror" id="yearly_price" placeholder="0.00" value="{{ old('yearly_price') }}">
                        @error('yearly_price')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="yearly_total">Yearly Total (Optional - if different from yearly price)</label>
                <input type="number" step="0.01" name="yearly_total" class="form-control @error('yearly_total') is-invalid @enderror" id="yearly_total" placeholder="Leave empty to use yearly price" value="{{ old('yearly_total') }}">
                <small class="form-text text-muted">Only fill if yearly total is different from the yearly price above.</small>
                @error('yearly_total')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="features">Features (One per line)</label>
                <textarea name="features" class="form-control @error('features') is-invalid @enderror" id="features" rows="6" placeholder="Unlimited projects&#10;100GB storage&#10;24/7 support&#10;Advanced analytics">{{ old('features') }}</textarea>
                @error('features')
                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                @enderror
                <small class="form-text text-muted">Enter each feature on a new line.</small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_popular" class="custom-control-input" id="is_popular" value="1" {{ old('is_popular') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_popular">Mark as Popular Plan</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active (Visible to users)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Create Plan</button>
                <a href="{{ route('admin.customer-plans.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Auto-generate slug from name (optional)
        $('#name').on('keyup', function() {
            // You can add slug preview if needed
        });
    });

</script>
@endsection
