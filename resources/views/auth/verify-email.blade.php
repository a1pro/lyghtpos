@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Verify Your Email Address</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        A verification code has been sent to <strong>{{ $user->email }}</strong>
                    </div>

                    <form id="verificationForm">
                        @csrf
                        <div class="mb-4">
                            <label for="otp" class="form-label">Enter Verification Code</label>
                            <input type="text" class="form-control form-control-lg text-center" id="otp" name="otp" placeholder="000000" maxlength="6" autocomplete="off" required>
                        </div>

                        <div class="text-center mb-4">
                            <button type="button" id="resendBtn" class="btn btn-link">
                                <i class="fas fa-redo-alt me-1"></i>Resend Code
                            </button>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100" id="verifyBtn">
                            Verify Email
                            <span class="spinner-border spinner-border-sm d-none ms-2"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('verificationForm');
        const otpInput = document.getElementById('otp');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const verificationRoute = '{{ request()->route()->getPrefix() }}';

        // Show SweetAlert2 notification
        function showNotification(type, title, message, timer = 3000) {
            const config = {
                title: title
                , text: message
                , timer: timer
                , showConfirmButton: true
            };

            switch (type) {
                case 'success':
                    Swal.fire({
                        ...config
                        , icon: 'success'
                        , confirmButtonColor: '#28a745'
                    });
                    break;
                case 'error':
                    Swal.fire({
                        ...config
                        , icon: 'error'
                        , confirmButtonColor: '#dc3545'
                    });
                    break;
                case 'warning':
                    Swal.fire({
                        ...config
                        , icon: 'warning'
                        , confirmButtonColor: '#ffc107'
                    });
                    break;
                case 'info':
                    Swal.fire({
                        ...config
                        , icon: 'info'
                        , confirmButtonColor: '#17a2b8'
                    });
                    break;
                default:
                    Swal.fire(config);
            }
        }

        function sendInitialOtp() {
            fetch(`/${verificationRoute}/send-otp`, {
                    method: 'POST'
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': csrfToken
                        , 'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .catch(error => {
                    showNotification('error', 'Error', 'Failed to send verification code. Please refresh the page.');
                });
        }

        function checkExistingOtpStatus() {
            fetch(`/${verificationRoute}/status`, {
                    method: 'GET'
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': csrfToken
                        , 'Accept': 'application/json'
                    }
                })
                .then(response => response.json());
        }

        function resendOtp() {
            resendBtn.disabled = true;
            const originalText = resendBtn.innerHTML;
            resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';

            fetch(`/${verificationRoute}/resend-otp`, {
                    method: 'POST'
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': csrfToken
                        , 'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', 'Success', 'New verification code sent to your email');
                        otpInput.value = '';
                        otpInput.focus();
                    } else {
                        showNotification('error', 'Error', data.message || 'Failed to resend code');
                        resendBtn.disabled = false;
                    }
                    resendBtn.innerHTML = originalText;
                })
                .catch(error => {
                    showNotification('error', 'Error', 'Failed to resend verification code. Please try again.');
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = originalText;
                });
        }

        function verifyOtp(otp) {
            verifyBtn.disabled = true;
            const originalText = verifyBtn.innerHTML;
            verifyBtn.innerHTML = 'Verifying... <span class="spinner-border spinner-border-sm ms-2"></span>';

            fetch(`/${verificationRoute}/verify-otp`, {
                    method: 'POST'
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': csrfToken
                        , 'Accept': 'application/json'
                    }
                    , body: JSON.stringify({
                        otp: otp
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and redirect
                        Swal.fire({
                            title: 'Success!'
                            , text: 'Email verified successfully! Redirecting to dashboard...'
                            , icon: 'success'
                            , confirmButtonColor: '#28a745'
                            , timer: 2000
                            , showConfirmButton: false
                        });

                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        showNotification('error', 'Verification Failed', data.message || 'Invalid verification code');
                        verifyBtn.disabled = false;
                        verifyBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    showNotification('error', 'Error', 'Verification failed. Please try again.');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalText;
                });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = otpInput.value.trim();

            if (otp.length !== 6) {
                showNotification('warning', 'Invalid Input', 'Please enter a valid 6-digit verification code');
                return;
            }

            if (!/^\d+$/.test(otp)) {
                showNotification('warning', 'Invalid Input', 'Please enter numbers only');
                return;
            }

            verifyOtp(otp);
        });

        resendBtn.addEventListener('click', resendOtp);

        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length === 6) {
                form.dispatchEvent(new Event('submit'));
            }
        });

        otpInput.focus();

        sendInitialOtp();
    });

</script>
@endsection
