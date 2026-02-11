@extends('subadmin.layouts.main')

@section('title', 'Merchants')

@section('content-header', 'Merchants')

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
                @forelse ($merchants as $merchant)
                <tr>
                    <td>{{$merchant->id}}</td>
                    <td>{{$merchant->role->name}}</td>
                    <td>{{$merchant->first_name . ' ' . $merchant->last_name}}</td>
                    <td>{{$merchant->email}}</td>
                    <td>{{$merchant->business_name ?? 'N/A'}}</td>
                    <td>{{$merchant->phone ?? 'N/A'}}</td>
                    <td>{{$merchant->location ?? 'N/A'}}</td>
                    <td>{{$merchant->website ?? 'N/A'}}</td>
                    <td>{{$merchant->consent_language_authorizing ?? 'N/A'}}</td>
                    <td>{{$merchant->communyti_enrollment ?? 'N/A'}}</td>
                    <td>{{$merchant->advocate_contact ?? 'N/A'}}</td>
                    <td>
                        <a href="{{route('subadmin.representations.view', $merchant->id)}}" class="btn btn-primary">View<i class="fas fa-eye"></i></a>
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
