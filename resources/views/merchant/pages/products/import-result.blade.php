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

<div class="card">
    <div class="card-body">

        {{-- IMPORT SUMMARY --}}
        <h4 class="mb-3">Import Summary</h4>

        <div class="row mb-4">
            <div class="col-md-4">
                <strong>File Name:</strong><br>
                {{ $data['fileName'] }}
            </div>

            <div class="col-md-2">
                <strong>Type:</strong><br>
                <span class="badge badge-info">{{ strtoupper($data['kind']) }}</span>
            </div>

            <div class="col-md-2">
                <strong>Status:</strong><br>
                @if($data['status'] == 'imported')
                <span class="badge-success">Imported</span>
                @else
                <span class="badge-warning">{{ $data['status'] }}</span>
                @endif
            </div>

            <div class="col-md-4">
                <strong>Uploaded By:</strong><br>
                {{ $data['by']['display'] }}
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <strong>Created At:</strong><br>
                {{ \Carbon\Carbon::parse($data['creationDate'])->format('Y-m-d H:i') }}
            </div>

            <div class="col-md-3">
                <strong>Processed At:</strong><br>
                {{ \Carbon\Carbon::parse($data['processingDate'])->format('Y-m-d H:i') }}
            </div>

            <div class="col-md-3">
                <strong>Archive Date:</strong><br>
                {{ \Carbon\Carbon::parse($data['archiveDate'])->format('Y-m-d') }}
            </div>

            <div class="col-md-3">
                <strong>Auto Process:</strong><br>
                {{ $data['processAutomatically'] ? 'Yes' : 'No' }}
            </div>
        </div>

        {{-- PROGRESS STATS --}}
        <h4 class="mb-3">Progress</h4>

        <div class="row mb-4">
            <div class="col-md-2">
                <div class="info-box">
                    <h5>Imported</h5>
                    <span>{{ $data['progress']['linesImported'] }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Errors</h5>
                    <span class="text-danger">{{ $data['progress']['linesImportError'] }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Validation</h5>
                    <span class="text-info">{{ $data['progress']['linesValidationError'] }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Skipped</h5>
                    <span>{{ $data['progress']['linesSkipped'] }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Ready</h5>
                    <span>{{ $data['progress']['linesReady'] }}</span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="info-box">
                    <h5>Progress</h5>
                    <span>{{ $data['progress']['progress'] * 100 }}%</span>
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
                @foreach($data['fields'] as $field)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $field['name'] }}</td>
                    <td><code>{{ $field['fieldName'] }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- IGNORED FIELDS --}}
        @if(!empty($data['ignoredFields']))
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
