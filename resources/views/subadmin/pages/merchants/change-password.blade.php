@extends('subadmin.layouts.main')

@section('title', 'Change Merchant Password')

@section('content-header', 'Change Password')

@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .password-requirements {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }
    .password-requirements ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Password for: {{ $merchant->first_name }} {{ $merchant->last_name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('subadmin.merchants') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <p><strong>Email:</strong> {{ $merchant->email }}</p>
                    <p><strong>Business Name:</strong> {{ $merchant->business_name ?? 'N/A' }}</p>
                </div>

                <form action="{{ route('subadmin.merchants.update-password', $merchant->id) }}" method="POST" id="passwordForm">
                    @csrf
                    
                    <div class="form-group">
                        <label for="password">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   minlength="8"
                                   placeholder="Enter new password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="password-requirements">
                            <ul>
                                <li>Minimum 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one lowercase letter</li>
                                <li>At least one number</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   minlength="8"
                                   placeholder="Confirm new password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> Changing the password will immediately affect the merchant's ability to log in. Make sure to communicate the new password to them securely.
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                        <a href="{{ route('subadmin.merchants') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordField = $('#password');
            const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
            passwordField.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        $('#toggleConfirmPassword').click(function() {
            const confirmField = $('#password_confirmation');
            const type = confirmField.attr('type') === 'password' ? 'text' : 'password';
            confirmField.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        // Form validation
        $('#passwordForm').submit(function(e) {
            const password = $('#password').val();
            const confirm = $('#password_confirmation').val();
            
            if (password !== confirm) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'The password and confirmation password do not match.',
                });
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Password',
                    text: 'Password must be at least 8 characters long.',
                });
                return false;
            }
            
            // Optional: Add more password strength validation
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Weak Password',
                    text: 'Password must contain uppercase, lowercase letters and numbers.',
                });
                return false;
            }
        });

        // Confirmation dialog before form submission
        $('#submitBtn').click(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will change the merchant's password immediately!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#passwordForm').submit();
                }
            });
        });
    });
</script>
@endsection