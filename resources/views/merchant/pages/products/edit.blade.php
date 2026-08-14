@extends('merchant.layouts.main')

@section('title', __('product.Edit_Product'))
@section('content-header', __('product.Edit_Product') . ': ' . $product['name'])

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('merchant.products.update', $product['id']) }}" method="POST" enctype="multipart/form-data" id="productForm">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">{{ __('product.Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="{{ __('product.Name') }}" value="{{ old('name', $product['name']) }}" required>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">{{ __('product.Description') }}</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="{{ __('product.Description') }}">{{ old('description', $product['description']) }}</textarea>
                                @error('description')
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
                                        <label for="price">{{ __('product.Price') }}
                                            @if(!empty($currencies))
                                            ({{ $currencies[0]['symbol'] ?? 'FT' }})
                                            @else
                                            ({{ config('services.cyclos.currency_symbol', 'FT') }})
                                            @endif
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" id="price" placeholder="{{ __('product.Price') }}" value="{{ old('price', $product['price']) }}" step="0.01" min="0" required>
                                        @error('price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity">{{ __('product.Quantity') }}</label>
                                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" id="quantity" placeholder="{{ __('product.Quantity') }}" value="{{ old('quantity', $product['quantity']) }}" min="0" {{ $product['unlimited_stock'] ? 'disabled' : '' }}>
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
                                    <input type="checkbox" class="custom-control-input" id="unlimited_stock" name="unlimited_stock" value="1" {{ old('unlimited_stock', $product['unlimited_stock']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="unlimited_stock">
                                        Unlimited Stock
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Category Selection -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Category <span class="text-danger">*</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="category">Select Category</label>
                                <select name="categories[]" class="form-control @error('categories') is-invalid @enderror" id="category" required>
                                    <option value="">Choose Category</option>
                                    @if(!empty($flattenedCategories))
                                    @foreach($flattenedCategories as $categoryId => $categoryName)
                                    <option value="{{ $categoryId }}" {{ $selectedCategoryId == $categoryId ? 'selected' : '' }}>
                                        {{ $categoryName }}
                                    </option>
                                    @endforeach
                                    @endif
                                </select>
                                @error('categories')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
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
                                    @php
                                        $imageUrl = old('image', $product['image']['url'] ?? null);
                                    @endphp
                                    @if($imageUrl)
                                        <img id="imagePreview" src="{{ $imageUrl }}" alt="Product Image" class="img-fluid rounded" style="max-height: 200px; border: 1px solid #ddd; padding: 5px; background: #f8f9fa;">
                                    @else
                                        <img id="imagePreview" src="https://placehold.co/300x300?text=No+Image" alt="No Image" class="img-fluid rounded" style="max-height: 200px; border: 1px solid #ddd; padding: 5px; background: #f8f9fa;">
                                    @endif
                                </div>
                                
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('image') is-invalid @enderror" name="image" id="image" accept="image/jpeg,image/png,image/jpg,image/gif">
                                    <label class="custom-file-label" for="image" id="fileLabel">
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

                    {{-- Hidden field for image ID to track existing image --}}
                    <input type="hidden" name="existing_image_id" value="{{ $product['image']['id'] ?? '' }}">

                    <!-- Trade Type -->
                    @if(!empty($tradeTypes))
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                @php
                                // Extract current trade type from the correct nested structure
                                $currentTradeType = 'tradeTypeOffer'; // default

                                if (!empty($rawProduct['customValues']) && is_array($rawProduct['customValues'])) {
                                    foreach ($rawProduct['customValues'] as $customValue) {
                                        if (isset($customValue['field']['internalName']) && $customValue['field']['internalName'] === 'tradeType') {
                                            if (!empty($customValue['enumeratedValues']) && is_array($customValue['enumeratedValues'])) {
                                                $currentTradeType = $customValue['enumeratedValues'][0]['internalName'] ?? 'tradeTypeOffer';
                                            }
                                            break;
                                        }
                                    }
                                }
                                @endphp

                                @foreach($tradeTypes as $tradeType)
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="trade_type_{{ $tradeType['internalName'] }}" name="trade_type" value="{{ $tradeType['internalName'] }}" class="custom-control-input" {{ old('trade_type', $currentTradeType) == $tradeType['internalName'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="trade_type_{{ $tradeType['internalName'] }}">
                                        {{ $tradeType['value'] }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Product Info -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Product Info</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Created:</dt>
                                <dd class="col-sm-7">{{ $product['created_at'] ? date('Y-m-d H:i', strtotime($product['created_at'])) : 'N/A' }}</dd>

                                <dt class="col-sm-5">Updated:</dt>
                                <dd class="col-sm-7">{{ $product['updated_at'] ? date('Y-m-d H:i', strtotime($product['updated_at'])) : 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="{{ route('merchant.products.index') }}" class="btn btn-secondary">
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize custom file input
        if (typeof bsCustomFileInput !== 'undefined') {
            bsCustomFileInput.init();
        }

        // Get DOM elements
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const fileLabel = document.getElementById('fileLabel');
        const unlimitedStockCheckbox = document.getElementById('unlimited_stock');
        const quantityInput = document.getElementById('quantity');
        const productForm = document.getElementById('productForm');
        const submitBtn = document.getElementById('submitBtn');

        // Store the original image URL for restoration
        const originalImageUrl = imagePreview ? imagePreview.src : null;

        // Image preview functionality
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                
                if (file) {
                    // Update the file label
                    if (fileLabel) {
                        fileLabel.textContent = file.name;
                    }
                    
                    // Read and display the image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    // If no file selected, restore original image
                    if (originalImageUrl) {
                        imagePreview.src = originalImageUrl;
                    }
                    if (fileLabel) {
                        fileLabel.textContent = 'Choose file';
                    }
                }
            });
        }

        // Unlimited stock toggle
        if (unlimitedStockCheckbox && quantityInput) {
            unlimitedStockCheckbox.addEventListener('change', function() {
                quantityInput.disabled = this.checked;
                if (this.checked) {
                    quantityInput.value = '';
                }
            });
        }

        // Form submission handling
        if (productForm && submitBtn) {
            productForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            });
        }

        // Custom file input label update (fallback for when bsCustomFileInput fails)
        const customFileInputs = document.querySelectorAll('.custom-file-input');
        customFileInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'Choose file';
                const label = this.nextElementSibling;
                if (label && label.classList.contains('custom-file-label')) {
                    label.textContent = fileName;
                }
            });
        });

        // Initialize quantity field state on page load
        if (unlimitedStockCheckbox && unlimitedStockCheckbox.checked && quantityInput) {
            quantityInput.disabled = true;
            quantityInput.value = '';
        }
    });
</script>
@endsection