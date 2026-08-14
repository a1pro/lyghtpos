@extends('merchant.layouts.main')

@section('title', __('Edit Advertisement'))
@section('content-header', __('Edit Advertisement') . ': ' . $advertisement['name'])

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('merchant.advertisements.update', $advertisement['id']) }}" method="POST" enctype="multipart/form-data" id="advertisementForm">

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
                                <label for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Name" value="{{ old('name', $advertisement['name']) }}" required>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Description">{{ old('description', $advertisement['description']) }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pricing</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">Price
                                            @if(!empty($currencies))
                                            ({{ $currencies[0]['symbol'] ?? 'FT' }})
                                            @else
                                            ({{ config('services.cyclos.currency_symbol', 'FT') }})
                                            @endif
                                        </label>
                                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" id="price" placeholder="Price" value="{{ old('price', $advertisement['price']) }}" step="0.01" min="0">
                                        @error('price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
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
                                <select name="categories[]" class="form-control select2 @error('categories') is-invalid @enderror" id="category" required>
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
                <!-- Advertisement Image -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Advertisement Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="text-center mb-3">
                                    @php
                                        $imageUrl = old('image', $advertisement['image']['url'] ?? null);
                                    @endphp
                                    @if($imageUrl)
                                        <img id="imagePreview" src="{{ $imageUrl }}" alt="Advertisement Image" class="img-fluid rounded" style="max-height: 200px; border: 1px solid #ddd; padding: 5px; background: #f8f9fa;">
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
                                    Upload an advertisement image (max 2MB, JPEG/PNG/JPG/GIF)
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
                    <input type="hidden" name="existing_image_id" value="{{ $advertisement['image']['id'] ?? '' }}">

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

                             if (!empty($rawAdvertisement['customValues']) && is_array($rawAdvertisement['customValues'])) {
                                    foreach ($rawAdvertisement['customValues'] as $customValue) {
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

                    <!-- Advertisement Info -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Advertisement Info</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Created:</dt>
                                <dd class="col-sm-7">{{ $advertisement['created_at'] ? date('Y-m-d H:i', strtotime($advertisement['created_at'])) : 'N/A' }}</dd>

                                <dt class="col-sm-5">Updated:</dt>
                                <dd class="col-sm-7">{{ $advertisement['updated_at'] ? date('Y-m-d H:i', strtotime($advertisement['updated_at'])) : 'N/A' }}</dd>
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
                    <a href="{{ route('merchant.advertisements.index') }}" class="btn btn-secondary">
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
        const productForm = document.getElementById('advertisementForm');
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
    });
</script>
@endsection
