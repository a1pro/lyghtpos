@extends('merchant.team-members.manager.layouts.main')

@section('title', 'Create Product')
@section('content-header', 'Create Product')

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('show_category_warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-triangle"></i> Category Required</strong>
                <p class="mb-0 mt-2">{{ session('error') }}</p>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form action="{{ route('merchant.manager.products.store') }}" 
              method="POST" 
              enctype="multipart/form-data"
              id="productForm">
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name"
                                       placeholder="Enter product name" 
                                       value="{{ old('name') }}"
                                       required>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" 
                                          class="form-control @error('description') is-invalid @enderror"
                                          id="description" 
                                          rows="4"
                                          placeholder="Enter product description">{{ old('description') }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="barcode">Barcode</label>
                                <div class="input-group">
                                    <input type="text" 
                                           name="barcode" 
                                           class="form-control @error('barcode') is-invalid @enderror"
                                           id="barcode" 
                                           placeholder="Enter barcode" 
                                           value="{{ old('barcode') }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="generateBarcode">
                                            <i class="fas fa-qrcode"></i> Generate
                                        </button>
                                    </div>
                                </div>
                                @error('barcode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Inventory -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pricing & Inventory</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">
                                            Price 
                                            @if(!empty($currencies))
                                                ({{ $currencies[0]['symbol'] ?? 'FT' }})
                                            @else
                                                (FT)
                                            @endif
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               name="price" 
                                               class="form-control @error('price') is-invalid @enderror" 
                                               id="price"
                                               placeholder="0.00" 
                                               value="{{ old('price') }}"
                                               step="0.01"
                                               min="0"
                                               required>
                                        @error('price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" 
                                               name="quantity" 
                                               class="form-control @error('quantity') is-invalid @enderror"
                                               id="quantity" 
                                               placeholder="Enter quantity" 
                                               value="{{ old('quantity', 1) }}"
                                               min="0"
                                               {{ old('unlimited_stock') ? 'disabled' : '' }}>
                                        @error('quantity')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="unlimited_stock" 
                                           name="unlimited_stock" 
                                           value="1"
                                           {{ old('unlimited_stock') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="unlimited_stock">
                                        Unlimited Stock (Product never runs out)
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                                    <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active (Visible in marketplace)</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive (Hidden from marketplace)</option>
                                </select>
                                @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Category Selection (Required) -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Category <span class="text-danger">*</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="category">Select Category</label>
                                <select name="categories[]" 
                                        class="form-control @error('categories') is-invalid @enderror @error('categories.*') is-invalid @enderror" 
                                        id="category"
                                        required>
                                    <option value="">-- Choose a category --</option>
                                    @if(!empty($flattenedCategories) && is_array($flattenedCategories))
                                        @foreach($flattenedCategories as $categoryId => $categoryName)
                                            <option value="{{ $categoryId }}" 
                                                {{ (old('categories') && is_array(old('categories')) && in_array($categoryId, old('categories'))) ? 'selected' : '' }}>
                                                {{ $categoryName }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    Select a category for your product (required)
                                </small>
                                @error('categories')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Trade Type -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" 
                                           id="tradeTypeOffer" 
                                           name="trade_type" 
                                           value="tradeTypeOffer"
                                           class="custom-control-input"
                                           {{ old('trade_type', 'tradeTypeOffer') == 'tradeTypeOffer' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tradeTypeOffer">
                                        <strong>Offer</strong> - I want to sell this product
                                    </label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" 
                                           id="tradeTypeDemand" 
                                           name="trade_type" 
                                           value="tradeTypeDemand"
                                           class="custom-control-input"
                                           {{ old('trade_type') == 'tradeTypeDemand' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tradeTypeDemand">
                                        <strong>Demand</strong> - I want to buy this product
                                    </label>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Specify whether you're offering this product for sale or requesting to buy it
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Product Image -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Product Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="text-center mb-3">
                                    <img id="imagePreview" 
                                         src="{{ asset('img/no-image.png') }}" 
                                         alt="Image Preview"
                                         class="img-fluid rounded"
                                         style="max-height: 200px; border: 1px solid #ddd; padding: 5px; background: #f8f9fa;">
                                </div>
                                <div class="custom-file">
                                    <input type="file" 
                                           class="custom-file-input @error('image') is-invalid @enderror" 
                                           name="image" 
                                           id="image"
                                           accept="image/jpeg,image/png,image/jpg,image/gif">
                                    <label class="custom-file-label" for="image">
                                        Choose file
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Upload a product image (max 2MB, JPEG/PNG/JPG/GIF)
                                </small>
                                @error('image')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Available Currencies -->
                    @if(!empty($currencies))
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Available Currencies</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @foreach($currencies as $currency)
                                <li>
                                    <i class="fas fa-coins text-warning"></i>
                                    {{ $currency['name'] ?? 'Unknown' }} 
                                    ({{ $currency['symbol'] ?? 'FT' }})
                                </li>
                                @endforeach
                            </ul>
                            <small class="form-text text-muted mt-2">
                                Products are priced in the default currency
                            </small>
                        </div>
                    </div>
                    @endif

                    <!-- Help Card -->
                    <div class="card mb-3 bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle text-info"></i> Product Tips</h6>
                            <ul class="small mb-0 pl-3">
                                <li>Choose a specific category for better visibility</li>
                                <li>Add a clear product image</li>
                                <li>Set accurate price and quantity</li>
                                <li>Active products appear immediately in marketplace</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Create Product
                    </button>
                    <a href="{{ route('merchant.manager.products.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
$(document).ready(function () {
    // Initialize custom file input
    bsCustomFileInput.init();

    // Image preview
    $('#image').on('change', function() {
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Unlimited stock toggle
    $('#unlimited_stock').on('change', function() {
        $('#quantity').prop('disabled', this.checked);
        if (this.checked) {
            $('#quantity').val('');
        } else {
            $('#quantity').val(1);
        }
    });

    // Generate barcode
    $('#generateBarcode').on('click', function() {
        // Generate a random 13-digit barcode (EAN-13 format)
        var barcode = '2' + Math.floor(Math.random() * 1000000000000).toString().padStart(12, '0');
        $('#barcode').val(barcode);
    });

    // Form submission handling
    $('#productForm').on('submit', function() {
        $('#submitBtn').prop('disabled', true)
                       .html('<i class="fas fa-spinner fa-spin"></i> Creating Product...');
    });

    // Category validation
    $('#category').on('change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>

@if(session('show_category_warning'))
<script>
    $(document).ready(function() {
        $('#category').addClass('is-invalid');
    });
</script>
@endif
@endsection