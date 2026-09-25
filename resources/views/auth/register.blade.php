@extends('layouts.app')

@section('content')
<main class="register-page">
    <section class="register-shell">
        <div class="register-media">
            <img src="{{ asset('images/mag.jpg') }}" alt="Yukan Faith Magazine">
            <div class="register-media-copy">
                <span>Yukan Faith Magazine</span>
                <h1>Create your reading account</h1>
                <p>Join the community and access articles, subscriptions, and the magazine reader from one dashboard.</p>
            </div>
        </div>

        <div class="register-form-wrap">
            <div class="register-form-card">
                <div class="mb-4">
                    <p class="text-uppercase text-muted font-weight-bold small mb-2">Start reading</p>
                    <h2 class="font-weight-bold mb-2">Register</h2>
                    <p class="text-muted mb-0">Use your details below to create a secure account.</p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label font-weight-bold">Name</label>
                        <input id="name" type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label font-weight-bold">Email address</label>
                        <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label font-weight-bold">Password</label>
                        <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                        <small class="form-text text-muted">Use at least 8 characters with uppercase, lowercase, number, and symbol.</small>

                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password-confirm" class="form-label font-weight-bold">Confirm password</label>
                        <input id="password-confirm" type="password" class="form-control form-control-lg" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100">
                        Create Account
                    </button>

                    <p class="text-center text-muted mt-4 mb-0">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-weight-bold text-dark">Login</a>
                    </p>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection

@section('styles')
<style>
    .container-fluid {
        padding: 0;
    }

    .register-page {
        background: #f7f5f2;
        padding: 48px 0;
        min-height: 100vh;
    }

    .register-shell {
        width: min(1120px, calc(100% - 32px));
        margin: 0 auto;
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.95fr);
        min-height: 660px;
        background: #fff;
        border: 1px solid #e7e0dc;
        border-radius: 8px;
        overflow: hidden;
    }

    .register-media {
        position: relative;
        min-height: 520px;
        background: #231b19;
    }

    .register-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        opacity: .84;
    }

    .register-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, .12), rgba(0, 0, 0, .68));
    }

    .register-media-copy {
        position: absolute;
        left: 36px;
        right: 36px;
        bottom: 34px;
        z-index: 1;
        color: #fff;
    }

    .register-media-copy span {
        display: block;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
        margin-bottom: 10px;
    }

    .register-media-copy h1 {
        font-size: 42px;
        line-height: 1.08;
        font-weight: 800;
        margin-bottom: 14px;
    }

    .register-media-copy p {
        max-width: 520px;
        margin: 0;
        color: rgba(255, 255, 255, .86);
        font-size: 16px;
    }

    .register-form-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .register-form-card {
        width: 100%;
        max-width: 430px;
    }

    .register-form-card .form-control {
        border: 1px solid #d8d1cb;
        border-radius: 8px;
        min-height: 52px;
    }

    .register-form-card .form-control:focus {
        border-color: #8e2d26;
        box-shadow: 0 0 0 .2rem rgba(142, 45, 38, .12);
    }

    @media (max-width: 991.98px) {
        .register-shell {
            grid-template-columns: 1fr;
        }

        .register-media {
            min-height: 360px;
        }
    }

    @media (max-width: 575.98px) {
        .register-page {
            padding: 24px 0;
        }

        .register-shell {
            width: calc(100% - 20px);
        }

        .register-form-wrap {
            padding: 28px 20px;
        }

        .register-media-copy {
            left: 22px;
            right: 22px;
            bottom: 24px;
        }

        .register-media-copy h1 {
            font-size: 31px;
        }
    }
</style>
@endsection
