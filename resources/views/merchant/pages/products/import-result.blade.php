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

</style>
@endsection

@section('content')

@php
$progress = $data['progress'] ?? [];
@endphp

<div class="card">
    <div class="card-body">

        {{-- IMPORT SUMMARY --}}
        <h4 class="mb-3">Import Summary</h4>

        <div class="row mb-4">
            <div class="col-md-4">
                <strong>File Name:</strong><br>
                {{ $data['fileName'] ?? 'N/A' }}
            </div>

            <div class="col-md-2">
                <strong>Type:</strong><br>
                <span class="badge badge-info">
                    {{ strtoupper($data['kind'] ?? 'N/A') }}
                </span>
            </div>

            <div class="col-md-2">
                <strong>Status:</strong><br>
                @if(($data['status'] ?? '') === 'imported')
                <span class="badge-success">Imported</span>
                @elseif(!empty($data['status']))
                <span class="badge-warning">{{ $data['status'] }}</span>
                @else
                <span class="badge-danger">Unknown</span>
                @endif
            </div>

        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <strong>Created At:</strong><br>
                {{ !empty($data['creationDate']) ? \Carbon\Carbon::parse($data['creationDate'])->format('Y-m-d H:i') : 'N/A' }}
            </div>

            <div class="col-md-3">
                <strong>Processed At:</strong><br>
                {{ !empty($data['processingDate']) ? \Carbon\Carbon::parse($data['processingDate'])->format('Y-m-d H:i') : 'N/A' }}
            </div>

            <div class="col-md-3">
                <strong>Archive Date:</strong><br>
                {{ !empty($data['archiveDate']) ? \Carbon\Carbon::parse($data['archiveDate'])->format('Y-m-d') : 'N/A' }}
            </div>

            <div class="col-md-3">
                <strong>Auto Process:</strong><br>
                {{ isset($data['processAutomatically']) ? ($data['processAutomatically'] ? 'Yes' : 'No') : 'N/A' }}
            </div>
        </div>

        {{-- PROGRESS STATS --}}
        <h4 class="mb-3">Progress</h4>

        <div class="row mb-4">
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Imported</h5>
                    <span>{{ $progress['linesImported'] ?? 0 }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Errors</h5>
                    <span class="text-danger">{{ $progress['linesImportError'] ?? 0 }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Validation</h5>
                    <span class="text-info">{{ $progress['linesValidationError'] ?? 0 }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Skipped</h5>
                    <span>{{ $progress['linesSkipped'] ?? 0 }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Ready</h5>
                    <span>{{ $progress['linesReady'] ?? 0 }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Progress</h5>
                    <span>
                        {{ isset($progress['progress']) ? ($progress['progress'] * 100) . '%' : '0%' }}
                    </span>
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

    </div>
</div>

@endsection