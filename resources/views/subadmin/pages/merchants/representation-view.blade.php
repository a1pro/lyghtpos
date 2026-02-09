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

                <!-- Review Section -->
                @if($representation)
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
                                    <small class="form-text text-muted">
                                        This message will be visible to the merchant.
                                        @if($representation->review_message)
                                            <span class="text-info">Editing existing message.</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                        Update Review
                                </button>
                                <a href="{{ route('subadmin.merchants') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Merchants
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Review History -->
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
        // Function to handle status change
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
                messageGroup.show(); // Always show for approved too
                messageLabel.text('Additional Comments (Optional)');
                messageField.attr('placeholder', 'You can add optional comments for approval...');
                messageField.prop('required', false);
            }
        }

        // Initialize on page load with current status
        const currentStatus = "{{ $representation->status }}";
        
        if (currentStatus) {
            // Pre-select the dropdown
            $('#status').val(currentStatus);
            
            // Handle UI based on current status
            handleStatusChange(currentStatus);
            
            // Update button text for edit mode
            $('button[type="submit"]').html('<i class="fas fa-save"></i> Update Review');
        } else {
            // For new reviews, hide message group by default
            $('#reviewMessageGroup').hide();
        }
        
        // Run when dropdown changes
        $('#status').change(function() {
            handleStatusChange();
        });

        // Handle form submission
        $('#reviewForm').submit(function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            
            // Validate form
            const status = $('#status').val();
            const reviewMessage = $('#review_message').val().trim();
            
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
            
            // Show appropriate confirmation message
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
                    submitForm(form, submitBtn, originalBtnText, actionText);
                }
            });
        });

        function submitForm(form, submitBtn, originalBtnText, actionText) {
            // Show loading state
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
    });
</script>
@endif

@endsection