@extends('layouts.admin')

@section('content-header')
    App Settings
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Configuration Settings</h3>
                </div>
                <div class="card-body p-0">
                    <div class="row">
                        <!-- Left Sidebar - Groups List -->
                        <div class="col-md-3 border-right p-0">
                            <div class="list-group list-group-flush">
                                @foreach($groups as $group)
                                    <a href="{{ route('admin.app-settings.index', ['group' => $group]) }}" 
                                       class="list-group-item list-group-item-action {{ $activeGroup == $group ? 'active' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-cog mr-2"></i>
                                                {{ ucwords(str_replace('_', ' ', $group)) }}
                                            </div>
                                            @if($activeGroup == $group)
                                                <i class="fas fa-chevron-right"></i>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Right Content - Settings Form -->
                        <div class="col-md-9">
                            <div class="p-4">
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif
                                
                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('error') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif
                                
                                @if($activeGroup && isset($settingsByGroup[$activeGroup]))
                                    <form action="{{ route('admin.app-settings.update', $activeGroup) }}" method="POST" id="settingsForm">
                                        @csrf
                                        @method('PUT')
                                        
                                        <div class="mb-4">
                                            <h4 class="mb-3">{{ ucwords(str_replace('_', ' ', $activeGroup)) }}</h4>
                                            <p class="text-muted">
                                                Configure your {{ ucwords(str_replace('_', ' ', $activeGroup)) }} settings below.
                                            </p>
                                        </div>
                                        
                                        @foreach($settingsByGroup[$activeGroup] as $setting)
                                            <div class="form-group mb-4">
                                                <label for="{{ $setting->key }}">
                                                    {{ $setting->name }}
                                                     
                                                </label>
                                                
                                                @if(str_contains($setting->key, 'secret') || str_contains($setting->key, 'password') || str_contains($setting->key, 'key'))
                                                    <input type="password" 
                                                           name="{{ $setting->key }}" 
                                                           id="{{ $setting->key }}" 
                                                           class="form-control" 
                                                           value="{{ $setting->value }}"
                                                           autocomplete="off">
                                                    <small class="form-text text-muted">
                                                        {{ $setting->key == 'secret_key' ? 'Enter the Client Secret securely.' : 'Enter the ' . $setting->name }}
                                                    </small>
                                                @elseif(str_contains($setting->key, 'url') || str_contains($setting->key, 'callback'))
                                                    <input type="url" 
                                                           name="{{ $setting->key }}" 
                                                           id="{{ $setting->key }}" 
                                                           class="form-control" 
                                                           value="{{ $setting->value }}"
                                                           placeholder="https://example.com">
                                                    <small class="form-text text-muted">
                                                        Enter the URL where users will be redirected after {{ str_contains($setting->key, 'callback') ? 'authentication' : 'login' }}
                                                    </small>
                                                @elseif(str_contains($setting->key, 'email'))
                                                    <input type="email" 
                                                           name="{{ $setting->key }}" 
                                                           id="{{ $setting->key }}" 
                                                           class="form-control" 
                                                           value="{{ $setting->value }}">
                                                    <small class="form-text text-muted">
                                                        Enter admin email to which all the system notifications will be sent.
                                                    </small>
                                                @else
                                                    <input type="text" 
                                                           name="{{ $setting->key }}" 
                                                           id="{{ $setting->key }}" 
                                                           class="form-control" 
                                                           value="{{ $setting->value }}">
                                                    <small class="form-text text-muted">
                                                        {{ $setting->description ?? 'Enter the ' . $setting->name }}
                                                    </small>
                                                @endif
                                            </div>
                                        @endforeach
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                                        <h5>No Settings Found</h5>
                                        <p class="text-muted">Please select a group from the left sidebar or add settings to the database.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .list-group-item {
        border-left: none;
        border-right: none;
        border-radius: 0;
        padding: 15px 20px;
    }
    
    .list-group-item.active {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .list-group-item:first-child {
        border-top: none;
    }
    
    .list-group-item:last-child {
        border-bottom: none;
    }
    
    .form-group label {
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .form-control {
        border-radius: 4px;
    }
    
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .border-right {
        border-right: 1px solid #dee2e6;
    }
    
    @media (max-width: 768px) {
        .border-right {
            border-right: none;
            border-bottom: 1px solid #dee2e6;
        }
    }
</style>
@endpush

@push('js')
<script>
    $(document).ready(function() {
        // Auto-save functionality (optional)
        let autoSaveTimer;
        
        $('#settingsForm input, #settingsForm textarea, #settingsForm select').on('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Optional: Auto-save form
                // $('#settingsForm').submit();
            }, 3000);
        });
        
        // Show confirmation before leaving with unsaved changes
        let formChanged = false;
        
        $('#settingsForm input, #settingsForm textarea, #settingsForm select').on('change', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        $('#settingsForm').on('submit', function() {
            formChanged = false;
        });
    });
</script>
@endpush