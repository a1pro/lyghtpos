@extends('layouts.admin')

@section('title', 'User Management')
@section('content-header', 'User Management')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Filter --}}
<div class="card card-outline card-primary">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.merchants.index') }}">
            <div class="form-row align-items-end">
                <div class="form-group col-md-6">
                    <label for="role_id" class="text-uppercase text-muted small font-weight-bold">
                        <i class="fas fa-filter mr-1"></i> Filter by Role
                    </label>
                    <select name="role_id" id="role_id" class="form-control">
                        <option value="all" {{ $roleId == 'all' || !$roleId ? 'selected' : '' }}>All Users</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $roleId == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filter
                    </button>
                    @if($roleId && $roleId != 'all')
                        <a href="{{ route('admin.merchants.index') }}" class="btn btn-default">
                            <i class="fas fa-undo-alt"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Users Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover text-nowrap mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Cyclos</th>
                        <th>Merchant</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $roleName  = $user->role->name ?? 'N/A';
                            $roleLower = strtolower($roleName);

                            $roleBadge = [
                                'admin'        => 'badge-danger',
                                'merchant'     => 'badge-primary',
                                'customer'     => 'badge-success',
                                'trade member' => 'badge-warning',
                                'organization' => 'badge-info',
                                'favor member' => 'badge-secondary',
                            ][$roleLower] ?? 'badge-secondary';

                            $roleIcon = [
                                'admin'        => 'fa-shield-alt',
                                'merchant'     => 'fa-store',
                                'customer'     => 'fa-user',
                                'trade member' => 'fa-handshake',
                                'organization' => 'fa-building',
                                'favor member' => 'fa-star',
                            ][$roleLower] ?? 'fa-user';

                            $cyclosGroupName = $cyclosGroups[$user->id] ?? 'Unknown';
                            $cyclosIcon = $cyclosGroupName == 'Not Synced'
                                ? 'fa-sync-alt'
                                : ($cyclosGroupName == 'Unknown' ? 'fa-question-circle' : 'fa-users');
                        @endphp
                        <tr>
                            <td class="text-center text-muted font-weight-bold">#{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                            <td>
                                <span class="badge {{ $roleBadge }}">
                                    <i class="fas {{ $roleIcon }} mr-1"></i>{{ $roleName }}
                                </span>
                            </td>
                            <td class="font-weight-bold">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->phone)
                                    <span title="{{ $user->phone }}">{{ Str::limit($user->phone, 12) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->location)
                                    <span title="{{ $user->location }}">{{ Str::limit($user->location, 15) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <i class="fas {{ $cyclosIcon }} mr-1"></i>{{ $cyclosGroupName }}
                                </span>
                            </td>
                            <td>
                                @if($roleLower == 'customer')
                                    @if($user->merchant_id)
                                        @php
                                            $assignedMerchant = \App\Models\User::find($user->merchant_id);
                                        @endphp
                                        @if($assignedMerchant)
                                            <div class="text-muted small mb-1">
                                                <i class="fas fa-store text-success mr-1"></i>
                                                {{ Str::limit($assignedMerchant->business_name ?? $assignedMerchant->first_name . ' ' . $assignedMerchant->last_name, 15) }}
                                            </div>
                                            <a href="{{ route('admin.merchants.assign-merchant-form', $user->id) }}"
                                               class="btn btn-xs btn-outline-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Change
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('admin.merchants.assign-merchant-form', $user->id) }}"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-link"></i> Assign
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.merchants.edit-group', $user->id) }}"
                                   class="btn btn-sm btn-outline-info"
                                   title="Edit Cyclos Group">
                                    <i class="fas fa-users-cog"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No users found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
        <div class="card-footer clearfix">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Auto-submit the filter form when the role dropdown changes
        $('#role_id').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endsection