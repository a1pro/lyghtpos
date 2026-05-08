@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-lg-8 col-md-7 col-sm-10">

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden verification-card">

                <!-- Header -->
                <div class="card-header text-center border-0 py-4 bg-gradient-primary-ak">
                    <div class="verification-icon mb-3">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>

                    <h3 class="fw-bold text-white mb-1">
                        Verify Your Email
                    </h3>

                    <p class="text-white-50 mb-0 small">
                        Enter the 6-digit verification code
                    </p>
                </div>

                <!-- Body -->
                <div class="card-body p-2 p-md-4">

                    <!-- Info Alert -->
                    <div class="alert custom-alert d-flex align-items-start mb-4">
                        <div class="alert-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>

                        <div class="ms-3">
                            <div class="fw-semibold mb-1">
                                Verification Code Sent
                            </div>

                            <small class="text-muted">
                                We have sent a verification code to
                            </small>

                            <div class="fw-bold text-dark mt-1">
                                {{ $user->email }}
                            </div>
                        </div>
                    </div>

                    <!-- Verification Form -->
                    <form id="verificationForm">

                        @csrf

                        <!-- OTP Input -->
                        <div class="mb-4">

                            <label for="otp" class="form-label fw-semibold">
                                Verification Code
                            </label>

                            <div class="otp-wrapper position-relative">

                                <input type="text" class="form-control otp-input" id="otp" name="otp" placeholder="000000" maxlength="6" autocomplete="off" required>

                                <span class="otp-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </span>

                            </div>

                            <small class="text-muted">
                                Please enter the 6-digit code sent to your email.
                            </small>

                        </div>

                        <!-- Verify Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 verify-btn" id="verifyBtn">
                            <span class="btn-text">
                                Verify Email
                            </span>

                            <span class="spinner-border spinner-border-sm d-none ms-2"></span>
                        </button>

                        <!-- Divider -->
                        <div class="divider my-4">
                            <span>OR</span>
                        </div>

                        <!-- Resend -->
                        <div class="text-center">

                            <p class="text-muted small mb-2">
                                Didn't receive the code?
                            </p>

                            <button type="button" id="resendBtn" class="btn btn-outline-primary resend-btn">
                                <i class="fas fa-redo-alt me-2"></i>
                                Resend Code
                            </button>

                        </div>

                    </form>
                </div>


            </div>

        </div>
    </div>
</div>

<style>
 
    .verification-card {
        border-radius: 24px;
        animation: fadeIn 0.5s ease-in-out;
    }

    .bg-gradient-primary-ak {
     background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
.otp-input:focus {
border-color: #723ceb!important;
box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

    .verification-icon {
        width: 85px;
        height: 85px;
        margin: auto;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }

    .verification-icon i {
        font-size: 38px;
        color: #fff;
    }

    .custom-alert {
        border: none;
        border-radius: 18px;
        background: #f8fbff;
        padding: 18px;
    }

    .alert-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background: rgba(13, 110, 253, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .alert-icon i {
        color: #0d6efd;
        font-size: 20px;
    }

    .otp-wrapper {
        position: relative;
    }

    .otp-input {
        height: 65px;
        border-radius: 16px;
        font-size: 28px;
        letter-spacing: 12px;
        text-align: center;
        border: 2px solid #dee2e6;
        padding-right: 50px;
        transition: all 0.3s ease;
        font-weight: 700;
    }

    .otp-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    .otp-icon {
        position: absolute;
        top: 50%;
        right: 18px;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 18px;
    }

    .verify-btn {
        height: 58px;
        border-radius: 16px;
        font-weight: 600;
        font-size: 17px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25);
        background: linear-gradient(135deg, #4f46e5, #7c3aed);

    }

    .verify-btn:hover {
        transform: translateY(-2px);
    }

    .resend-btn {
        border-radius: 14px;
        padding: 10px 22px;
        font-weight: 600;
        transition: 0.3s;
    }

    .resend-btn:hover {
        transform: translateY(-2px);
    }

    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        color: #adb5bd;
        font-size: 13px;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #dee2e6;
    }

    .divider span {
        padding: 0 15px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {

        .card-body {
            padding: 2rem !important;
        }

        .otp-input {
            height: 58px;
            font-size: 24px;
            letter-spacing: 8px;
        }

        .verify-btn {
            height: 54px;
            font-size: 16px;
        }

        .verification-icon {
            width: 70px;
            height: 70px;
        }

        .verification-icon i {
            font-size: 30px;
        }
    }

    @media (max-width: 576px) {

        .container {
            padding-left: 15px;
            padding-right: 15px;
        }

        .card-body {
            padding: 1.5rem !important;
        }

        .otp-input {
            letter-spacing: 5px;
            font-size: 22px;
        }
    }

</style>



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

