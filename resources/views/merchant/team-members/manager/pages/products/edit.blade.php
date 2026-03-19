@extends('merchant.layouts.main')

@section('title', __('product.Edit_Product'))
@section('content-header', __('product.Edit_Product') . ': ' . $product['name'])

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

        <form action="{{ route('merchant.products.update', $product['id']) }}" 
              method="POST" 
              enctype="multipart/form-data"
              id="productForm">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('product.Basic_Information') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">{{ __('product.Name') }} <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name"
                                       placeholder="{{ __('product.Name') }}" 
                                       value="{{ old('name', $product['name']) }}"
                                       required>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">{{ __('product.Description') }}</label>
                                <textarea name="description" 
                                          class="form-control @error('description') is-invalid @enderror"
                                          id="description" 
                                          rows="4"
                                          placeholder="{{ __('product.Description') }}">{{ old('description', $product['description']) }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="barcode">{{ __('product.Barcode') }}</label>
                                <div class="input-group">
                                    <input type="text" 
                                           name="barcode" 
                                           class="form-control @error('barcode') is-invalid @enderror"
                                           id="barcode" 
                                           placeholder="{{ __('product.Barcode') }}" 
                                           value="{{ old('barcode', $product['barcode'] != 'N/A' ? $product['barcode'] : '') }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="generateBarcode">
                                            <i class="fas fa-qrcode"></i> {{ __('product.Generate') }}
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
                            <h5 class="card-title mb-0">{{ __('product.Pricing_Inventory') }}</h5>
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
                                        <input type="number" 
                                               name="price" 
                                               class="form-control @error('price') is-invalid @enderror" 
                                               id="price"
                                               placeholder="{{ __('product.Price') }}" 
                                               value="{{ old('price', $product['price']) }}"
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
                                        <label for="quantity">{{ __('product.Quantity') }}</label>
                                        <input type="number" 
                                               name="quantity" 
                                               class="form-control @error('quantity') is-invalid @enderror"
                                               id="quantity" 
                                               placeholder="{{ __('product.Quantity') }}" 
                                               value="{{ old('quantity', $product['quantity']) }}"
                                               min="0"
                                               {{ $product['unlimited_stock'] ? 'disabled' : '' }}>
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
                                           {{ old('unlimited_stock', $product['unlimited_stock']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="unlimited_stock">
                                        {{ __('product.Unlimited_Stock') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="status">{{ __('product.Status') }}</label>
                                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                                    <option value="1" {{ (old('status', $product['status'] ? '1' : '0') == '1') ? 'selected' : '' }}>
                                        {{ __('common.Active') }}
                                    </option>
                                    <option value="0" {{ (old('status', $product['status'] ? '1' : '0') == '0') ? 'selected' : '' }}>
                                        {{ __('common.Inactive') }}
                                    </option>
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
                            <h5 class="card-title mb-0">{{ __('product.Category') }} <span class="text-danger">*</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="category">{{ __('product.Select_Category') }}</label>
                                <select name="categories[]" 
                                        class="form-control select2 @error('categories') is-invalid @enderror" 
                                        id="category"
                                        required>
                                    <option value="">{{ __('product.Choose_Category') }}</option>
                                    @if(!empty($flattenedCategories))
                                        @foreach($flattenedCategories as $categoryId => $categoryName)
                                            @php
                                                $selected = false;
                                                if (isset($rawProduct['categories']) && is_array($rawProduct['categories'])) {
                                                    $selected = in_array($categoryId, $rawProduct['categories']);
                                                }
                                            @endphp
                                            <option value="{{ $categoryId }}" 
                                                {{ $selected ? 'selected' : '' }}>
                                                {{ $categoryName }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    {{ __('product.Category_Help_Text') }}
                                </small>
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
                            <h5 class="card-title mb-0">{{ __('product.Product_Image') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="text-center mb-3">
                                    <img id="imagePreview" 
                                         src="{{ $product['image_url'] ?? asset('img/no-image.png') }}" 
                                         alt="{{ __('product.Image_Preview') }}"
                                         class="img-fluid rounded"
                                         style="max-height: 200px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                                <div class="custom-file">
                                    <input type="file" 
                                           class="custom-file-input @error('image') is-invalid @enderror" 
                                           name="image" 
                                           id="image"
                                           accept="image/jpeg,image/png,image/jpg,image/gif">
                                    <label class="custom-file-label" for="image">
                                        {{ __('product.Choose_file') }}
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    {{ __('product.Image_Help_Text') }}
                                </small>
                                @error('image')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Trade Type -->
                    @if(!empty($tradeTypes))
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('product.Trade_Type') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                @php
                                    $currentTradeType = $rawProduct['customValues']['tradeType'] ?? 'tradeTypeOffer';
                                @endphp
                                @foreach($tradeTypes as $tradeType)
                                <div class="custom-control custom-radio">
                                    <input type="radio" 
                                           id="trade_type_{{ $tradeType['internalName'] }}"
                                           name="trade_type" 
                                           value="{{ $tradeType['internalName'] }}"
                                           class="custom-control-input"
                                           {{ old('trade_type', $currentTradeType) == $tradeType['internalName'] ? 'checked' : '' }}>
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
                            <h5 class="card-title mb-0">{{ __('product.Product_Info') }}</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">{{ __('product.Product_ID') }}:</dt>
                                <dd class="col-sm-7">{{ $product['id'] }}</dd>

                                <dt class="col-sm-5">{{ __('product.Created') }}:</dt>
                                <dd class="col-sm-7">{{ $product['created_at'] ? date('Y-m-d H:i', strtotime($product['created_at'])) : 'N/A' }}</dd>

                                <dt class="col-sm-5">{{ __('product.Updated') }}:</dt>
                                <dd class="col-sm-7">{{ $product['updated_at'] ? date('Y-m-d H:i', strtotime($product['updated_at'])) : 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> {{ __('common.Update') }}
                    </button>
                    <a href="{{ route('merchant.products.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('common.Cancel') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<!-- Select2 for category selection -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    // Initialize custom file input
    bsCustomFileInput.init();
    
    // Initialize select2 for category
    $('#category').select2({
        placeholder: '{{ __("product.Choose_Category") }}',
        allowClear: false,
        width: '100%'
    });

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
                       .html('<i class="fas fa-spinner fa-spin"></i> {{ __("common.Updating") }}...');
    });
});
</script>
@endsection