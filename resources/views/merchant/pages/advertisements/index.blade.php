@extends('merchant.layouts.main')

@section('title', __('Advertisements List'))
@section('content-header', __('Advertisements List'))

@section('content-actions')
<a href="{{ route('merchant.advertisements.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> {{ __('Create Advertisement') }}
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
                <form action="{{ route('merchant.advertisements.index') }}" method="GET" class="form-inline">
                    <div class="input-group w-100">
                        <input type="text" name="search" class="form-control" placeholder="Search Advertisements" value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th width="80">{{ __('product.Image') }}</th>
                        <th width="150">{{ __('product.Name') }}</th>
                        <th width="100">{{ __('product.Price') }}</th>
                        <th width="100">Status</th>
                        <th width="150">Categories</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($advertisements as $index => $advertisement)
                    <tr>
                        <td>{{ $advertisements->firstItem() + $index }}</td>
                        <td>
                            @if($advertisement['image_url'])
                            <img class="product-img" src="{{ $advertisement['image_url'] }}" alt="{{ $advertisement['name'] }}">
                            @else
                            <div class="product-img bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-image text-white"></i>
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="product-name">{{ $advertisement['name'] }}</div>
                            @if(!empty($advertisement['description']))
                            <div class="product-description-preview">
                                {{ Str::limit($advertisement['description'], 60) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <strong class="text-primary">{{ $advertisement['price'] == 0 ? 'N/A' : $advertisement['price'] }}</strong>
                            <br>
                            <small class="text-muted">{{ $advertisement['currency_symbol'] }}</small>
                        </td>
                        <td>
                            @if($advertisement['status'] == 'active')
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($advertisement['categories']) && is_array($advertisement['categories']))
                            @foreach(array_slice($advertisement['categories'], 0, 2) as $category)
                            <span class="category-badge">{{ last(explode('.', $category)) }}</span>
                            @endforeach
                            @if(count($advertisement['categories']) > 2)
                            <span class="category-badge">+{{ count($advertisement['categories']) - 2 }}</span>
                            @endif
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-info btn-sm btn-view" data-advertisement='@json($advertisement)' data-toggle="modal" data-target="#productModal" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a class="btn btn-info btn-sm" href="{{ route('merchant.advertisements.edit', $advertisement['id']) }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-danger btn-sm btn-delete" data-url="{{ route('merchant.advertisements.destroy', $advertisement['id']) }}" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No advertisements Found</p>
                            <small class="text-muted">Click "Create Advertisement" to add your first advertisement.</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        @if($advertisements->hasPages())
        <div class="pagination-wrapper mt-3">
            {{ $advertisements->appends(request()->query())->links() }}
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
                    <i class="fas fa-box"></i> Advertisement Details
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
            var advertisement = $(this).data('advertisement');
            var modalBody = $('#productModalBody');

            // Format categories
            var categoriesHtml = '';
            if (advertisement.categories && advertisement.categories.length > 0) {

                var categoryBadges = [];
                advertisement.categories.forEach(function(cat) {
                    var catName = cat.split('.').pop();
                    categoryBadges.push('<span class="category-badge">' + catName + '</span>');
                });
                categoriesHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Categories:</div><div class="col-md-9 detail-value">' + categoryBadges.join(' ') + '</div></div></div>';
            } else {
                categoriesHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Categories:</div><div class="col-md-9 detail-value"><span class="text-muted">No categories</span></div></div></div>';
            }

            // Format publication period
            var periodHtml = '';
            if (advertisement.publication_period) {
                var beginDate = advertisement.publication_period.begin ? new Date(advertisement.publication_period.begin).toLocaleDateString() : 'N/A';
                var endDate = advertisement.publication_period.end ? new Date(advertisement.publication_period.end).toLocaleDateString() : 'N/A';
                periodHtml = '<div class="detail-row"><div class="row"><div class="col-md-3 detail-label">Published:</div><div class="col-md-9 detail-value">' + beginDate + ' → ' + endDate + '</div></div></div>';
            }

            // Build modal content
            var modalContent = `
            <div class="row">
                <div class="col-md-4 text-center">
                    ${advertisement.image_url ?
                        `<img src="${advertisement.image_url}" alt="${advertisement.name}" class="modal-product-image img-fluid mb-2">` :
                        '<div class="bg-light p-4 rounded"><i class="fas fa-image fa-3x text-muted"></i><p class="mt-2 text-muted mb-0">No image available</p></div>'
                    }
                </div>
                <div class="col-md-8">
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Advertisement Name:</div>
                            <div class="col-md-9 detail-value"><strong>${escapeHtml(advertisement.name)}</strong></div>

                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Price:</div>
                            <div class="col-md-9 detail-value"><strong class="text-primary h5">${advertisement.price == 0 ? 'N/A' : advertisement.price}</strong> <small>(${advertisement.currency_symbol})</small></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Type:</div>
                            <div class="col-md-9 detail-value"><span class="badge badge-secondary">${capitalizeFirstLetter(advertisement.kind)}</span></div>
                        </div>
                    </div>
                    ${periodHtml}
                    ${categoriesHtml}
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Description:</div>
                            <div class="col-md-9 detail-value">${advertisement.description ? nl2br(escapeHtml(advertisement.description)) : '<span class="text-muted">No description</span>'}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Merchant:</div>
                            <div class="col-md-9 detail-value"><i class="fas fa-store"></i> ${advertisement.user && advertisement.user.display ? escapeHtml(advertisement.user.display) : 'N/A'}</div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-md-3 detail-label">Advertisement ID:</div>
                            <div class="col-md-9 detail-value"><small class="text-muted">${escapeHtml(advertisement.id)}</small></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

            modalBody.html(modalContent);
        });

        // Delete advertisement confirmation
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
                                Swal.fire('Deleted!', 'Advertisement has been deleted.', 'success');
                            } else {
                                Swal.fire('Error!', res.message || 'Error deleting advertisement', 'error');
                            }
                        }
                        , error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Error deleting advertisement', 'error');
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

