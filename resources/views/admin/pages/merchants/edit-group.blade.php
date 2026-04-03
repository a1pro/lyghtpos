@extends('layouts.admin')

@section('title', 'Edit User Group')

@section('content-header', 'Edit User Group')

@section('css')
<style>
    .group-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .current-group {
        background: #e8f4fd;
        border-left: 4px solid #17a2b8;
    }
    .group-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    .group-kind {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
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

<div class="row">
    <div class="col-md-4">
        <!-- User Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User Information</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Name:</dt>
                    <dd class="col-sm-8">{{ $user->first_name }} {{ $user->last_name }}</dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>
                    
                    <dt class="col-sm-4">Role:</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-info">{{ $user->role->name ?? 'N/A' }}</span>
                    </dd>
                    
                    <dt class="col-sm-4">Cyclos ID:</dt>
                    <dd class="col-sm-8">
                        <code>{{ $user->cyclos_user_id ?? 'Not created yet' }}</code>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Current Group Card -->
        @if($cyclosGroupInfo && isset($cyclosGroupInfo['current_group']))
        <div class="card current-group">
            <div class="card-header">
                <h3 class="card-title">Current Cyclos Group</h3>
            </div>
            <div class="card-body text-center">
                <div class="group-badge badge badge-primary badge-lg" style="font-size: 16px; padding: 10px 20px;">
                    {{ $cyclosGroupInfo['current_group']['name'] ?? 'Unknown' }}
                </div>
                <div class="group-kind mt-2">
                    Internal Name: <code>{{ $cyclosGroupInfo['current_group']['internalName'] ?? 'N/A' }}</code><br>
                    Kind: {{ $cyclosGroupInfo['current_group']['kind'] ?? 'N/A' }}<br>
                    ID: <code>{{ $cyclosGroupInfo['current_group']['id'] ?? 'N/A' }}</code>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Status: {{ $cyclosGroupInfo['status'] ?? 'active' }}</small>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <div class="col-md-8">
        <!-- Change Group Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Cyclos Group</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if(!$user->cyclos_user_id)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This user does not have a Cyclos account yet. Please create the Cyclos account first before changing groups.
                    </div>
                @elseif(empty($availableGroups))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No available groups found. Please check Cyclos configuration.
                    </div>
                @else
                    <form id="changeGroupForm" action="{{ route('admin.merchants.update-group', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="group_id">Select New Group *</label>
                            <select name="group_id" id="group_id" class="form-control @error('group_id') is-invalid @enderror" required>
                                <option value="">-- Select a group --</option>
                                @foreach($availableGroups as $group)
                                    <option value="{{ $group['internalName'] ?? $group['id'] }}" 
                                        {{ (isset($cyclosGroupInfo['current_group']['internalName']) && $cyclosGroupInfo['current_group']['internalName'] == ($group['internalName'] ?? '')) ? 'disabled' : '' }}
                                    >
                                        {{ $group['name'] ?? 'Unnamed Group' }}
                                        @if(isset($group['kind']))
                                            ({{ ucfirst($group['kind']) }})
                                        @endif
                                        @if(isset($cyclosGroupInfo['current_group']['internalName']) && $cyclosGroupInfo['current_group']['internalName'] == ($group['internalName'] ?? ''))
                                            - Current Group
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                You can use either Group Name or Internal Name. The system will automatically detect the correct identifier.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Comment (Optional)</label>
                            <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" 
                                rows="3" placeholder="Add a reason for changing this user's group..."></textarea>
                            @error('comment')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                This comment will be recorded in Cyclos group change history.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn btn-primary" id="confirmGroupChange">
                                <i class="fas fa-save"></i> Change Group
                            </button>
                            <a href="{{ route('admin.merchants.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        
        <!-- Group History Card -->
        @if($cyclosGroupInfo && !empty($cyclosGroupInfo['history']))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Group Change History</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Group</th>
                            <th>Changed By</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cyclosGroupInfo['history'] as $history)
                        <tr>
                            <td>{{ isset($history['period']['begin']) ? \Carbon\Carbon::parse($history['period']['begin'])->format('Y-m-d H:i:s') : 'N/A' }}</td>
                            <td>
                                {{ $history['group']['name'] ?? 'Unknown' }}
                                @if(isset($history['period']['end']))
                                    <br><small class="text-muted">Until: {{ \Carbon\Carbon::parse($history['period']['end'])->format('Y-m-d H:i:s') }}</small>
                                @else
                                    <br><small class="text-success">Current</small>
                                @endif
                            </td>
                            <td>{{ $history['by']['display'] ?? 'System' }}</td>
                            <td>{{ $history['comment'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // SweetAlert confirmation for group change
        $('#confirmGroupChange').click(function(e) {
            e.preventDefault();
            
            const groupSelect = $('#group_id');
            const selectedGroup = groupSelect.find('option:selected');
            const selectedGroupName = selectedGroup.text().trim();
            const selectedGroupValue = groupSelect.val();
            const comment = $('#comment').val();
            
            // Validation
            if (!selectedGroupValue) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a group to change to.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Change User Group?',
                html: `
                    <div style="text-align: left;">
                        <p><strong>User:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
                        <p><strong>Current Group:</strong> 
                            <span class="badge badge-info">${'{{ $cyclosGroupInfo["current_group"]["name"] ?? "Unknown" }}'}</span>
                        </p>
                        <p><strong>New Group:</strong> 
                            <span class="badge badge-warning">${selectedGroupName}</span>
                        </p>
                        ${comment ? `<p><strong>Comment:</strong> ${comment}</p>` : ''}
                        <hr>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> 
                            <strong>Warning:</strong> This action will take effect immediately in Cyclos!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, change group!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Changing Group...',
                        text: 'Please wait while we update the user group in Cyclos.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    $('#changeGroupForm').submit();
                }
            });
        });
        
        // Optional: Add warning if user tries to change group without comment
        $('#group_id').change(function() {
            const comment = $('#comment').val();
            if (!comment) {
                // Just a subtle hint, not blocking
                $('#comment').addClass('border-warning');
                setTimeout(() => {
                    $('#comment').removeClass('border-warning');
                }, 3000);
            }
        });
    });
</script>
@endsection