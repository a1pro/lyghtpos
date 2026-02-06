@extends('subadmin.layouts.main')

@section('title', 'User Representation')

@section('content-header', 'User Representation')

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
                    <th>User</th>
                    <th>Subject</th>
                    <th>Purpose</th>
                    <th>Futuristic Plan</th>
                    <th>Pledge Value</th>
                    <th>Currency</th>
                </tr>
            </thead>
            <tbody>
                @if($representation)
                <tr>
                    <td>{{$representation->id}}</td>
                    <td>{{$representation->user->first_name}}</td>
                    <td>{{$representation->subject ?? 'N/A'}}</td>
                    <td>{{$representation->purpose ?? 'N/A'}}</td>
                    <td>{{$representation->futuristic_plan ?? 'N/A'}}</td>
                    <td>{{$representation->pledge_value ?? 'N/A'}}</td>
                    <td>{{$representation->currency ?? 'N/A'}}</td>
                </tr>
                @else
                <tr>
                    <td colspan="7" class="text-center">Nothing found</td>
                </tr>
                @endif

            </tbody>
        </table>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection
