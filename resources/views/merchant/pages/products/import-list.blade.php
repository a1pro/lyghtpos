{{-- resources/views/merchant/pages/products/import-list.blade.php --}}
@extends('merchant.layouts.main')

@section('title', 'Import History')
@section('content-header', 'Import History')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Import History</h3>
        <div class="card-tools">
            <a href="{{ route('merchant.products.import.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> New Import
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Success/Failed</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports as $import)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $import->file_name }}</td>
                    <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        @switch($import->status)
                            @case('completed')
                                <span class="badge-success">Completed</span>
                                @break
                            @case('processing')
                                <span class="badge-warning">Processing</span>
                                @break
                            @case('partial')
                                <span class="badge-warning">Partial</span>
                                @break
                            @case('failed')
                                <span class="badge-danger">Failed</span>
                                @break
                            @default
                                <span class="badge-info">Pending</span>
                        @endswitch
                    </td>
                    <td width="200">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: {{ $import->getProgressPercentage() }}%">
                                {{ $import->getProgressPercentage() }}%
                            </div>
                        </div>
                    </td>
                    <td>
                        ✅ {{ number_format($import->success_rows) }} / 
                        ❌ {{ number_format($import->failed_rows) }}
                    </td>
                    <td>
                        <a href="{{ route('merchant.products.import.show', $import->cyclos_import_id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No import history found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-center">
            {{ $imports->links() }}
        </div>
    </div>
</div>
@endsection