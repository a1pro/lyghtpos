@extends('layouts.admin')

@section('title', __('customer.Create_Customer') )
@section('content-header', __('customer.Create_Customer') )

@section('content')

    <div class="card">
        <div class="card-body">
                
            <form action="{{ route('admin.joining-fees.store')}}" method="POST" enctype="multipart/form-data">
                @csrf
                @php
    $roles = userRoles(); // Assuming this returns collection or array of roles
@endphp

    <div class="form-group">
    <label for="role_id">Role Name</label>
    <select name="role_id" class="form-control @error('role_id') is-invalid @enderror" id="role_id">
        <option value="">Select Role</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
    @error('role_id')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

                <div class="form-group">
                    <label for="last_name">Amount</label>
                    <input type="text" name="amount" class="form-control @error('amount') is-invalid @enderror"
                           id="last_name"
                           placeholder="Add Plan Amount" value="{{ old('amount') }}">
                    @error('amount')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

               {{-- Add this after amount field in create view --}}
<div class="form-group">
    <label for="status">Status</label>
    <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    @error('status')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

                <button class="btn btn-primary" type="submit">Create</button>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            bsCustomFileInput.init();
        });
    </script>
@endsection
