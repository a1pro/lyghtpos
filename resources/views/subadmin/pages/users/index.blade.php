@extends('subadmin.layouts.main')

@section('title', 'Users')

@section('content-header', 'Users')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('content')
<div class="card product-list">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Serial No.</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Business Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Website</th>
                    <th>Consent Language Authorizing</th>
                    <th>Communyti Enrollment</th>
                    <th>Advocate Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr>
                    <td>{{$user->id}}</td>
                    <td>{{$user->role->name}}</td>
                    <td>{{$user->first_name . ' ' . $user->last_name}}</td>
                    <td>{{$user->email}}</td>
                    <td>{{$user->business_name ?? 'N/A'}}</td>
                    <td>{{$user->phone ?? 'N/A'}}</td>
                    <td>{{$user->location ?? 'N/A'}}</td>
                    <td>{{$user->website ?? 'N/A'}}</td>
                    <td>{{$user->consent_language_authorizing ?? 'N/A'}}</td>
                    <td>{{$user->Communyti_enrollment ?? 'N/A'}}</td>
                    <td>{{$user->advocate_contact ?? 'N/A'}}</td>
                    <td>
                        <a href="{{route('subadmin.user.representation',$user->id)}}" class="btn btn-primary">View<i class="fas fa-eye"></i></a>

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
@endsection
