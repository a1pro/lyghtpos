@extends('layouts.admin')

@section('title', 'Create Governance Layer')

@section('content-header', 'Create Governance Layer')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.governance-layers.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="5">{{ old('description') }}</textarea>
                @error('description')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                    <option value="">Select Status</option>
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Create Governance Layer</button>
                <a href="{{ route('admin.governance-layers') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
