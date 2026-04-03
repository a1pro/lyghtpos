@extends('layouts.admin')

@section('title', 'User Management')

@section('content-header', 'User Management')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    /* Professional Color Scheme */
    :root {
        --primary: #4f46e5;
        --success: #059669;
        --danger: #dc2626;
        --warning: #d97706;
        --info: #2563eb;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    }
    
    /* Stats Cards */
    .stats-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--gray-200);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .stats-icon {
        font-size: 2rem;
        position: absolute;
        right: 20px;
        top: 20px;
        opacity: 0.1;
    }
    
    .stats-number {
        font-size: 1.875rem;
        font-weight: 800;
        color: var(--gray-800);
        margin-bottom: 4px;
    }
    
    .stats-label {
        color: var(--gray-500);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
    }
    
    .filter-form {
        display: flex;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 250px;
    }
    
    .filter-group label {
        margin-bottom: 8px;
        font-weight: 700;
        color: var(--gray-700);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }
    
    .filter-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--gray-300);
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--gray-700);
        background: white;
        cursor: pointer;
    }
    
    .filter-group select:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    
    .btn-primary {
        background: var(--primary);
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
        background: #4338ca;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: white;
        border: 1.5px solid var(--gray-300);
        color: var(--gray-700);
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-secondary:hover {
        background: var(--gray-50);
        border-color: var(--gray-400);
    }
    
    /* Table Styles - Clean & No Overlap */
    .table-container {
        background: white;
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }
    
    .users-table thead th {
        background: var(--gray-50);
        color: var(--gray-600);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 12px;
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
        text-align: left;
    }
    
    .users-table tbody td {
        padding: 16px 12px;
        border-bottom: 1px solid var(--gray-100);
        font-size: 0.875rem;
        color: var(--gray-700);
        vertical-align: middle;
    }
    
    .users-table tbody tr:hover {
        background: var(--gray-50);
    }
    
    /* Fixed Column Widths */
    .users-table th:nth-child(1),
    .users-table td:nth-child(1) {
        width: 60px;
        text-align: center;
    }
    
    .users-table th:nth-child(2),
    .users-table td:nth-child(2) {
        width: 110px;
    }
    
    .users-table th:nth-child(3),
    .users-table td:nth-child(3) {
        width: 140px;
    }
    
    .users-table th:nth-child(4),
    .users-table td:nth-child(4) {
        width: 200px;
    }
    
    .users-table th:nth-child(5),
    .users-table td:nth-child(5) {
        width: 120px;
    }
    
    .users-table th:nth-child(6),
    .users-table td:nth-child(6) {
        width: 130px;
    }
    
    .users-table th:nth-child(7),
    .users-table td:nth-child(7) {
        width: 100px;
    }
    
    .users-table th:nth-child(8),
    .users-table td:nth-child(8) {
        width: 80px;
    }
    
    .users-table th:nth-child(9),
    .users-table td:nth-child(9) {
        width: 120px;
    }
    
    .users-table th:nth-child(10),
    .users-table td:nth-child(10) {
        width: 160px;
    }
    
    .users-table th:nth-child(11),
    .users-table td:nth-child(11) {
        width: 90px;
    }
    
    .users-table th:nth-child(12),
    .users-table td:nth-child(12) {
        width: 100px;
        text-align: center;
    }
    
    /* Role Badge */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    
    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    
    .status-active {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .status-inactive {
        background: #fecaca;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    /* Cyclos Badge */
    .cyclos-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        white-space: nowrap;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-action:hover {
        transform: translateY(-1px);
    }
    
    .btn-password {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    
    .btn-group-edit {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    
    /* Merchant Assignment - Fixed Alignment */
    .merchant-assignment {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .assigned-merchant-box {
        background: var(--gray-50);
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid var(--gray-200);
        width: 100%;
        max-width: 140px;
    }
    
    .merchant-name-display {
        font-weight: 700;
        font-size: 0.8rem;
        color: var(--gray-700);
        margin-bottom: 6px;
        word-break: break-word;
        line-height: 1.3;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .merchant-name-display i {
        color: var(--success);
        font-size: 0.7rem;
        flex-shrink: 0;
    }
    
    .merchant-name-display span {
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    .btn-change-merchant {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 6px;
        background: white;
        color: var(--gray-700);
        border: 1px solid var(--gray-300);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    
    .btn-change-merchant:hover {
        background: var(--gray-100);
        border-color: var(--gray-400);
    }
    
    .btn-assign-merchant {
        font-size: 0.7rem;
        padding: 6px 12px;
        border-radius: 8px;
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .btn-assign-merchant:hover {
        background: #a7f3d0;
        transform: translateY(-1px);
    }
    
    /* Text Utilities */
    .text-muted {
        color: var(--gray-400) !important;
    }
    
    .font-bold {
        font-weight: 700;
    }
    
    .text-truncate {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
    }
    
    /* Alert */
    .alert-custom {
        border-radius: 12px;
        padding: 14px 20px;
        margin-bottom: 24px;
        border: none;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: var(--gray-300);
        margin-bottom: 16px;
    }
    
    /* Tooltip */
    [data-tooltip] {
        position: relative;
        cursor: help;
    }
    
    [data-tooltip]:before {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 5px 10px;
        background: var(--gray-800);
        color: white;
        font-size: 0.7rem;
        border-radius: 6px;
        white-space: nowrap;
        display: none;
        z-index: 10;
        margin-bottom: 5px;
    }
    
    [data-tooltip]:hover:before {
        display: block;
    }
    
    /* Responsive */
    @media (max-width: 1400px) {
        .users-table {
            min-width: 1200px;
        }
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-number">{{ $users->count() }}</div>
            <div class="stats-label">Total Users</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="stats-number">{{ $users->filter(function($user) { return strtolower($user->role->name ?? '') == 'merchant'; })->count() }}</div>
            <div class="stats-label">Total Merchants</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="stats-number">{{ $users->filter(function($user) { return strtolower($user->role->name ?? '') == 'customer'; })->count() }}</div>
            <div class="stats-label">Total Customers</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-number">{{ $users->where('status', 1)->count() }}</div>
            <div class="stats-label">Active Users</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" action="{{ route('admin.merchants.index') }}" class="filter-form">
        <div class="filter-group">
            <label for="role_id">
                <i class="fas fa-filter me-1"></i> FILTER BY ROLE
            </label>
            <select name="role_id" id="role_id">
                <option value="all" {{ $roleId == 'all' || !$roleId ? 'selected' : '' }}>All Users</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ $roleId == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions" style="display: flex; gap: 10px;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search"></i> Apply Filter
            </button>
            @if($roleId && $roleId != 'all')
                <a href="{{ route('admin.merchants.index') }}" class="btn-secondary">
                    <i class="fas fa-undo-alt"></i> Reset
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="table-container">
    <table class="users-table">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag"></i> #</th>
                <th><i class="fas fa-user-tag"></i> ROLE</th>
                <th><i class="fas fa-user"></i> NAME</th>
                <th><i class="fas fa-envelope"></i> EMAIL</th>
                <th><i class="fas fa-phone-alt"></i> PHONE</th>
                 
                <th><i class="fas fa-map-marker-alt"></i> LOCATION</th>
                 
                <th><i class="fas fa-users"></i> CYCLOS</th>
                <th><i class="fas fa-store"></i> MERCHANT</th>
               
                <th><i class="fas fa-cog"></i> ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            @php
                $roleName = $user->role->name ?? 'N/A';
                $roleLower = strtolower($roleName);
                
                $roleStyles = [
                    'admin' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'fa-shield-alt'],
                    'merchant' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-store'],
                    'customer' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-user'],
                    'trade member' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-handshake'],
                    'organization' => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'icon' => 'fa-building'],
                    'favor member' => ['bg' => '#fce7f3', 'color' => '#9d174d', 'icon' => 'fa-star'],
                ];
                $style = $roleStyles[$roleLower] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-user'];
                
                $cyclosGroupName = $cyclosGroups[$user->id] ?? 'Unknown';
                $cyclosIcon = $cyclosGroupName == 'Not Synced' ? 'fa-sync-alt' : ($cyclosGroupName == 'Unknown' ? 'fa-question-circle' : 'fa-users');
            @endphp
            <tr>
                <td class="font-bold text-muted" style="text-align: center;">#{{ $loop->iteration }}</td>
                
                <td>
                    <span class="role-badge" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                        <i class="fas {{ $style['icon'] }}"></i>
                        {{ $roleName }}
                    </span>
                </td>
                
                <td class="font-bold">{{ $user->first_name }} {{ $user->last_name }}</td>
                
                <td>{{ $user->email }}</td>
                
                <td>
                    @if($user->phone)
                        <span data-tooltip="{{ $user->phone }}">{{ Str::limit($user->phone, 12) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                
              
                
                <td>
                    @if($user->location)
                        <span data-tooltip="{{ $user->location }}">{{ Str::limit($user->location, 15) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                
                 
                
                <td>
                    <span class="cyclos-badge">
                        <i class="fas {{ $cyclosIcon }}"></i>
                        {{ $cyclosGroupName }}
                    </span>
                </td>
                
                <!-- Merchant Assignment Column - Fixed Alignment -->
                <td>
                    @if($roleLower == 'customer')
                        @if($user->merchant_id)
                            @php
                                $assignedMerchant = \App\Models\User::find($user->merchant_id);
                            @endphp
                            @if($assignedMerchant)
                                <div class="merchant-assignment">
                                    <div class="assigned-merchant-box">
                                        <div class="merchant-name-display">
                                            <i class="fas fa-store"></i>
                                            <span>{{ Str::limit($assignedMerchant->business_name ?? $assignedMerchant->first_name . ' ' . $assignedMerchant->last_name, 15) }}</span>
                                        </div>
                                        <button onclick="window.location.href='{{ route('admin.merchants.assign-merchant-form', $user->id) }}'" class="btn-change-merchant">
                                            <i class="fas fa-edit"></i> Change
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @else
                            <button onclick="window.location.href='{{ route('admin.merchants.assign-merchant-form', $user->id) }}'" class="btn-assign-merchant">
                                <i class="fas fa-link"></i> Assign
                            </button>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                
                
                
                <td>
                    <div class="action-buttons">
                        <button onclick="window.location.href='{{ route('admin.merchants.change-password', $user->id) }}'" 
                                class="btn-action btn-password" 
                                data-tooltip="Change Password">
                            <i class="fas fa-key"></i>
                        </button>
                        
                        <button onclick="window.location.href='{{ route('admin.merchants.edit-group', $user->id) }}'" 
                                class="btn-action btn-group-edit" 
                                data-tooltip="Edit Cyclos Group">
                            <i class="fas fa-users-cog"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No users found</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
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
        
        // Auto-submit form when dropdown changes
        $('#role_id').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endsection