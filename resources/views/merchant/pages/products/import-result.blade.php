{{-- resources/views/merchant/pages/products/import-result.blade.php --}}
@extends('merchant.layouts.main')

@section('title', 'Import Result')
@section('content-header', 'Import Result')

@section('css')
<style>
    .badge-success {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .badge-warning {
        background-color: #ffc107;
        color: black;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .badge-danger {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .badge-info {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
    }
    .info-box {
        padding: 15px;
        border-radius: 6px;
        background: #f8f9fa;
        margin-bottom: 10px;
        text-align: center;
    }
    .info-box h5 {
        margin: 0;
        font-weight: bold;
    }
    .info-box span {
        font-size: 18px;
    }
    .progress {
        height: 25px;
        margin-bottom: 20px;
    }
    .progress-bar {
        line-height: 25px;
        font-size: 12px;
    }
</style>
@endsection

@section('content')

@php
// Define the cyclosImportId from the data array
$cyclosImportId = $data['id'] ?? $data['cyclos_import_id'] ?? ($import->cyclos_import_id ?? null);
$progress = $data['progress'] ?? [];
$canCancel = $data['can_cancel'] ?? false;
$importId = $data['import_id'] ?? ($import->id ?? null);
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="mb-3">
            <a href="{{ route('merchant.products.import.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to History
            </a>
            <a href="{{ route('merchant.products.import.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> New Import
            </a>
            @if($canCancel)
            <button type="button" class="btn btn-danger" id="cancelBtn">
                <i class="fas fa-times"></i> Cancel Import
            </button>
            @endif
            <button type="button" class="btn btn-info" id="refreshBtn">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">

        {{-- IMPORT SUMMARY --}}
        <h4 class="mb-3">Import Summary</h4>

        <div class="row mb-4">
            <div class="col-md-4">
                <strong>File Name:</strong><br>
                {{ $data['fileName'] ?? ($import->file_name ?? 'N/A') }}
            </div>

            <div class="col-md-2">
                <strong>Type:</strong><br>
                <span class="badge badge-info">
                    {{ strtoupper($data['kind'] ?? 'PRODUCT') }}
                </span>
            </div>

            <div class="col-md-2">
                <strong>Status:</strong><br>
                <span id="statusBadge">
                    @if(($data['local_status'] ?? '') === 'processing')
                        <span class="badge-warning">Processing</span>
                    @elseif(($data['local_status'] ?? '') === 'completed')
                        <span class="badge-success">Completed</span>
                    @elseif(($data['local_status'] ?? '') === 'partial')
                        <span class="badge-warning">Partial</span>
                    @elseif(($data['local_status'] ?? '') === 'failed')
                        <span class="badge-danger">Failed</span>
                    @elseif(($data['status'] ?? '') === 'imported')
                        <span class="badge-success">Imported</span>
                    @elseif(!empty($data['status']))
                        <span class="badge-warning">{{ $data['status'] }}</span>
                    @else
                        <span class="badge-danger">Unknown</span>
                    @endif
                </span>
            </div>

            <div class="col-md-2">
                <strong>Started At:</strong><br>
                <span id="startedAt">
                    {{ !empty($import->started_at) ? $import->started_at->format('Y-m-d H:i:s') : 'Pending' }}
                </span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <strong>Created At:</strong><br>
                {{ !empty($data['creationDate']) ? \Carbon\Carbon::parse($data['creationDate'])->format('Y-m-d H:i') : ($import->created_at->format('Y-m-d H:i') ?? 'N/A') }}
            </div>

            <div class="col-md-3">
                <strong>Processed At:</strong><br>
                {{ !empty($data['processingDate']) ? \Carbon\Carbon::parse($data['processingDate'])->format('Y-m-d H:i') : 'N/A' }}
            </div>

            <div class="col-md-3">
                <strong>Estimated Completion:</strong><br>
                <span id="estCompletion">{{ $data['estimated_completion'] ?? 'Calculating...' }}</span>
            </div>

            <div class="col-md-3">
                <strong>Time Remaining:</strong><br>
                <span id="timeRemaining">{{ $data['estimated_time_remaining'] ?? 'Calculating...' }}</span>
            </div>
        </div>

        {{-- PROGRESS BAR --}}
        <div class="mb-4">
            <div class="progress-group">
                <span class="progress-text">Overall Progress</span>
                <span class="progress-number" id="progressPercent">{{ ($progress['progress'] ?? 0) * 100 }}%</span>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="progressBar" 
                         style="width: {{ ($progress['progress'] ?? 0) * 100 }}%">
                        <span id="progressText">{{ $data['local_processed'] ?? 0 }}/{{ $data['local_total'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- PROGRESS STATS --}}
        <h4 class="mb-3">Progress Statistics</h4>

        <div class="row mb-4">
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Processed</h5>
                    <span id="processedCount">{{ $data['local_processed'] ?? 0 }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Successful</h5>
                    <span class="text-success" id="successCount">{{ $data['local_success'] ?? 0 }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Failed</h5>
                    <span class="text-danger" id="failedCount">{{ $data['local_failed'] ?? 0 }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Validation Errors</h5>
                    <span class="text-info" id="validationErrors">{{ $progress['linesValidationError'] ?? 0 }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <h5>API Errors</h5>
                    <span class="text-warning" id="apiErrors">0</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Duplicates</h5>
                    <span class="text-secondary" id="duplicates">{{ $progress['linesSkipped'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- FIELDS TABLE --}}
        <h4 class="mb-3">Mapped Fields</h4>

        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Field Key</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['fields'] ?? [] as $field)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $field['name'] ?? 'N/A' }}</td>
                    <td><code>{{ $field['fieldName'] ?? 'N/A' }}</code></td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center">No fields available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- IGNORED FIELDS --}}
        @if(!empty($data['ignoredFields'] ?? []))
        <h4 class="mt-4">Ignored Fields</h4>
        <div class="mb-4">
            @foreach($data['ignoredFields'] as $field)
            <span class="badge badge-danger">{{ $field }}</span>
            @endforeach
        </div>
        @endif

        {{-- FAILED PRODUCTS TABLE --}}
        @if(isset($failures) && $failures->count() > 0)
        <h4 class="mt-4">Failed Products</h4>
        <div class="mb-3">
            <button type="button" class="btn btn-sm btn-primary" id="retryFailedBtn">
                <i class="fas fa-redo"></i> Retry Failed
            </button>
            @if($cyclosImportId)
            <a href="{{ route('merchant.products.import.download-errors', $cyclosImportId) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-download"></i> Download Error Report
            </a>
            @endif
        </div>
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Row</th>
                    <th>Product Name</th>
                    <th>Error Type</th>
                    <th>Error Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach($failures as $failure)
                <tr>
                    <td>{{ $failure->row_number }}</td>
                    <td>{{ $failure->product_name }}</td>
                    <td>
                        <span class="badge 
                            @switch($failure->error_type)
                                @case('validation') badge-danger @break
                                @case('api') badge-warning @break
                                @case('network') badge-info @break
                                @case('duplicate') badge-secondary @break
                                @default badge-dark
                            @endswitch">
                            {{ ucfirst($failure->error_type) }}
                        </span>
                    </td>
                    <td>{{ $failure->error_message }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            {{ $failures->links() }}
        </div>
        @endif

    </div>
</div>

@if(($data['local_status'] ?? '') === 'processing')
<script>
    // Real-time progress updates
    let updateInterval;
    const cyclosImportId = '{{ $cyclosImportId }}';
    const progressUrl = '{{ route("merchant.products.import.progress", ":id") }}'.replace(':id', cyclosImportId);
    
    function updateProgress() {
        if (!cyclosImportId) return;
        
        fetch(progressUrl)
            .then(response => response.json())
            .then(data => {
                // Update progress bar
                const percentage = data.percentage || 0;
                document.getElementById('progressBar').style.width = percentage + '%';
                document.getElementById('progressPercent').textContent = percentage + '%';
                document.getElementById('progressText').textContent = data.processed + '/' + data.total;
                
                // Update counts
                document.getElementById('processedCount').textContent = data.processed.toLocaleString();
                document.getElementById('successCount').textContent = data.success.toLocaleString();
                document.getElementById('failedCount').textContent = data.failed.toLocaleString();
                document.getElementById('validationErrors').textContent = data.validation_errors || 0;
                document.getElementById('apiErrors').textContent = data.api_errors || 0;
                document.getElementById('duplicates').textContent = data.duplicates || 0;
                
                // Update time estimates
                if (data.estimated_time_remaining) {
                    document.getElementById('timeRemaining').textContent = data.estimated_time_remaining;
                }
                if (data.estimated_completion) {
                    document.getElementById('estCompletion').textContent = data.estimated_completion;
                }
                
                // Update status badge
                const statusBadge = document.getElementById('statusBadge');
                if (data.status === 'processing') {
                    statusBadge.innerHTML = '<span class="badge-warning">Processing</span>';
                } else if (data.status === 'completed') {
                    statusBadge.innerHTML = '<span class="badge-success">Completed</span>';
                    if (updateInterval) clearInterval(updateInterval);
                    setTimeout(() => location.reload(), 2000);
                } else if (data.status === 'partial') {
                    statusBadge.innerHTML = '<span class="badge-warning">Partial</span>';
                    if (updateInterval) clearInterval(updateInterval);
                } else if (data.status === 'failed') {
                    statusBadge.innerHTML = '<span class="badge-danger">Failed</span>';
                    if (updateInterval) clearInterval(updateInterval);
                }
            })
            .catch(error => console.error('Error fetching progress:', error));
    }
    
    // Cancel import
    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        if (confirm('Cancel this import? This cannot be undone.')) {
            const cancelUrl = '{{ route("merchant.products.import.cancel", ":id") }}'.replace(':id', cyclosImportId);
            fetch(cancelUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to cancel import');
                }
            });
        }
    });
    
    // Retry failed products
    document.getElementById('retryFailedBtn')?.addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Retrying...';
        
        const retryUrl = '{{ route("merchant.products.import.retry", ":id") }}'.replace(':id', cyclosImportId);
        fetch(retryUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then(response => response.json()).then(data => {
            if (data.success) {
                alert(data.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo"></i> Retry Failed';
            }
        }).catch(error => {
            alert('Error retrying failed products');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo"></i> Retry Failed';
        });
    });
    
    // Refresh button
    document.getElementById('refreshBtn').addEventListener('click', function() {
        updateProgress();
    });
    
    // Start polling every 5 seconds
    if (cyclosImportId) {
        updateProgress();
        updateInterval = setInterval(updateProgress, 5000);
    }
</script>
@endif
@endsection