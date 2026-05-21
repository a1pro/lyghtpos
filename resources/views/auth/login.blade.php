@extends('layouts.auth')

@section('content')

<div class="login-wrapper">

    <!-- Left Side -->
    <div class="left-side">
        <h2 class="login-head">{{ config('app.name') }}</h2>
    </div>

    <!-- Right Side -->
    <div class="right-side">

        <div class="login-box">
            <h2>Sign In</h2>
            <p>Please login to continue</p>

            @if (Session::has('error'))
            <div class="alert alert-danger">
                {{ Session::get('error') }}
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <div class="input-box">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="@error('email') is-invalid @enderror" placeholder="Enter your email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    </div>

                    @error('email')
                    <span class="invalid-feedback">
                        {{ $message }}
                    </span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <div class="input-box password-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="@error('password') is-invalid @enderror" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility()" style="cursor: pointer; right: 18px; left: auto;"></i>
                    </div>

                    @error('password')
                    <span class="invalid-feedback">
                        {{ $message }}
                    </span>
                    @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="options">
                    <label class="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        Remember Me
                    </label>

                    <a href="{{ route('password.request') }}" class="forgot">
                        Forgot Password?
                    </a>
                </div>

                <!-- Button -->
                <button type="submit" class="login-btn">
                    Sign In
                </button>

                <!-- Register -->
                <div class="register">
                    Don't have an account?
                    <a href="{{ route('register') }}">
                        Register
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    const passwordInput = document.querySelector('input[name="password"]');
    const passwordToggle = document.querySelector('.password-toggle');

    function togglePasswordVisibility() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordToggle.classList.remove('fa-eye');
            passwordToggle.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordToggle.classList.remove('fa-eye-slash');
            passwordToggle.classList.add('fa-eye');
        }
    }
</script>
@endsection
