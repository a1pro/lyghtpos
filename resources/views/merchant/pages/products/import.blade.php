@extends('merchant.layouts.main')

@section('title', 'Upload Products CSV')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('merchant.products.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label>Upload CSV</label>
                <input type="file" name="file" class="form-control" required>
            </div>

            <button class="btn btn-primary">
                Upload & Process
            </button>
        </form>
    </div>
</div>
@endsection
