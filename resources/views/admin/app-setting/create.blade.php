@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Setting</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.app-settings.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <form action="{{ route('admin.app-settings.store') }}" method="POST">
                    @csrf
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
                        
                        <!-- Group Selection -->
                        <div class="form-group">
                            <label for="group">Group <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="group_select" id="group_select" class="form-control @error('group') is-invalid @enderror">
                                        <option value="">Select existing group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group }}" {{ old('group_select', $selectedGroup) == $group ? 'selected' : '' }}>
                                                {{ ucfirst($group) }}
                                            </option>
                                        @endforeach
                                        <option value="new">+ Create new group</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" 
                                           name="new_group" 
                                           id="new_group" 
                                           class="form-control @error('group') is-invalid @enderror" 
                                           placeholder="New group name"
                                           style="display: none;">
                                </div>
                            </div>
                            <input type="hidden" name="group" id="group" value="{{ old('group', $selectedGroup) }}">
                            @error('group')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Select an existing group (like cyclos, paypal) or create a new one
                            </small>
                        </div>
                        
                        <!-- Setting Details -->
                        <div class="form-group">
                            <label for="name">Display Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}"
                                   placeholder="e.g., Client ID, Secret Key, API URL"
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
                                   value="{{ old('key') }}"
                                   placeholder="e.g., client_id, secret_key, api_url"
                                   required>
                            @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Used in code to access this setting (use snake_case)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="value">Value</label>
                            <textarea name="value" 
                                      id="value" 
                                      rows="4" 
                                      class="form-control @error('value') is-invalid @enderror"
                                      placeholder="Enter the actual value">{{ old('value') }}</textarea>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="environment">Environment</label>
                            <select name="environment" id="environment" class="form-control @error('environment') is-invalid @enderror">
                                <option value="">All Environments</option>
                                <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>Production</option>
                                <option value="staging" {{ old('environment') == 'staging' ? 'selected' : '' }}>Staging</option>
                                <option value="development" {{ old('environment') == 'development' ? 'selected' : '' }}>Development</option>
                            </select>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Specify environment if this setting is environment-specific
                            </small>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Setting
                        </button>
                        <a href="{{ route('admin.app-settings.index') }}" class="btn btn-default">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle group selection
        $('#group_select').change(function() {
            if($(this).val() === 'new') {
                $('#new_group').show().prop('required', true);
                $('#group').val('');
                $('#group').prop('disabled', true);
            } else {
                $('#new_group').hide().prop('required', false);
                $('#group').val($(this).val());
                $('#group').prop('disabled', false);
            }
        });
        
        // Handle new group input
        $('#new_group').on('input', function() {
            $('#group').val($(this).val());
        });
        
        // Trigger change on page load
        $('#group_select').trigger('change');
        
        // Auto-generate key from name (optional)
        $('#name').on('blur', function() {
            if(!$('#key').val()) {
                let key = $(this).val()
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_|_$/g, '');
                $('#key').val(key);
            }
        });
    });
</script>
@endpush