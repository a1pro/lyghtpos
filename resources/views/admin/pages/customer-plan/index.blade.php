@extends('layouts.admin')

@section('title', 'Customer Subscription Plans')
@section('content-header', 'Customer Subscription Plans')

@section('content-actions')
<a href="{{ route('admin.customer-plans.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Create Plan
</a>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Sort Order</th>
                    <th>Name</th>
                    <th>Badge</th>
                    <th>Monthly Price</th>
                    <th>Yearly Price</th>
                    <th>Popular</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                <tr>
                    <td>{{ $plan->sort_order }}</td>
                    <td>
                        <strong>{{ $plan->name }}</strong><br>
                        <small class="text-muted">{{ $plan->short_description }}</small>
                    </td>
                    <td>
                        @if($plan->badge_text)
                        <span class="badge badge-{{ $plan->badge_color }}">
                            {{ $plan->badge_text }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>${{ number_format($plan->monthly_price, 2) }}</td>
                    <td>
                        ${{ number_format($plan->yearly_price, 2) }}
                        @if($plan->yearly_savings > 0)
                        <small class="text-success">(Save {{ $plan->yearly_savings }}%)</small>
                        @endif
                    </td>
                    <td>
                        @if($plan->is_popular)
                        <span class="badge badge-warning">Popular</span>
                        @else
                        <span class="badge badge-secondary">No</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->is_active)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.customer-plans.edit', $plan) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger btn-delete" data-url="{{ route('admin.customer-plans.destroy', $plan) }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No subscription plans found.</td>
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
        $(document).on('click', '.btn-delete', function() {
            var $this = $(this);
            Swal.fire({
                title: 'Are you sure?'
                , text: "You won't be able to revert this!"
                , icon: 'warning'
                , showCancelButton: true
                , confirmButtonColor: '#d33'
                , cancelButtonColor: '#3085d6'
                , confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: $this.data('url')
                        , type: 'POST'
                        , data: {
                            _method: 'DELETE'
                            , _token: '{{ csrf_token() }}'
                        }
                        , success: function(res) {
                            if (res.success) {
                                $this.closest('tr').fadeOut(500, function() {
                                    $(this).remove();
                                });
                                Swal.fire('Deleted!', res.message, 'success');
                            }
                        }
                        , error: function(xhr) {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });
    });

</script>
@endsection
