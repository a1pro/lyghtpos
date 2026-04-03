@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content-header', 'User Management')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .btn-icon {
        padding: 5px 10px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .filter-form {
        display: flex;
        align-items: flex-end;
        gap: 15px;
        flex-wrap: wrap;
    }
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    .filter-group label {
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }
    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
    }
    .badge-role {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    /* Modern button styles */
    .btn-group-action {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    
    .btn-group-action:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
        color: white;
        transform: translateY(-1px);
    }
</style>
@endsection

@section('content')
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

<div class="card">
    <div class="card-body">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="{{ route('admin.merchants.index') }}" class="filter-form">
                <div class="filter-group">
                    <label for="role_id">Filter by Role:</label>
                    <select name="role_id" id="role_id" class="form-control">
                        <option value="all" {{ $roleId == 'all' || !$roleId ? 'selected' : '' }}>All Users</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $roleId == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                    @if($roleId && $roleId != 'all')
                        <a href="{{ route('admin.merchants.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    <strong>Total Users:</strong> {{ $users->count() }}
                    @if($roleId && $roleId != 'all')
                        @php
                            $selectedRole = $roles->firstWhere('id', $roleId);
                        @endphp
                        @if($selectedRole)
                            | <strong>Filtered by Role:</strong> {{ $selectedRole->name }}
                        @endif
                    @endif
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Business Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Website</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @php
                                $roleColors = [
                                    'Admin' => 'danger',
                                    'Merchant' => 'primary',
                                    'Trade Member' => 'success',
                                    'Organization' => 'info',
                                    'Favor Member' => 'warning',
                                    'customer' => 'secondary',
                                    'owner_member' => 'dark',
                                ];
                                $roleName = $user->role->name ?? 'N/A';
                                $color = $roleColors[$roleName] ?? 'secondary';
                            @endphp
                            <span class="badge-role badge badge-{{ $color }}">
                                {{ $roleName }}
                            </span>
                         </td>
                        <td>{{ $user->first_name . ' ' . $user->last_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->business_name ?? 'N/A' }}</td>
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                        <td>{{ $user->location ?? 'N/A' }}</td>
                        <td>
                            @if($user->website)
                                <a href="{{ $user->website }}" target="_blank">{{ Str::limit($user->website, 30) }}</a>
                            @else
                                N/A
                            @endif
                         </td>
                        <td>
                            @if($user->status)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                         </td>
                        <td>
                            <div class="action-buttons">
                                <!-- Change Password Button -->
                                <a href="{{ route('admin.merchants.change-password', $user->id) }}" 
                                   class="btn btn-sm btn-warning btn-icon" 
                                   title="Change Password">
                                    <i class="fas fa-key"></i>
                                    <span class="d-none d-md-inline">Password</span>
                                </a>
                                
                                <!-- Edit Cyclos Group Button -->
                                <a href="{{ url('/admin/merchants/' . $user->id . '/edit-group') }}" 
                                   class="btn btn-sm btn-info btn-icon" 
                                   title="Edit Cyclos Group">
                                    <i class="fas fa-users"></i>
                                    <span class="d-none d-md-inline">Group</span>
                                </a>
                            </div>
                         </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> No users found.
                            </div>
                         </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Auto-submit form when dropdown changes for better UX
        $('#role_id').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endsection