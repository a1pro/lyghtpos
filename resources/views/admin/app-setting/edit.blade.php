@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Setting: {{ $key->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.app-settings.index', ['group' => $key->group]) }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <form action="{{ route('admin.app-settings.update', $key->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="form-group">
                            <label for="group">Group <span class="text-danger">*</span></label>
                            <select name="group" id="group" class="form-control @error('group') is-invalid @enderror" required>
                                <option value="">Select group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group }}" {{ old('group', $key->group) == $group ? 'selected' : '' }}>
                                        {{ ucfirst($group) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('group')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Display Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $key->name) }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="key">System Key <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="key" 
                                   id="key" 
                                   class="form-control @error('key') is-invalid @enderror" 
                                   value="{{ old('key', $key->key) }}"
                                   required>
                            @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="value">Value</label>
                            <textarea name="value" 
                                      id="value" 
                                      rows="4" 
                                      class="form-control @error('value') is-invalid @enderror">{{ old('value', $key->value) }}</textarea>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="environment">Environment</label>
                            <select name="environment" id="environment" class="form-control @error('environment') is-invalid @enderror">
                                <option value="">All Environments</option>
                                <option value="production" {{ old('environment', $key->environment) == 'production' ? 'selected' : '' }}>Production</option>
                                <option value="staging" {{ old('environment', $key->environment) == 'staging' ? 'selected' : '' }}>Staging</option>
                                <option value="development" {{ old('environment', $key->environment) == 'development' ? 'selected' : '' }}>Development</option>
                            </select>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Setting
                        </button>
                        <a href="{{ route('admin.app-settings.index', ['group' => $key->group]) }}" class="btn btn-default">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection