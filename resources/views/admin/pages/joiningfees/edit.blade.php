{{-- resources/views/admin/pages/joiningfees/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Edit Joining Fee Plan'))
@section('content-header', __('Edit Joining Fee Plan'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.joining-fees.update', $plan->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            @php
                $roles = userRoles();
            @endphp

            <div class="form-group">
                <label for="role_id">Role Name <span class="text-danger">*</span></label>
                <select name="role_id" class="form-control @error('role_id') is-invalid @enderror" id="role_id" required>
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" 
                            {{ old('role_id', $plan->role_id) == $role->id ? 'selected' : '' }}>
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
                <label for="amount">Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="amount" 
                    class="form-control @error('amount') is-invalid @enderror"
                    id="amount" placeholder="Enter plan amount" 
                    value="{{ old('amount', $plan->amount) }}" required>
                @error('amount')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                    <option value="active" {{ old('status', $plan->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $plan->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Update Plan</button>
            <a href="{{ route('admin.joining-fees.index') }}" class="btn btn-secondary">Cancel</a>
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