@extends('merchant.layouts.main')

@section('title', __('customer.Customer_List'))
@section('content-header', __('customer.Customer_List'))
@section('content-actions')
<a href="{{ auth()->user()->isAdmin() ? route('customers.create') : route('merchant.customers.create')}}" class="btn btn-primary">{{ __('customer.Add_Customer') }}</a>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card">
    <div class="card-body">
    <form method="GET" action="{{ route('merchant.customers.index') }}" class="mb-3">
        <div class="input-group" style="max-width: 400px;">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search customers..."
                value="{{ $search ?? '' }}"
            >
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
                @if(!empty($search))
                    <a href="{{ route('merchant.customers.index') }}" class="btn btn-default">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Sr no.</th>
                    <th>{{ __('customer.First_Name') }}</th>
                    <th>{{ __('customer.Last_Name') }}</th>
                    <th>{{ __('customer.Email') }}</th>
                    <th>{{ __('customer.Phone') }}</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$customer->first_name}}</td>
                    <td>{{$customer->last_name}}</td>
                    <td>{{$customer->email}}</td>
                    <td>{{$customer->phone ?? 'N/A'}}</td>
                    <td>{{date('Y-m-d H:i',strtotime($customer->created_at))}}</td>
                    <td>
                        <a href="{{ route('merchant.customers.edit', $customer->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('merchant.customers.resend-credentials', $customer->id) }}"
                            method="POST"
                            class="d-inline resend-credentials-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="fas fa-envelope"></i> Resend Credentials
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            @if(!empty($search))
                                No customers found for "{{ $search }}".
                            @else
                                No customers found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $customers->render() }}
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
                title: '{{ __('customer.sure ') }}',
                text: '{{ __('customer.really_delete ') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('customer.yes_delete ') }}',
                cancelButtonText: '{{ __('customer.No ') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post($this.data('url'), {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    }, function(res) {
                        $this.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                        });
                    });
                }
            });
        });

         $(document).on('submit', '.resend-credentials-form', function(e) {
            e.preventDefault();
            var formEl = this;   // raw DOM element

            Swal.fire({
                title: 'Resend credentials?',
                html: 'This will <b>generate a new password</b> and email the login details to this customer.<br><br>The customer\'s current password will <b>stop working immediately</b>.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, send it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    formEl.submit();   // ← native DOM submit, does NOT trigger jQuery's submit handler
                }
            });
        });

    });
</script>
@endsection