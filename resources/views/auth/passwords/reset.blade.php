@extends('layouts.app')
@section('content')
    <main class="auth-reset-shell">
        <section class="auth-reset-panel">
            <div class="auth-reset-card">
                <a href="{{ route('home') }}" class="auth-reset-logo" aria-label="Yukan Faith Magazine home">
                    <img src="{{ asset('uploads/yukan.png') }}" alt="Yukan Faith Magazine">
                </a>

                <p class="auth-reset-eyebrow">Secure reset</p>
                <h1>{{ trans('global.reset_password') }}</h1>
                <p class="auth-reset-copy">Create a new password for your Yukan Faith Magazine account.</p>

                <form method="POST" action="{{ route('password.request') }}" class="auth-reset-form">
                    @csrf

                    <input name="token" value="{{ $token }}" type="hidden">

                    <div class="auth-reset-field">
                        <label for="email">{{ trans('global.login_email') }}</label>
                        <div class="auth-reset-control">
                            <i class="fi fi-rr-at"></i>
                            <input id="email" type="email" name="email"
                                class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required
                                autocomplete="email" autofocus placeholder="name@example.com"
                                value="{{ $email ?? old('email') }}">
                        </div>

                        @if($errors->has('email'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('email') }}
                            </div>
                        @endif
                    </div>

                    <div class="auth-reset-field">
                        <label for="password">{{ trans('global.login_password') }}</label>
                        <div class="auth-reset-control">
                            <i class="fi fi-rr-fingerprint"></i>
                            <input id="password" type="password" name="password"
                                class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required
                                placeholder="Enter new password">
                        </div>

                        @if($errors->has('password'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>

                    <div class="auth-reset-field">
                        <label for="password-confirm">{{ trans('global.login_password_confirmation') }}</label>
                        <div class="auth-reset-control">
                            <i class="fi fi-rr-lock"></i>
                            <input id="password-confirm" type="password" name="password_confirmation"
                                required placeholder="Confirm new password">
                        </div>
                    </div>

                    <button type="submit" class="auth-reset-submit">
                        {{ trans('global.reset_password') }}
                    </button>
                </form>

                <p class="auth-reset-alt">
                    <a href="{{ route('login') }}">Back to login</a>
                </p>
            </div>
        </section>
    </main>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');
        @import url('https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css');

        body {
            font-family: 'Manrope', sans-serif;
            background: #fbfaf8;
        }

        .container-fluid {
            padding: 0;
        }

        .auth-reset-shell {
            min-height: 100vh;
            padding: 92px 18px 56px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(251, 250, 248, .98)),
                url("{{ asset('images/mag.jpg') }}") center/cover no-repeat;
        }

        .auth-reset-panel {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 148px);
        }

        .auth-reset-card {
            width: min(100%, 460px);
            margin-top: 24px;
            padding: 42px;
            border: 1px solid #eadfd9;
            border-radius: 8px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 24px 70px rgba(56, 32, 28, .14);
        }

        .auth-reset-logo {
            display: inline-flex;
            margin-bottom: 34px;
        }

        .auth-reset-logo img {
            width: 128px;
            height: auto;
            display: block;
        }

        .auth-reset-eyebrow {
            margin: 0 0 12px;
            color: #b85a4e;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .auth-reset-card h1 {
            margin: 0 0 10px;
            color: #201918;
            font-size: 34px;
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: 0;
        }

        .auth-reset-copy {
            margin: 0 0 28px;
            color: #6e625d;
            font-size: 15px;
            line-height: 1.7;
        }

        .auth-reset-form {
            display: grid;
            gap: 18px;
        }

        .auth-reset-field label {
            display: block;
            margin-bottom: 8px;
            color: #2d2623;
            font-size: 13px;
            font-weight: 800;
        }

        .auth-reset-control {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 56px;
            padding: 0 16px;
            border: 1px solid #e1d7d2;
            border-radius: 8px;
            background: #fff;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .auth-reset-control:focus-within {
            border-color: #8e2d26;
            box-shadow: 0 0 0 4px rgba(142, 45, 38, .12);
        }

        .auth-reset-control i {
            color: #8e2d26;
            font-size: 18px;
            line-height: 1;
        }

        .auth-reset-control input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: #201918;
            font-size: 15px;
            font-weight: 600;
        }

        .auth-reset-control input::placeholder {
            color: #a99d97;
            font-weight: 500;
        }

        .invalid-feedback {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .auth-reset-submit {
            min-height: 56px;
            border: 2px solid #8e2d26;
            border-radius: 8px;
            background: #8e2d26;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .auth-reset-submit:hover,
        .auth-reset-submit:focus {
            background: #fff;
            color: #8e2d26;
            transform: translateY(-1px);
            outline: 0;
        }

        .auth-reset-alt {
            margin: 24px 0 0;
            color: #6e625d;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .auth-reset-alt a {
            color: #8e2d26;
            font-weight: 800;
            text-decoration: none;
        }

        .auth-reset-alt a:hover {
            text-decoration: underline;
        }

        @media (max-width: 575.98px) {
            .auth-reset-shell {
                padding: 44px 16px 36px;
            }

            .auth-reset-panel {
                min-height: auto;
            }

            .auth-reset-card {
                margin-top: 0;
                padding: 30px 22px;
            }

            .auth-reset-logo img {
                width: 108px;
            }

            .auth-reset-card h1 {
                font-size: 28px;
            }
        }
    </style>
@endsection
