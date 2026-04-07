@extends('layouts.admin')

@section('title', 'Joining Fees Plans')
@section('content-header','Joining Fees Plans')
@section('content-actions')
<a href="{{ auth()->user()->isAdmin() ? route('admin.joining-fees.create') : route('admin.joining-fees.create')}}" class="btn btn-primary">Add Plans</a>
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
                    <th>User Role</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>{{ __('customer.Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                <tr>
                    <td>{{$loop->iteration}}</td>
                     
                    <td>{{$plan->role->name}}</td>
                    <td>{{$plan->amount}}</td>
                 <td>
    @if($plan->status == 'active')
        <span class="badge badge-success">Active</span>
    @else
        <span class="badge badge-danger">Inactive</span>
    @endif
</td>
                     
                   
                    <td>
                        <a href="{{auth()->user()->role_id ==1 ? route('admin.joining-fees.edit', $plan) : route('admin.joining-fees.edit', $plan) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-danger btn-delete" data-url="{{auth()->user()->role_id == 1 ? route('admin.joining-fees.destroy', $plan):route('admin.joining-fees.destroy', $plan)}}"><i class="fas fa-trash"></i></button>
                    </td> ss
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