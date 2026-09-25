@extends('layouts.app')
@section('content')
    @php
        $loginContext = $loginContext ?? 'user';
        $loginHeading = $loginHeading ?? 'Welcome back,';
        $loginSubheading = $loginSubheading ?? 'Sign in to your Account';
        $loginButtonText = $loginButtonText ?? trans('global.login');
        $isAdminLogin = $loginContext === 'admin';
    @endphp
    @if (! $isAdminLogin)
        <main class="reader-login-shell">
            <section class="reader-login-hero" aria-label="Yukan Faith Magazine">
                <img src="{{ asset('images/mag.jpg') }}" alt="Yukan Faith Magazine" class="reader-login-hero-image">
                <div class="reader-login-hero-overlay"></div>
                <div class="reader-login-hero-content">
                    <a href="{{ route('home') }}" class="reader-login-brand" aria-label="Yukan Faith Magazine home">
                        <img src="{{ asset('uploads/yukan.png') }}" alt="Yukan Faith Magazine">
                    </a>
                    <p>Digital magazine access</p>
                    <h1>Read inspiring stories and continue your subscription.</h1>
                </div>
            </section>

            <section class="reader-login-panel">
                <div class="reader-login-card">
                    <div class="reader-login-header">
                        <img src="{{ asset('uploads/yukan.png') }}" alt="Yukan Faith Magazine" class="reader-login-logo">
                        <p class="reader-login-eyebrow">Member login</p>
                        <h2>{{ $loginHeading }}</h2>
                        <p>{{ $loginSubheading }}</p>
                    </div>

                    @if (session('message'))
                        <div class="alert alert-info" role="alert">
                            {{ session('message') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="reader-login-form">
                        @csrf
                        <input type="hidden" name="login_context" value="{{ $loginContext }}">

                        <div class="reader-form-field">
                            <label for="email">{{ trans('global.login_email') }}</label>
                            <div class="reader-field-control">
                                <i class="fi fi-rr-at"></i>
                                <input id="email" name="email" type="email"
                                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required
                                    autocomplete="email" autofocus placeholder="name@example.com"
                                    value="{{ old('email', null) }}">
                            </div>

                            @if ($errors->has('email'))
                                <div class="invalid-feedback d-block">
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>

                        <div class="reader-form-field">
                            <label for="password">{{ trans('global.login_password') }}</label>
                            <div class="reader-field-control">
                                <i class="fi fi-rr-fingerprint"></i>
                                <input id="password" name="password" type="password"
                                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required
                                    placeholder="Enter your password">
                            </div>

                            @if ($errors->has('password'))
                                <div class="invalid-feedback d-block">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif
                        </div>

                        <div class="reader-login-options">
                            <label class="reader-remember" for="remember">
                                <input name="remember" type="checkbox" id="remember">
                                <span>{{ trans('global.remember_me') }}</span>
                            </label>

                            <!-- @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">{{ trans('global.forgot_password') }}</a>
                            @endif -->
                        </div>

                        <button type="submit" class="reader-login-submit">
                            {{ $loginButtonText }}
                        </button>
                    </form>

                    @if (Route::has('register'))
                        <p class="reader-login-alt">
                            New to Yukan Faith Magazine?
                            <a href="{{ route('register') }}">Create an account</a>
                        </p>
                    @endif

                    <!-- <p class="reader-login-alt reader-login-muted">
                        <a href="{{ route('admin.login') }}">Admin login</a>
                    </p> -->
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

            .reader-login-shell {
                display: grid;
                grid-template-columns: minmax(0, 1.08fr) minmax(420px, .92fr);
                min-height: 100vh;
                background: #fbfaf8;
            }

            .reader-login-hero {
                position: relative;
                min-height: 100vh;
                overflow: hidden;
                background: #291816;
            }

            .reader-login-hero-image {
                width: 100%;
                height: 100%;
                min-height: 100vh;
                object-fit: cover;
                display: block;
            }

            .reader-login-hero-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, rgba(31, 18, 16, .88), rgba(31, 18, 16, .5) 52%, rgba(31, 18, 16, .2));
            }

            .reader-login-hero-content {
                position: absolute;
                left: clamp(28px, 6vw, 80px);
                right: clamp(28px, 8vw, 96px);
                bottom: clamp(36px, 9vh, 100px);
                color: #fff;
                max-width: 640px;
            }

            .reader-login-brand {
                display: inline-flex;
                align-items: center;
                background: rgba(255, 255, 255, .92);
                padding: 14px 18px;
                border-radius: 8px;
                margin-bottom: 36px;
            }

            .reader-login-brand img,
            .reader-login-logo {
                display: block;
                width: 128px;
                height: auto;
            }

            .reader-login-hero-content p,
            .reader-login-eyebrow {
                margin: 0 0 12px;
                color: #b85a4e;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
            }

            .reader-login-hero-content p {
                color: #f1cbc5;
            }

            .reader-login-hero-content h1 {
                margin: 0;
                max-width: 560px;
                color: #fff;
                font-size: clamp(34px, 5vw, 64px);
                line-height: 1.05;
                font-weight: 800;
                letter-spacing: 0;
            }

            .reader-login-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 48px;
                background: linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(251, 250, 248, .96)), #fbfaf8;
            }

            .reader-login-card {
                width: min(100%, 440px);
            }

            .reader-login-header {
                margin-bottom: 28px;
            }

            .reader-login-logo {
                margin-bottom: 34px;
            }

            .reader-login-header h2 {
                margin: 0 0 10px;
                color: #201918;
                font-size: 34px;
                line-height: 1.12;
                font-weight: 800;
                letter-spacing: 0;
            }

            .reader-login-header p:not(.reader-login-eyebrow) {
                margin: 0;
                color: #6e625d;
                font-size: 15px;
                line-height: 1.7;
            }

            .reader-login-form {
                display: grid;
                gap: 18px;
            }

            .reader-form-field label {
                display: block;
                margin-bottom: 8px;
                color: #2d2623;
                font-size: 13px;
                font-weight: 800;
            }

            .reader-field-control {
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

            .reader-field-control:focus-within {
                border-color: #8e2d26;
                box-shadow: 0 0 0 4px rgba(142, 45, 38, .12);
            }

            .reader-field-control i {
                color: #8e2d26;
                font-size: 18px;
                line-height: 1;
            }

            .reader-field-control input {
                width: 100%;
                min-width: 0;
                border: 0;
                outline: 0;
                background: transparent;
                color: #201918;
                font-size: 15px;
                font-weight: 600;
            }

            .reader-field-control input::placeholder {
                color: #a99d97;
                font-weight: 500;
            }

            .reader-login-options {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                margin: 2px 0 4px;
                flex-wrap: wrap;
            }

            .reader-remember {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                color: #625852;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
            }

            .reader-remember input {
                accent-color: #8e2d26;
            }

            .reader-login-options a,
            .reader-login-alt a {
                color: #8e2d26;
                font-size: 13px;
                font-weight: 800;
                text-decoration: none;
            }

            .reader-login-options a:hover,
            .reader-login-alt a:hover {
                text-decoration: underline;
            }

            .reader-login-submit {
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

            .reader-login-submit:hover,
            .reader-login-submit:focus {
                background: #fff;
                color: #8e2d26;
                transform: translateY(-1px);
                outline: 0;
            }

            .reader-login-alt {
                margin: 22px 0 0;
                color: #6e625d;
                font-size: 13px;
                font-weight: 700;
                text-align: center;
            }

            .reader-login-muted {
                margin-top: 12px;
            }

            @media (max-width: 991.98px) {
                .reader-login-shell {
                    grid-template-columns: 1fr;
                }

                .reader-login-hero,
                .reader-login-hero-image {
                    min-height: 340px;
                }

                .reader-login-hero-content {
                    bottom: 34px;
                }

                .reader-login-panel {
                    min-height: auto;
                    padding: 42px 24px 56px;
                }
            }

            @media (max-width: 575.98px) {
                .reader-login-hero,
                .reader-login-hero-image {
                    min-height: 260px;
                }

                .reader-login-brand {
                    margin-bottom: 22px;
                    padding: 10px 12px;
                }

                .reader-login-brand img,
                .reader-login-logo {
                    width: 104px;
                }

                .reader-login-hero-content h1 {
                    font-size: 30px;
                }

                .reader-login-panel {
                    padding: 32px 18px 44px;
                }

                .reader-login-header h2 {
                    font-size: 28px;
                }
            }
        </style>
    @else
    <div class="row ">
        <div class="col-md-7 vid">
            <div class="video-container">
                 <img src="{{ asset('images/mag.jpg') }}" alt="Background Image" class="background-image">

             </div>
        </div>
        <div class="col-md-5 login ">
            <div class="row">
                <div class="card col-lg-7 bg_bl">
                    <div class="card-body p-4">
                        <p class="cn1z"><img class="w_100" src="{{ asset('css/img/sims1.png') }}" alt=""> </p>
                        <!-- <h1>{{ trans('panel.site_title') }}</h1> -->
                        <div class="in_tex">
                            <p class="p_1">{{ $loginHeading }}</p>
                            <p class="p_2">{{ $loginSubheading }}</p>
                        </div>
                        <!-- {{ trans('global.login') }} -->
                        @if (session('message'))
                            <div class="alert alert-info" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="login_context" value="{{ $loginContext }}">

                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fi fi-rr-at"></i>
                                    </span>
                                </div>

                                <input id="email" name="email" type="text"
                                    class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" required
                                    autocomplete="email" autofocus placeholder="{{ trans('global.login_email') }}"
                                    value="{{ old('email', null) }}">

                                @if ($errors->has('email'))
                                    <div class="invalid-feedback">
                                        {{ $errors->first('email') }}
                                    </div>
                                @endif
                            </div>

                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fi fi-rr-fingerprint"></i></span>
                                </div>

                                <input id="password" name="password" type="password"
                                    class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" required
                                    placeholder="{{ trans('global.login_password') }}">

                                @if ($errors->has('password'))
                                    <div class="invalid-feedback">
                                        {{ $errors->first('password') }}
                                    </div>
                                @endif
                            </div>

                            <div class="input-group mb-4">
                                <div class="form-check checkbox cnal">
                                    <input class="form-check-input" name="remember" type="checkbox" id="remember"
                                        style="vertical-align: middle;" />
                                    <label class="form-check-label texlb" for="remember" style="vertical-align: middle;">
                                        {{ trans('global.remember_me') }}
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 tc">
                                    <button type="submit" class="btn btn-primary px-4">
                                        {{ $loginButtonText }}
                                    </button>
                                </div>
                                <!-- <div class="col-6 text-right">
                                    @if (Route::has('password.request'))
    <a class="btn btn-link px-0" href="{{ route('password.request') }}">
                                            {{ trans('global.forgot_password') }}
                                        </a><br>
    @endif

                                </div> -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap');
        @import url('https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css');

        @import url('https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css');

        @import url('https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css');

        body {
            font-family: 'Manrope', sans-serif;
        }

        .logo {
            width: 40%;
            margin: 38% auto 6% auto;
        }

        .btn-primary.focus,
        .btn-primary:focus {
            box-shadow: 0 0 0 .2rem rgba(65, 181, 222, 0);
        }

        .logo img {
            width: 100%;
        }

        .cn1z {
            font-size: 18px;
            font-weight: 800;
            margin-left: 0px;
            /* margin-top: 20px; */
            color: #2b68e8;
            /* background: #dfebf5; */
            /* padding: 20px 30px; */
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
            /* position: absolute; */
            /* right: 157px; */
            width: 100%;
            overflow: hidden;
            margin-top: 20px;
        }

        .w_100 {
            width: 100%;
        }

        .bg_bl {
            background: #fff;
            /* width: 50%; */
            margin: 23% auto;
            /* min-height: 400px; */
            padding-top: 10px;
            padding-bottom: 30px;
            box-shadow: 0px 12px 23px 0px rgba(112, 112, 112, 0.04);
            height: calc(100% - 30px);
            border: 0px;
        }

        .texlb {
            vertical-align: middle;
            font-size: 12px;
            /* margin-bottom: 2px; */
            /* font-weight: 800; */
        }

        .cnal {
            /* text-align: center; */
            margin: 0px auto;
        }

        .in_tex {
            margin-top: 60px;
            text-align: center;
        }

        .tc {
            text-align: center;
        }

        .p_1 {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 1px;
        }

        .p_2 {
            font-size: 16px;
            font-weight: 800;
        }

        .vid {
            background: #d7d5cf;
            min-height: 100vh;
            overflow: hidden;
        }

        .login {
            background: #f4f5f9;
            min-height: 100vh;
        }

        .form-control {
            border-radius: 13px;
            background: transparent;
            border-bottom: 1px solid #f0f1f5;
            color: #000;
            height: 56px;
            /* border: 1px; */
            border: 2px solid #949497;
            color: #404040;
            border-left: 0px;
        }

        .input-group-text {
            /* display: -ms-flexbox; */
            border: 2px solid #949497;
            border-radius: 13px 0px 13px 13px !important;
            padding: .375rem 1rem;
        }

        .mr10 {
            margin-right: 10px;
            display: block;
            float: left;
            margin-top: 2px;
        }

        .form-control:hover,
        .form-control:focus,
        .form-control.active {
            box-shadow: none;
            /* background: #fff; */
            color: #020202;
        }

        .video-container video {
            min-width: 100%;
            min-height: 100%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
        }

        .btn-primary:hover {
            color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
            background-color: #fff;
            border-color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
            border: 2px solid {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
            font-weight: 800;
        }

        .btn-primary {
            color: #fff;
                background-color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
    border-color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
    border: 2px solid {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
            font-weight: 800;
        }

        /* Just styling the content of the div, the *magic* in the previous rules */
        .video-container .caption {
            z-index: 1;
            position: relative;
            text-align: center;
            color: #dc0000;
            padding: 10px;
        }

        .card {

            border-radius: 20px;
        }

        @media (max-width: 767.98px) {

            .bg_bl {

                margin: 5% auto;

            }

            .logo {
                width: 68%;
                margin: 11% auto 6% auto;
            }

            .vid {
                background: #d7d5cf;
                min-height: 5vh;
                overflow: hidden;
            }
        }
		
		 .form-control:focus{
    color: #5c6873;
    background-color: #fff;
    border-color: #8e352f6e !important;
 }
 .btn-primary:not(:disabled):not(.disabled).active, .btn-primary:not(:disabled):not(.disabled):active, .show>.btn-primary.dropdown-toggle {
    color: #fff;
    background-color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
    border-color: {{ $isAdminLogin ? '#1f2937' : '#8e2d26' }};
}
    </style>
    @endif
@endsection
