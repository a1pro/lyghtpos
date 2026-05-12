@extends('merchant.layouts.main')

@section('title', 'Create Product')
@section('content-header', 'Create Product')

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('show_category_warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Category Required</strong>
            <p class="mb-0 mt-2">{{ session('error') }}</p>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        <form action="{{ route('merchant.products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
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
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Enter product name" value="{{ old('name') }}" required>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Enter product description">{{ old('description') }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="barcode">Barcode</label>
                                <div class="input-group">
                                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" id="barcode" placeholder="Enter barcode" value="{{ old('barcode') }}">
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
                                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" id="price" placeholder="0.00" value="{{ old('price') }}" step="0.01" min="0" required>
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
                                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" id="quantity" placeholder="Enter quantity" value="{{ old('quantity', 1) }}" min="0" {{ old('unlimited_stock') ? 'disabled' : '' }}>
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
                                    <input type="checkbox" class="custom-control-input" id="unlimited_stock" name="unlimited_stock" value="1" {{ old('unlimited_stock') ? 'checked' : '' }}>
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
                                <select name="categories[]" class="form-control @error('categories') is-invalid @enderror @error('categories.*') is-invalid @enderror" id="category" required>
                                    <option value="">-- Choose a category --</option>
                                    @if(!empty($flattenedCategories) && is_array($flattenedCategories))
                                    @foreach($flattenedCategories as $categoryId => $categoryName)
                                    <option value="{{ $categoryId }}" {{ (old('categories') && is_array(old('categories')) && in_array($categoryId, old('categories'))) ? 'selected' : '' }}>
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
                                    <input type="radio" id="tradeTypeOffer" name="trade_type" value="tradeTypeOffer" class="custom-control-input" {{ old('trade_type', 'tradeTypeOffer') == 'tradeTypeOffer' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tradeTypeOffer">
                                        <strong>Offer</strong>
                                    </label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="tradeTypeDemand" name="trade_type" value="tradeTypeDemand" class="custom-control-input" {{ old('trade_type') == 'tradeTypeDemand' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tradeTypeDemand">
                                        <strong>Demand</strong>
                                    </label>
                                </div>
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
                                    <img id="imagePreview" src="https://placehold.co/300x300?text=No+Image" alt="Image Preview" class="img-fluid rounded" style="max-height: 200px; border: 1px solid #ddd; padding: 5px; background: #f8f9fa;">
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('image') is-invalid @enderror" name="image" id="image" accept="image/jpeg,image/png,image/jpg,image/gif">
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
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize custom file input (if bsCustomFileInput is available)
        if (typeof bsCustomFileInput !== 'undefined') {
            bsCustomFileInput.init();
        }

        // Get DOM elements
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const unlimitedStockCheckbox = document.getElementById('unlimited_stock');
        const quantityInput = document.getElementById('quantity');
        const generateBarcodeBtn = document.getElementById('generateBarcode');
        const barcodeInput = document.getElementById('barcode');
        const productForm = document.getElementById('productForm');
        const submitBtn = document.getElementById('submitBtn');
        const categorySelect = document.getElementById('category');

        // Image preview functionality
        if (imageInput) {
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        if (imagePreview) {
                            imagePreview.src = e.target.result;
                        }
                    };

                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Unlimited stock toggle functionality
        if (unlimitedStockCheckbox && quantityInput) {
            function toggleQuantityField() {
                const isChecked = unlimitedStockCheckbox.checked;
                quantityInput.disabled = isChecked;

                if (isChecked) {
                    quantityInput.value = '';
                } else {
                    // Only set to 1 if it's empty or 0
                    if (!quantityInput.value || quantityInput.value === '0') {
                        quantityInput.value = '1';
                    }
                }
            }

            unlimitedStockCheckbox.addEventListener('change', toggleQuantityField);
            toggleQuantityField(); // Run on page load to set initial state
        }

        // Generate barcode functionality
        if (generateBarcodeBtn && barcodeInput) {
            generateBarcodeBtn.addEventListener('click', function() {
                // Generate a random 13-digit barcode (EAN-13 format)
                const randomPart = Math.floor(Math.random() * 1000000000000).toString().padStart(12, '0');
                const barcode = '2' + randomPart;
                barcodeInput.value = barcode;
            });
        }

        // Form submission handling
        if (productForm && submitBtn) {
            productForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Product...';
            });
        }

        // Category validation
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('is-invalid');
                }
            });
        }

        // Custom file input label update (fallback for bs-custom-file-input)
        const customFileInputs = document.querySelectorAll('.custom-file-input');
        customFileInputs.forEach(function(input) {
            input.addEventListener('change', function(e) {
                const fileName = this.files[0] ? this.files[0].name : 'Choose file';
                const label = this.nextElementSibling;
                if (label && label.classList.contains('custom-file-label')) {
                    label.textContent = fileName;
                }
            });
        });

        // Initialize quantity field state on page load
        if (unlimitedStockCheckbox && unlimitedStockCheckbox.checked) {
            if (quantityInput) {
                quantityInput.disabled = true;
                quantityInput.value = '';
            }
        }
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
