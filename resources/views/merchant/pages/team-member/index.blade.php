{{-- resources/views/merchant/pages/team-member/index.blade.php --}}
@extends('merchant.layouts.main')

@section('title', 'Team Members')

@section('content-header', 'Team Members')

@section('content-actions')
<a href="{{ route('merchant.team-members.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Member
</a>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>S.NO</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teamMembers as $member)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    
                                                </div>
                                                <div>
                                                    <h5 class="font-size-14 mb-1">{{ $member->first_name }} {{ $member->last_name }}</h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $member->email }}</td>
                                        <td>
                                            @if($member->role_id == 9)
                                                <span class="badge bg-info">Manager</span>
                                            @else
                                                <span class="badge bg-success">Cashier</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($member->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $member->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('merchant.team-members.edit', $member->id) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('merchant.team-members.destroy', $member->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this team member?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <p class="mb-0">No team members found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $teamMembers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection