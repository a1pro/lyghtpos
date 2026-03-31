@extends('merchant.layouts.main')

@section('title', 'Import History')
@section('content-header', 'Import History')

@section('content')
<div class="card">
    <div class="card-body">

        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Date</th>
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
                        <a href="{{ route('merchant.products.import.show', $import->cyclos_import_id) }}" class="btn btn-sm btn-primary">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No import history found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-center">
            {{ $imports->links() }}
        </div>

    </div>
</div>
@endsection