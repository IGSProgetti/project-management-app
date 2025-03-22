@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Register') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-sm-12 col-form-label text-md-end text-sm-start">{{ __('Name') }}</label>
                            <div class="col-md-6 col-sm-12">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-sm-12 col-form-label text-md-end text-sm-start">{{ __('Email Address') }}</label>
                            <div class="col-md-6 col-sm-12">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-sm-12 col-form-label text-md-end text-sm-start">{{ __('Password') }}</label>
                            <div class="col-md-6 col-sm-12">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-sm-12 col-form-label text-md-end text-sm-start">{{ __('Confirm Password') }}</label>
                            <div class="col-md-6 col-sm-12">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4 col-sm-12 offset-sm-0 d-sm-grid mt-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                                
                                @if (Route::has('login'))
                                    <div class="mt-3 text-center">
                                        <a href="{{ route('login') }}" class="text-decoration-none">
                                            {{ __('Already have an account? Login') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 767.98px) {
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .text-sm-start {
            text-align: left !important;
        }
        
        .offset-sm-0 {
            margin-left: 0;
        }
        
        .d-sm-grid {
            display: grid;
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        input.form-control {
            padding: 0.75rem;
            font-size: 1rem;
            height: auto;
        }
        
        .col-form-label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
    }
</style>
@endsection