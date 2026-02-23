@extends('layouts.admin')

@section('title', 'Governance Layers')

@section('content-header', 'Governance Layers')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('content-actions')
<a href="{{ route('admin.governance-layers.create') }}" class="btn btn-primary">
    {{ __('Create Governance Layer') }}
</a>
@endsection


@section('content')
<div class="card product-list">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Serial No.</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($governanceLayers as $governanceLayer)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$governanceLayer->name}}</td>
                    <td>{{Str::limit($governanceLayer->description, 100) ?? 'N/A'}}</td>
                    <td>
                        @if($governanceLayer->status == 'active')
                        <span class="badge badge-success text-capitalize">Active</span>
                        @elseif($governanceLayer->status == 'inactive')
                        <span class="badge badge-danger text-capitalize">Inactive</span>
                        @else
                        <span class="badge badge-light text-capitalize">{{$governanceLayer->status ?? 'N/A'}}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{route('admin.governance-layers.edit', $governanceLayer->id)}}" class="btn btn-primary">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $governanceLayer->id }})">
                            Delete
                        </button>
                        <form id="delete-form-{{ $governanceLayer->id }}" action="{{ route('admin.governance-layers.destroy', $governanceLayer) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center">
                        Nothing found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?'
            , text: "You won't be able to revert this!"
            , icon: 'warning'
            , showCancelButton: true
            , confirmButtonColor: '#d33'
            , cancelButtonColor: '#3085d6'
            , confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }

    @if(session('success'))
    Swal.fire({
        icon: 'success'
        , title: 'Success!'
        , text: '{{ session('
        success ') }}'
        , showConfirmButton: false
        , timer: 3000
    });
    @endif

</script>
@endsection

