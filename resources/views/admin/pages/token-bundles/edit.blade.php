@extends('layouts.admin')

@section('title', 'Edit Token Bundle')
@section('content-header', 'Edit Token Bundle')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.token-bundles.update', $bundle->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="tokens">Number of CC Tokens</label>
                <input type="number" name="tokens" class="form-control @error('tokens') is-invalid @enderror" id="tokens" placeholder="e.g., 100" value="{{ old('tokens', $bundle->tokens) }}">
                @error('tokens')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>


            <div class="form-group">
                <label for="base_amount">Base Amount</label>
                <input type="number" name="base_amount" class="form-control @error('base_amount') is-invalid @enderror" id="base_amount" placeholder="Add Base Amount" value="{{ old('base_amount', $bundle->base_amount) }}">
                @error('base_amount')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="system_fee">System Fee</label>
                <input type="number" name="system_fee" class="form-control @error('system_fee') is-invalid @enderror" id="system_fee" placeholder="Add System Fee" value="{{ old('system_fee', $bundle->system_fee) }}">
                @error('system_fee')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="discounted" class="custom-control-input @error('discounted') is-invalid @enderror" id="discounted" value="1" {{ old('discounted', $bundle->discounted) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="discounted">Discounted</label>
                    @error('discounted')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                    <option value="active" {{ old('status', $bundle->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $bundle->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Update Bundle</button>
            <a href="{{ route('admin.token-bundles.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(document).ready(function() {
        bsCustomFileInput.init();
    });

</script>
@endsection