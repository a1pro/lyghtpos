@extends('subadmin.layouts.main')

@section('title', 'Merchant Representation')

@section('content-header', 'Merchant Representation')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-approved { background-color: #d4edda; color: #155724; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }
    .status-hold { background-color: #d1ecf1; color: #0c5460; }
    
    .review-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }
    
    .review-history {
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .review-info {
        font-size: 14px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    #reviewMessageGroup {
        transition: all 0.3s ease;
    }

    .review-logs-section {
        margin-top: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }

    .compact-logs-list {
        background: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }

    .compact-log-item {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f3f4;
    }

    .compact-log-item:last-child {
        border-bottom: none;
    }

    .log-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .log-reviewer {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        width: 25%;
    }

    .log-status-change {
        width: 40%;
        text-align: center;
        color: #6c757d;
    }

    .log-status-change .badge {
        padding: 2px 8px;
        font-size: 11px;
        margin: 0 3px;
    }

    .log-time {
        width: 25%;
        text-align: right;
        font-size: 12px;
        color: #6c757d;
    }

    .log-message-row {
        font-size: 13px;
        color: #6c757d;
        padding-left: 5px;
        display: flex;
        align-items: baseline;
    }

    .log-message-text {
        color: #495057;
    }

    /* Governance Layer Styles */
    .governance-layers-section .card {
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-top: 20px;
    }

    .governance-layers-section .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .governance-layers-section .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }

    .governance-layers-section .badge {
        font-size: 12px;
        padding: 6px 10px;
    }

    .governance-layers-section .btn-sm {
        padding: 4px 12px;
        font-size: 12px;
        margin-right: 5px;
    }

    .governance-layers-section .btn-sm:last-child {
        margin-right: 0;
    }

    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }

    .badge-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }

    .badge-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Action buttons container */
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    /* Status indicator */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        margin-left: 5px;
    }

    /* Review message tooltip */
    .review-message-tooltip {
        cursor: pointer;
        color: #6c757d;
        margin-left: 5px;
    }

    .review-message-tooltip:hover {
        color: #007bff;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card product-list">
            <div class="card-body">
                <!-- Current Status Display -->
                @if($representation)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Representation Details</h4>
                            @if($representation->status)
                                <span class="status-badge status-{{ $representation->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $representation->status === 'hold' ? 'Requested Changes' : $representation->status)) }}
                                    @if($representation->last_reviewed)
                                        <small class="ml-2">(Updated: {{ $representation->last_reviewed->format('M d, Y') }})</small>
                                    @endif
                                </span>
                            @endif
                        </div>
                        @if($representation->reviewer && $representation->last_reviewed)
                            <div class="review-info">
                                Last reviewed by: {{ $representation->reviewer->first_name ?? 'N/A' }} 
                                on {{ $representation->last_reviewed->format('M d, Y h:i A') }}
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Representation Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Serial No.</th>
                                <th>User</th>
                                <th>Subject</th>
                                <th>Purpose</th>
                                <th>Futuristic Plan</th>
                                <th>Pledge Value</th>
                                <th>Currency</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if($representation)
                            <tr>
                                <td>{{ $representation->id }}</td>
                                <td>{{ $representation->user->first_name }}</td>
                                <td>{{ $representation->subject ?? 'N/A' }}</td>
                                <td>{{ $representation->purpose ?? 'N/A' }}</td>
                                <td>{{ $representation->futuristic_plan ?? 'N/A' }}</td>
                                <td>{{ $representation->pledge_value ?? 'N/A' }}</td>
                                <td>{{ $representation->currency ?? 'N/A' }}</td>
                            </tr>
                            @else
                            <tr>
                                <td colspan="12" class="text-center">
                                    Nothing found
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Governance Layers Section -->
                @if($representation && isset($governanceLayers) && $governanceLayers->count() > 0)
                <div class="governance-layers-section">
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-layer-group mr-2"></i> 
                                    Governance Layer Approval
                                    @if($totalGovernanceCount > 0)
                                        <span class="badge badge-info ml-2">
                                            {{ $userGovernanceLayers->where('status', 'approved')->count() }}/{{ $totalGovernanceCount }} Approved
                                        </span>
                                    @endif
                                </h5>
                                @if($allGovernanceApproved)
                                    <span class="badge badge-success p-2">
                                        <i class="fas fa-check-circle mr-1"></i> All Governance Layers Approved
                                    </span>
                                @elseif($rejectedGovernanceCount > 0)
                                    <span class="badge badge-danger p-2">
                                        <i class="fas fa-times-circle mr-1"></i> {{ $rejectedGovernanceCount }} Layer(s) Rejected
                                    </span>
                                @else
                                    <span class="badge badge-warning p-2">
                                        <i class="fas fa-clock mr-1"></i> {{ $pendingGovernanceCount }} Layer(s) Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Layer Name</th>
                                            <th width="30%">Description</th>
                                            <th width="15%">Status</th>
                                            <th width="30%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($governanceLayers as $index => $layer)
                                            @php
                                                $userLayer = $userGovernanceLayers->get($layer->id);
                                                $layerStatus = $userLayer ? $userLayer->status : 'pending';
                                                $statusBadgeClass = $layerStatus === 'approved' ? 'success' : 
                                                               ($layerStatus === 'rejected' ? 'danger' : 'warning');
                                                $reviewMessage = $userLayer ? $userLayer->message : null;
                                                $reviewer = $userLayer && $userLayer->reviewer ? $userLayer->reviewer->first_name : null;
                                                $reviewedAt = $userLayer && $userLayer->reviewed_at ? $userLayer->reviewed_at->format('M d, Y H:i') : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $layer->name }}</td>
                                                <td>{{ $layer->description ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $statusBadgeClass }} p-2">
                                                        {{ ucfirst($layerStatus) }}
                                                    </span>
                                                    @if($reviewMessage)
                                                        <i class="fas fa-info-circle review-message-tooltip" 
                                                           data-toggle="tooltip" 
                                                           title="Message: {{ $reviewMessage }}&#10;Reviewed by: {{ $reviewer ?? 'N/A' }}&#10;Date: {{ $reviewedAt ?? 'N/A' }}"></i>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        @if($layerStatus === 'approved')
                                                            {{-- Approved - Can reject --}}
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger reject-governance" 
                                                                    data-user-id="{{ $representation->user_id }}"
                                                                    data-governance-id="{{ $layer->id }}"
                                                                    data-layer-name="{{ $layer->name }}"
                                                                    data-current-status="approved">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                            <span class="status-indicator text-success">
                                                                <i class="fas fa-check-circle"></i>
                                                            </span>
                                                        @elseif($layerStatus === 'rejected')
                                                            {{-- Rejected - Can approve --}}
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success approve-governance" 
                                                                    data-user-id="{{ $representation->user_id }}"
                                                                    data-governance-id="{{ $layer->id }}"
                                                                    data-layer-name="{{ $layer->name }}"
                                                                    data-current-status="rejected">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <span class="status-indicator text-danger">
                                                                <i class="fas fa-times-circle"></i>
                                                            </span>
                                                        @else
                                                            {{-- Pending - Can approve or reject --}}
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success approve-governance" 
                                                                    data-user-id="{{ $representation->user_id }}"
                                                                    data-governance-id="{{ $layer->id }}"
                                                                    data-layer-name="{{ $layer->name }}"
                                                                    data-current-status="pending">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger reject-governance" 
                                                                    data-user-id="{{ $representation->user_id }}"
                                                                    data-governance-id="{{ $layer->id }}"
                                                                    data-layer-name="{{ $layer->name }}"
                                                                    data-current-status="pending">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($pendingGovernanceCount > 0 && !$allGovernanceApproved)
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Note:</strong> Please approve all governance layers before updating the final representation status.
                                    @if($rejectedGovernanceCount > 0)
                                        <br>Some layers have been rejected. You can re-approve them if needed.
                                    @endif
                                </div>
                            @elseif($allGovernanceApproved)
                                <div class="alert alert-success mt-3 mb-0">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <strong>All governance layers approved!</strong> You can now proceed with the final representation review.
                                </div>
                            @elseif($rejectedGovernanceCount > 0)
                                <div class="alert alert-danger mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Some layers are rejected.</strong> You can re-approve them if the issues have been resolved.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Review Section -->
                @if($representation && $join!==0)
                <div class="review-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            @if($isReviewed)
                                Update Review
                            @else
                                Review Representation
                            @endif
                        </h5>
                        @if($isReviewed)
                            <span class="badge badge-info">Editing existing review</span>
                        @endif
                    </div>
                    
                    <form id="reviewForm" action="{{ route('subadmin.representations.submit-review', $representation) }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Select Action *</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="">Choose action...</option>
                                        <option value="approved" {{ $representation->status == 'approved' ? 'selected' : '' }}>
                                            Approve
                                        </option>
                                        <option value="rejected" {{ $representation->status == 'rejected' ? 'selected' : '' }}>
                                            Reject
                                        </option>
                                        <option value="hold" {{ $representation->status == 'hold' ? 'selected' : '' }}>
                                            Request Changes
                                        </option>
                                    </select>
                                    @if($isReviewed)
                                        <small class="text-muted">Current selection will update the existing review</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div id="reviewMessageGroup" class="form-group">
                                    <label for="review_message" id="reviewMessageLabel">Message</label>
                                    <textarea class="form-control" id="review_message" name="review_message" 
                                              rows="3" placeholder="Enter your message...">{{ $representation->review_message ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    @if($isReviewed)
                                        Update Review
                                    @else
                                        Submit Review
                                    @endif
                                </button>
                                <a href="{{ route('subadmin.merchants') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Merchants
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Review Details -->
                @if($isReviewed)
                <div class="review-history">
                    <h6><strong>Current Review Details:</strong></h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Status:</strong><br>
                            <span class="status-badge status-{{ $representation->status }}">
                                {{ ucfirst(str_replace('_', ' ', $representation->status === 'hold' ? 'Requested Changes' : $representation->status)) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Last Updated:</strong><br>
                            {{ $representation->last_reviewed ? $representation->last_reviewed->format('M d, Y h:i A') : 'N/A' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Reviewed By:</strong><br>
                            {{ $representation->reviewer->first_name ?? 'N/A' }}
                        </div>
                    </div>
                    @if($representation->review_message)
                    <div class="mt-3">
                        <strong>Current Message:</strong>
                        <p class="mb-0 mt-1 bg-light p-2 rounded">{{ $representation->review_message }}</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Review Logs Section -->
                @if($representation && $representation->reviewLogs->count() > 0)
                <div class="review-logs-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="fas fa-history mr-1"></i> Review History
                            <span class="badge badge-light border ml-1">{{ $representation->reviewLogs->count() }}</span>
                        </h6>
                    </div>

                    <div class="compact-logs-list">
                        @foreach($representation->reviewLogs->sortByDesc('created_at') as $log)
                        <div class="compact-log-item">
                            <div class="log-info-row">
                                <span class="log-reviewer">{{ $log->user->first_name ?? 'System' }}</span>
                                <span class="log-status-change">
                                    @php
                                    $beforeStatus = $log->before_status ?? 'new';
                                    if($beforeStatus === null) $beforeStatus = 'new';
                                    @endphp

                                    @if($beforeStatus === 'new')
                                    → <span class="badge status-badge status-{{ $log->after_status }}">{{ ucfirst($log->after_status) }}</span>
                                    @else
                                    <span class="badge status-badge status-{{ $beforeStatus }}">{{ ucfirst($beforeStatus) }}</span>
                                    → <span class="badge status-badge status-{{ $log->after_status }}">{{ ucfirst($log->after_status) }}</span>
                                    @endif
                                </span>
                                <span class="log-time">{{ $log->created_at->format('M d, H:i') }}</span>
                            </div>

                            @if($log->message)
                            <div class="log-message-row">
                                <small class="text-muted mr-1">Note:</small>
                                <span class="log-message-text">{{ Str::limit($log->message, 80) }}</span>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@if($representation)
<script>
    $(document).ready(function() {
        // Function to handle status change in review form
        function handleStatusChange(statusValue = null) {
            const status = statusValue || $('#status').val();
            const messageGroup = $('#reviewMessageGroup');
            const messageLabel = $('#reviewMessageLabel');
            const messageField = $('#review_message');

            if (status === 'rejected' || status === 'hold') {
                messageGroup.show();
                
                if (status === 'rejected') {
                    messageLabel.text('Rejection Reason *');
                    messageField.attr('placeholder', 'Please provide the reason for rejection...');
                } else {
                    messageLabel.text('Change Request Details *');
                    messageField.attr('placeholder', 'Please specify what changes are required...');
                }
                
                messageField.prop('required', true);
            } else {
                messageGroup.show();
                messageLabel.text('Additional Comments (Optional)');
                messageField.attr('placeholder', 'You can add optional comments for approval...');
                messageField.prop('required', false);
            }
        }

        // Initialize review form
        const currentStatus = "{{ $representation->status }}";
        
        if (currentStatus) {
            $('#status').val(currentStatus);
            handleStatusChange(currentStatus);
            $('button[type="submit"]').html('<i class="fas fa-save"></i> Update Review');
        } else {
            $('#reviewMessageGroup').hide();
        }
        
        // Review form status change handler
        $('#status').change(function() {
            handleStatusChange();
        });

        // Governance Layer Approval Handling
        // Approve governance layer (works for pending and rejected)
        $('.approve-governance').click(function() {
            const userId = $(this).data('user-id');
            const governanceId = $(this).data('governance-id');
            const layerName = $(this).data('layer-name');
            const currentStatus = $(this).data('current-status');
            
            let message = '';
            let title = 'Approve Governance Layer';
            let html = `Are you sure you want to approve <strong>${layerName}</strong>?`;
            let inputRequired = false;
            
            if (currentStatus === 'rejected') {
                html = `This layer was previously rejected. Are you sure you want to re-approve <strong>${layerName}</strong>?`;
            }
            
            Swal.fire({
                title: title,
                html: html,
                icon: 'question',
                input: 'textarea',
                inputPlaceholder: 'Enter approval message (optional)...',
                inputAttributes: {
                    'aria-label': 'Approval message'
                },
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitGovernanceAction(userId, governanceId, 'approved', result.value || '');
                }
            });
        });
        
        // Reject governance layer (works for pending and approved)
        $('.reject-governance').click(function() {
            const userId = $(this).data('user-id');
            const governanceId = $(this).data('governance-id');
            const layerName = $(this).data('layer-name');
            const currentStatus = $(this).data('current-status');
            
            let title = 'Reject Governance Layer';
            let html = `Please provide a reason for rejecting <strong>${layerName}</strong>:`;
            
            if (currentStatus === 'approved') {
                html = `This layer was previously approved. Please provide a reason for re-rejecting <strong>${layerName}</strong>:`;
            }
            
            Swal.fire({
                title: title,
                html: html,
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Enter rejection reason...',
                inputAttributes: {
                    'aria-label': 'Rejection reason'
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reject it!',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Rejection reason is required!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitGovernanceAction(userId, governanceId, 'rejected', result.value);
                }
            });
        });
        
        function submitGovernanceAction(userId, governanceId, status, message) {
            const url = `{{ url('subadmin/users') }}/${userId}/governance/${governanceId}/update`;
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('status', status);
            formData.append('review_message', message);
            
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we update the governance layer.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Something went wrong.',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response?.message || 'Failed to update governance layer.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        // Review form submission handler
        $('#reviewForm').submit(function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            
            const status = $('#status').val();
            const reviewMessage = $('#review_message').val().trim();
            
            // Validate form
            if (!status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Action Required',
                    text: 'Please select an action from the dropdown.',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if ((status === 'rejected' || status === 'hold') && !reviewMessage) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Message Required',
                    text: 'Please provide a message for ' + (status === 'rejected' ? 'rejection' : 'change request') + '.',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Check governance layers if trying to approve
            @if(isset($totalGovernanceCount) && $totalGovernanceCount > 0)
            if (status === 'approved') {
                const allApproved = {{ $allGovernanceApproved ? 'true' : 'false' }};
                
                if (!allApproved) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Approve',
                        html: 'Please approve all governance layers before final approval.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            }
            @endif
            
            const actionText = currentStatus ? 'update' : 'submit';
            const statusText = status === 'approved' ? 'approve' : 
                             status === 'rejected' ? 'reject' : 'request changes';
            
            Swal.fire({
                title: 'Confirm Review',
                html: `Are you sure you want to <strong>${actionText}</strong> this review as <strong>${statusText}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Yes, ${actionText} it!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitReviewForm(form, submitBtn, originalBtnText, actionText);
                }
            });
        });

        function submitReviewForm(form, submitBtn, originalBtnText, actionText) {
            submitBtn.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin"></i> ${actionText === 'update' ? 'Updating...' : 'Submitting...'}`);
            
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Something went wrong.',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    let errorMessage = 'Something went wrong. Please try again.';
                    
                    if (response && response.errors) {
                        errorMessage = Object.values(response.errors).flat().join('<br>');
                    } else if (response && response.message) {
                        errorMessage = response.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        }

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endif
@endsection