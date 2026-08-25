{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.app')

@section('title', 'Registration')

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset('assets/img/logo.png') }}"
                                     alt="Woot" height="40" class="mb-3">
                            </a>
                            <h4 class="fw-700">Create Account</h4>
                            <p class="text-muted fs-14">Join Woot and start shopping</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-600 fs-14">First Name</label>
                                        <input type="text" name="first_name"
                                               class="form-control @error('first_name') is-invalid @enderror"
                                               placeholder="First name"
                                               value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-600 fs-14">Last Name</label>
                                        <input type="text" name="last_name"
                                               class="form-control @error('last_name') is-invalid @enderror"
                                               placeholder="Last name"
                                               value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">Email Address</label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Enter your email"
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">Phone Number</label>
                                <input type="tel" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="Phone number"
                                       value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">Password</label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Create a password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">Confirm Password</label>
                                <input type="password" name="password_confirmation"
                                       class="form-control"
                                       placeholder="Confirm your password" required>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="agree" class="custom-control-input" id="agree" required>
                                    <label class="custom-control-label fs-14" for="agree">
                                        I agree to the
                                        <a href="{{ route('terms') }}" class="text-primary">Terms &amp; Conditions</a>
                                        and
                                        <a href="{{ route('privacy-policy') }}" class="text-primary">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block fw-600">
                                Create Account
                            </button>
                        </form>

                        <div class="text-center mt-4 fs-14">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-primary fw-600">Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
