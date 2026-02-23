@extends('layouts.admin')

@section('title', 'Edit Governance Layer - ' . $governanceLayer->name)

@section('content-header', 'Edit Governance Layer')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.governance-layers.update', $governanceLayer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $governanceLayer->name) }}" required>
                @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="5">{{ old('description', $governanceLayer->description) }}</textarea>
                @error('description')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', $governanceLayer->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $governanceLayer->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <!-- Add more fields based on your GovernanceLayer model structure -->

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Governance Layer</button>
                <a href="{{ route('admin.governance-layers') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </form>
    </div>
</div>
@endsection
