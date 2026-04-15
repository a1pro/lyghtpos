{{-- resources/views/merchant/pages/products/import.blade.php --}}
@extends('merchant.layouts.main')

@section('title', 'Upload Products CSV')
@section('content-header', 'Upload Products CSV')

@section('content')

@if(isset($activeImport) && $activeImport)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>You have an active import!</strong><br>
    File: {{ $activeImport->file_name }} | 
    Progress: {{ $activeImport->getProgressPercentage() }}% |
    <a href="{{ route('merchant.products.import.show', $activeImport->cyclos_import_id) }}">View Progress</a>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <strong><i class="fas fa-info-circle"></i> CSV Format Requirements:</strong>
            <ul class="mt-2">
                <li><strong>Required columns:</strong> <code>name</code>, <code>price</code></li>
                <li><strong>Optional columns:</strong> <code>description</code>, <code>stockQuantity</code>, <code>barcode</code></li>
                <li><strong>Maximum products:</strong> 5,000 per file</li>
                <li><strong>File size limit:</strong> 20MB</li>
            </ul>
        </div>
        
        <div class="alert alert-secondary">
            <strong><i class="fas fa-clock"></i> Estimated Processing Time:</strong>
            <ul class="mt-2">
                <li>500 products: ~20 minutes</li>
                <li>1,000 products: ~40 minutes</li>
                <li>4,000 products: ~2.8 hours</li>
                <li>5,000 products: ~3.5 hours</li>
            </ul>
        </div>
        
        <form action="{{ route('merchant.products.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
            @csrf
            
            <div class="form-group">
                <label>Upload CSV</label>
                <input type="file" name="file" class="form-control-file" accept=".csv" required>
                <small class="form-text text-muted">CSV file with product data (max 5,000 rows)</small>
            </div>
            
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="confirmEstimate" required>
                    <label class="custom-control-label" for="confirmEstimate">
                        I understand this import will take several hours to complete
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                <i class="fas fa-upload"></i> Upload & Process
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('confirmEstimate').addEventListener('change', function(e) {
        document.getElementById('submitBtn').disabled = !e.target.checked;
    });
    
    document.getElementById('importForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
</script>
@endsection