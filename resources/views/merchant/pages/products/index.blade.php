@extends('merchant.layouts.main')

@section('title', __('product.Product_List'))
@section('content-header', __('product.Product_List'))

@section('content-actions')
<a href="{{ route('merchant.products.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> {{ __('product.Create_Product') }}
</a>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .badge-success {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .badge-danger {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .search-box {
        margin-bottom: 20px;
    }
    .action-buttons .btn {
        margin: 0 2px;
    }
</style>
@endsection

@section('content')
<div class="card product-list">
    <div class="card-body">
        <div class="row search-box align-items-center">
            {{--  Search --}}
            <div class="col-md-6">
                <form action="{{ route('merchant.products.index') }}" method="GET" class="form-inline">
                    <div class="input-group w-100">
                        <input type="text" name="search" class="form-control" placeholder="{{ __('product.Search_Products') }}" value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            {{-- Import Actions --}}
            <div class="col-md-6 text-right">
                <a href="{{ asset('samples/product-import-sample.csv') }}" class="btn btn-outline-info btn-sm" download>
                    <i class="fas fa-download"></i> Download Sample CSV
                </a>
                <a href="{{ route('merchant.products.import.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-upload"></i> Upload Products
                </a>
                <a href="{{ route('merchant.products.import.index') }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-history"></i> Import History
                </a>
            </div>

        </div>

        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th width="50">{{ __('product.ID') }}</th>
                    <th>{{ __('product.Name') }}</th>
                    <th width="80">{{ __('product.Image') }}</th>
                    <th>{{ __('product.Barcode') }}</th>
                    <th>{{ __('product.Price') }}</th>
                  
                    
                    <th>{{ __('product.Created_At') }}</th>
                    <th>{{ __('product.Updated_At') }}</th>
                 
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr>
                    <td>{{ $loop->iteration + (($products->currentPage() - 1) * $products->perPage()) }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td>
                        @if($product['image_url'])
                            <img class="product-img" src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}">
                        @else
                            <div class="product-img bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-image text-white"></i>
                            </div>
                        @endif
                    </td>
                    <td>{{ $product['barcode'] }}</td>
                    <td>{{ $product['price'] }} {{ $product['currency_symbol'] ?? 'FT' }}</td>
                    
                    
                    <td>{{ $product['created_at'] ? date('Y-m-d H:i', strtotime($product['created_at'])) : 'N/A' }}</td>
                    <td>{{ $product['updated_at'] ? date('Y-m-d H:i', strtotime($product['updated_at'])) : 'N/A' }}</td>
                    
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">
                        {{ __('product.No_Products_Found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Delete product confirmation
    $(document).on('click', '.btn-delete', function() {
        var $this = $(this);
        var url = $this.data('url');
        
        Swal.fire({
            title: '{{ __("product.sure") }}',
            text: '{{ __("product.really_delete") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __("product.yes_delete") }}',
            cancelButtonText: '{{ __("product.No") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            $this.closest('tr').fadeOut(500, function() {
                                $(this).remove();
                                
                                // Check if table is empty
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                            
                            Swal.fire(
                                '{{ __("common.Deleted") }}',
                                '{{ __("product.deleted_success") }}',
                                'success'
                            );
                        } else {
                            Swal.fire(
                                '{{ __("common.Error") }}',
                                res.message || '{{ __("product.error_deleting") }}',
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            '{{ __("common.Error") }}',
                            xhr.responseJSON?.message || '{{ __("product.error_deleting") }}',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endsection