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

    .badge-warning {
        background-color: #ffc107;
        color: #856404;
        padding: 5px 10px;
        border-radius: 4px;
    }

    .badge-info {
        background-color: #17a2b8;
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

    .category-badge {
        display: inline-block;
        background-color: #e9ecef;
        color: #495057;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        margin: 2px;
    }

    .product-name {
        font-weight: 500;
    }

    .product-description-preview {
        font-size: 11px;
        color: #6c757d;
        margin-top: 3px;
    }

    /* Modal Styles */
    .modal-product-image {
        max-width: 100%;
        max-height: 200px;
        object-fit: contain;
        border-radius: 8px;
    }

    .detail-row {
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        color: #212529;
        font-size: 14px;
    }

    .table td {
        vertical-align: middle;
    }

</style>
@endsection

@section('content')
<div class="card product-list">
    <div class="card-body">
        <div class="row search-box align-items-center">
            {{-- Search --}}
            <div class="col-md-6">
                <form action="{{ route('merchant.products.index') }}" method="GET" class="form-inline">
                    <div class="input-group w-100">
                        <input type="text" name="search" class="form-control" placeholder="Search Product" value="{{ request('search') }}">
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
                <a href="{{ route('merchant.categories.index') }}" class="btn btn-info btn-sm">
                    <i class="fa-solid fa-layer-group"></i> Categories
                </a>
                <a href="{{ asset('samples/product-import-sample.csv') }}" class="btn btn-outline-info btn-sm" download>
                    <i class="fas fa-download"></i> Download Sample CSV
                </a>
                <a href="{{ route('merchant.products.import.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-upload"></i> Upload Products
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th width="80">{{ __('product.Image') }}</th>
                        <th width="150">{{ __('product.Name') }}</th>
                        <th width="100">Product #</th>
                        <th width="100">{{ __('product.Price') }}</th>
                        <th width="100">Stock</th>
                        <th width="100">Status</th>
                        <th width="150">Categories</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $index => $product)
                    <tr>
                        <td>{{ $products->firstItem() + $index }}</td>
                        <td>
                            @if($product['image_url'])
                            <img class="product-img" src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}">
                            @else
                            <div class="product-img bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-image text-white"></i>
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="product-name">{{ $product['name'] }}</div>
                            @if(!empty($product['description']))
                            <div class="product-description-preview">
                                {{ Str::limit($product['description'], 60) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <code>{{ $product['product_number'] }}</code>
                        </td>
                        <td>
                            <strong class="text-primary">{{ $product['price'] }}</strong>
                            <br>
                            <small class="text-muted">{{ $product['currency_symbol'] }}</small>
                        </td>
                        <td>
                            @if($product['unlimited_stock'])
                            <span class="badge badge-info">Unlimited</span>
                            @elseif($product['stock_quantity'] <= 0) <span class="badge badge-danger">Out of Stock</span>
                                @elseif($product['stock_quantity'] <= 20) <span class="badge badge-warning">Low: {{ $product['stock_quantity'] }}</span>
                                    @else
                                    <span class="badge badge-success">{{ $product['stock_quantity'] }} units</span>
                                    @endif
                        </td>
                        <td>
                            @if($product['status'] == 'active')
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($product['categories']) && is_array($product['categories']))
                            @foreach(array_slice($product['categories'], 0, 2) as $category)
                            <span class="category-badge">{{ last(explode('.', $category)) }}</span>
                            @endforeach
                            @if(count($product['categories']) > 2)
                            <span class="category-badge">+{{ count($product['categories']) - 2 }}</span>
                            @endif
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-info btn-sm btn-view" data-product='@json($product)' data-toggle="modal" data-target="#productModal" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a class="btn btn-info btn-sm btn-view" href="{{ route('merchant.products.edit', $product['id']) }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-danger btn-sm btn-delete" data-url="{{ route('merchant.products.destroy', $product['id']) }}" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No Products Found</p>
                            <small class="text-muted">Click "Create Product" to add your first product.</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        @if($products->hasPages())
        <div class="pagination-wrapper mt-3">
            {{ $products->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalLabel">
                    <i class="fas fa-box"></i> Product Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="productModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // View Product Details
        $(document).on('click', '.btn-view', function() {
            var product = $(this).data('product');
            var modalBody = $('#productModalBody');

            // Format categories
            var categoriesHtml = '';
            if (product.categories && product.categories.length > 0) {
                var categoryBadges = [];
                product.categories.forEach(function(cat) {
                    var catName = cat.split('.').pop();
                    categoryBadges.push('<span class="category-badge">' + catName + '</span>');
                });
                categoriesHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Categories:</div><div class="col-md-9 detail-value">' + categoryBadges.join(' ') + '</div></div></div>';
            } else {
                categoriesHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Categories:</div><div class="col-md-9 detail-value"><span class="text-muted">No categories</span></div></div></div>';
            }

            // Format stock info
            var stockHtml = '';
            if (product.unlimited_stock) {
                stockHtml = '<span class="badge badge-info">Unlimited Stock</span>';
            } else {
                var stockClass = product.stock_quantity <= 0 ? 'danger' : (product.stock_quantity <= 20 ? 'warning' : 'success');
                stockHtml = '<span class="badge badge-' + stockClass + '">' + product.stock_quantity + ' units available</span>';
            }

            // Format publication period
            var periodHtml = '';
            if (product.publication_period) {
                var beginDate = product.publication_period.begin ? new Date(product.publication_period.begin).toLocaleDateString() : 'N/A';
                var endDate = product.publication_period.end ? new Date(product.publication_period.end).toLocaleDateString() : 'N/A';
                periodHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Published:</div><div class="col-md-9 detail-value">' + beginDate + ' → ' + endDate + '</div></div></div>';
            }

            // Build modal content
            var modalContent = `
            <div class="row">
                <div class="col-md-4 text-center">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" alt="${product.name}" class="modal-product-image img-fluid mb-2">` : 
                        '<div class="bg-light p-4 rounded"><i class="fas fa-image fa-3x text-muted"></i><p class="mt-2 text-muted mb-0">No image available</p></div>'
                    }
                </div>
                <div class="col-md-8">
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Product Name:</div>
                            <div class="col-md-9 detail-value"><strong>${escapeHtml(product.name)}</strong></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Product #:</div>
                            <div class="col-md-9 detail-value"><code>${escapeHtml(product.product_number)}</code></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Price:</div>
                            <div class="col-md-9 detail-value"><strong class="text-primary h5">${product.price}</strong> <small>(${product.currency_symbol})</small></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Stock:</div>
                            <div class="col-md-9 detail-value">${stockHtml}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Type:</div>
                            <div class="col-md-9 detail-value"><span class="badge badge-secondary">${capitalizeFirstLetter(product.kind)}</span></div>
                        </div>
                    </div>
                    ${periodHtml}
                    ${categoriesHtml}
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Description:</div>
                            <div class="col-md-9 detail-value">${product.description ? nl2br(escapeHtml(product.description)) : '<span class="text-muted">No description</span>'}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Merchant:</div>
                            <div class="col-md-9 detail-value"><i class="fas fa-store"></i> ${product.user && product.user.display ? escapeHtml(product.user.display) : 'N/A'}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Product ID:</div>
                            <div class="col-md-9 detail-value"><small class="text-muted">${escapeHtml(product.id)}</small></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

            modalBody.html(modalContent);
        });

        // Delete product confirmation
        $(document).on('click', '.btn-delete', function() {
            var $this = $(this);
            var url = $this.data('url');

            Swal.fire({
                title: 'Are you sure?'
                , text: "You won't be able to revert this!"
                , icon: 'warning'
                , showCancelButton: true
                , confirmButtonColor: '#d33'
                , cancelButtonColor: '#3085d6'
                , confirmButtonText: 'Yes, delete it!'
                , cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url
                        , type: 'DELETE'
                        , data: {
                            _token: '{{ csrf_token() }}'
                        }
                        , success: function(res) {
                            if (res.success) {
                                location.reload();
                                Swal.fire('Deleted!', 'Product has been deleted.', 'success');
                            } else {
                                Swal.fire('Error!', res.message || 'Error deleting product', 'error');
                            }
                        }
                        , error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Error deleting product', 'error');
                        }
                    });
                }
            });
        });
    });

    // Helper function to escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Captialize first letter of a string
    function capitalizeFirstLetter(val) {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
    }


    // Helper function to convert newlines to <br>
    function nl2br(str) {
        if (!str) return '';
        return str.replace(/\n/g, '<br>');
    }

</script>
@endsection
