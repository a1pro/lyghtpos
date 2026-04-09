@extends('layouts.admin')

@section('title', 'Token Bundles')
@section('content-header','Token Bundles')

@section('content-actions')
<a href="{{ auth()->user()->isAdmin() ? route('admin.token-bundles.create') : route('admin.token-bundles.create')}}" class="btn btn-primary">Create</a>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>CC Tokens</th>
                    <th>Base Amount</th>
                    <th>System Fees</th>
                    <th>Discounted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bundles as $bundle)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{ $bundle->tokens }}</td>
                    <td>{{$bundle->base_amount}}</td>
                    <td>{{$bundle->system_fee}}</td>
                    <td>{{ $bundle->discounted ? 'Yes' : 'No' }}</td>
                    <td>
                        @if($bundle->status == 'active')
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.token-bundles.edit', $bundle) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-danger btn-delete" data-url="{{ route('admin.token-bundles.destroy', $bundle) }}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script type="module">
    $(document).ready(function() {
        $(document).on('click', '.btn-delete', function() {
            var $this = $(this);
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            swalWithBootstrapButtons.fire({
                title: '{{ __('customer.sure ') }}', // Wrap in quotes
                text: '{{ __('customer.really_delete ') }}', // Wrap in quotes
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('customer.yes_delete ') }}', // Wrap in quotes
                cancelButtonText: '{{ __('customer.No ') }}', // Wrap in quotes
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post($this.data('url'), {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}' // Wrap in quotes
                    }, function(res) {
                        $this.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                        });
                    });
                }
            });
        });
    });
</script>
@endsection
