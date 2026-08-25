{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', 'Login')

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset('assets/img/logo.png') }}"
                                     alt="Woot" height="40" class="mb-3">
                            </a>
                            <h4 class="fw-700">Welcome Back!</h4>
                            <p class="text-muted fs-14">Sign in to your account</p>
                        </div>

                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="form-group">
                                <label class="fw-600 fs-14">Email Address</label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Enter your email"
                                       value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">Password</label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Enter your password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group d-flex justify-content-between align-items-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="remember" class="custom-control-input" id="remember">
                                    <label class="custom-control-label fs-14" for="remember">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="text-primary fs-14">Forgot password?</a>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block fw-600">
                                Sign In
                            </button>
                        </form>

                        <div class="text-center mt-4 fs-14">
                            Don't have an account?
                            <a href="{{ route('register') }}" class="text-primary fw-600">Register Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection