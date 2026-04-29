@extends('merchant.layouts.main-single-page')

@section('title', 'Accept Agreements')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Required Agreements</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Please review and accept ALL the following agreements to continue using the platform.
                    </div>

                    <form id="agreementsForm" action="{{ route('merchant.agreements.accept') }}" method="POST">
                        @csrf

                        <div class="agreements-list">
                            @foreach($agreements as $agreement)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input agreement-checkbox" name="agreements[]" value="{{ $agreement['id'] }}" id="agreement_{{ $agreement['id'] }}">
                                        <label class="form-check-label" for="agreement_{{ $agreement['id'] }}">
                                            <strong>{{ $agreement['name'] }}</strong>
                                            @if(isset($agreement['required']) && $agreement['required'])
                                            <span class="badge badge-danger ml-2">Required</span>
                                            @endif
                                        </label>
                                        <button type="button" class="btn btn-link btn-sm view-agreement" data-id="{{ $agreement['id'] }}" data-name="{{ $agreement['name'] }}">
                                            <i class="fas fa-file-alt"></i> View Content
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" id="acceptBtn" disabled>
                                <i class="fas fa-check"></i> Accept All Agreements
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agreement Content Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" aria-labelledby="agreementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="agreementModalLabel">Agreement Content</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="agreementContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            // Get the total number of agreements - FIXED BLADE SYNTAX
            const totalAgreements = {{count($agreements)}};

            // Function to check if all agreements are checked
            function areAllAgreementsChecked() {
                const checkboxes = document.querySelectorAll('.agreement-checkbox');
                const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                return checkedCount === totalAgreements;
            }

            // Enable/disable accept button based on whether ALL agreements are checked
            function updateAcceptButton() {
                const acceptBtn = document.getElementById('acceptBtn');
                if (acceptBtn) {
                    const allChecked = areAllAgreementsChecked();
                    acceptBtn.disabled = !allChecked;

                    // Update button text based on state
                    if (allChecked) {
                        acceptBtn.innerHTML = '<i class="fas fa-check"></i> Accept All Agreements';
                        acceptBtn.classList.remove('btn-secondary');
                        acceptBtn.classList.add('btn-primary');
                    } else {
                        const checkedCount = document.querySelectorAll('.agreement-checkbox:checked').length;
                        acceptBtn.innerHTML = '<i class="fas fa-check"></i> Accept All Agreements (' + checkedCount + '/' + totalAgreements + ')';
                        acceptBtn.classList.remove('btn-primary');
                        acceptBtn.classList.add('btn-secondary');
                    }
                }
            }

            // Load agreement content
            function loadAgreementContent(agreementId, agreementName) {
                // Set modal title
                const modalTitle = document.getElementById('agreementModalLabel');
                if (modalTitle) {
                    modalTitle.textContent = agreementName;
                }

                // Reset content to loading state
                const contentContainer = document.getElementById('agreementContent');
                if (contentContainer) {
                    contentContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>';
                }

                // Build URL with agreement ID
                const url = window.location.origin + '/merchant/agreements/content/' + agreementId;

                // Fetch agreement content
                fetch(url, {
                        method: 'GET'
                        , headers: {
                            'Accept': 'application/json'
                            , 'X-Requested-With': 'XMLHttpRequest'
                            , 'Content-Type': 'application/json'
                        }
                        , credentials: 'same-origin'
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        const contentContainer = document.getElementById('agreementContent');
                        if (contentContainer) {
                            contentContainer.innerHTML = data.content || '<p class="text-muted">No content available for this agreement.</p>';
                        }
                    })
                    .catch(function(error) {
                        console.error('Error loading agreement:', error);
                        const contentContainer = document.getElementById('agreementContent');
                        if (contentContainer) {
                            contentContainer.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Failed to load agreement content. Please try again or contact support.</div>';
                        }
                    });
            }

            // Initialize checkboxes
            const checkboxes = document.querySelectorAll('.agreement-checkbox');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].addEventListener('change', updateAcceptButton);
            }

            // Initialize view buttons
            const viewButtons = document.querySelectorAll('.view-agreement');
            for (let i = 0; i < viewButtons.length; i++) {
                viewButtons[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    const agreementId = this.getAttribute('data-id');
                    const agreementName = this.getAttribute('data-name');
                    if (agreementId && agreementName) {
                        loadAgreementContent(agreementId, agreementName);
                        // Show modal using Bootstrap 5
                        const modalElement = document.getElementById('agreementModal');
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                });
            }

            // Fix close button functionality
            const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
            for (let i = 0; i < closeButtons.length; i++) {
                closeButtons[i].addEventListener('click', function(e) {
                    const modalElement = document.getElementById('agreementModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                });
            }

            // Also handle manual close with X button
            const modalCloseButtons = document.querySelectorAll('#agreementModal .close');
            for (let i = 0; i < modalCloseButtons.length; i++) {
                modalCloseButtons[i].addEventListener('click', function(e) {
                    const modalElement = document.getElementById('agreementModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                });
            }

            // Reset modal content when closed
            const agreementModal = document.getElementById('agreementModal');
            if (agreementModal) {
                agreementModal.addEventListener('hidden.bs.modal', function() {
                    const contentContainer = document.getElementById('agreementContent');
                    if (contentContainer) {
                        contentContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>';
                    }
                });
            }

            // Initial update of accept button
            updateAcceptButton();
        });
    })();

</script>
@endsection

