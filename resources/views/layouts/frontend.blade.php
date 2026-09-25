<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>YuKanFaith Magazine</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Swiper -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet"/>
    <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            color: #1f1b18;
            background: #fff;
        }

        .site-header {
            background: #fff;
        }

        .site-header-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .site-brand img {
            height: 52px;
            width: auto;
            display: block;
        }

        .header-action {
            min-width: 170px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .user-menu a,
        .user-menu button {
            border: 1px solid transparent;
            background: transparent;
            color: #212529;
            font-weight: 700;
            text-decoration: none;
            padding: 9px 13px;
            border-radius: 999px;
            line-height: 1;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
        }

        .user-menu button {
            cursor: pointer;
        }

        .user-menu a:hover,
        .user-menu button:hover,
        .user-menu .active {
            background: #f4eeea;
            border-color: #eadcd4;
            color: #8e2d26;
        }

        .user-menu form {
            margin: 0;
        }

        .category-menu {
            border-top: 1px solid #f0ece8;
            margin-top: 22px;
            padding-top: 18px;
        }

        .category-menu a {
            color: #655c55;
        }

        .category-menu a:hover {
            color: #8e2d26;
        }

        .hero-slider {
            height: 85vh;
        }

        .hero-slider img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }

        @media (max-width: 768px) {
            .site-header-main {
                flex-direction: column;
            }

            .header-action {
                min-width: 0;
                order: 2;
            }

            .site-brand {
                order: 1;
            }

            .user-menu {
                justify-content: center;
                order: 3;
            }

            .hero-slider {
                height: 60vh;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<!-- ================= HEADER ================= -->
<nav class="site-header border-bottom py-4">
    <div class="container">

        <div class="site-header-main">

            @auth
                <a href="{{ route('user.subscriptions') }}" class="btn btn-dark header-action">
                    Subscription
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-dark header-action">
                    Subscribe
                </a>
            @endauth

            <a href="{{ route('home') }}" class="site-brand" aria-label="Yukan Faith Magazine home">
                 <img src="{{ asset('uploads/yukan.png') }}" alt="Yukan Faith Magazine">
            </a>

            @auth
                <div class="user-menu">
                    <a href="{{ route('user.dashboard') }}" class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-table-columns me-1"></i> Dashboard
                    </a>
                    <a href="{{ route('user.dashboard') }}#articles">
                        <i class="fa-regular fa-newspaper me-1"></i> Articles
                    </a>
                    <!-- <a href="{{ route('user.dashboard') }}#magazines">
                        <i class="fa-solid fa-book-open me-1"></i> Reader
                    </a> -->

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="d-flex gap-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-dark">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-dark">
                        Register
                    </a>
                </div>
            @endauth

        </div>

        <!-- Categories -->
        <div class="category-menu d-flex justify-content-center flex-wrap gap-4">
            <a href="{{ route('home') }}"
               class="text-decoration-none text-secondary fw-semibold">
                Home
            </a>
            @foreach(\App\Models\Category::all() as $cat)
                @continue(in_array(str_replace(['-', '_'], ' ', strtolower(trim($cat->category_name))), ['past magazines', 'past magazine'], true))
                <a href="{{ route('frontend.category', $cat) }}"
                   class="text-decoration-none text-secondary fw-semibold">
                    {{ $cat->category_name }}
                </a>
            @endforeach
        </div>

    </div>
</nav>

@yield('content')

<footer class="border-top mt-5 pt-5 pb-4 bg-light">

<div class="container text-center">

    {{-- ================= CATEGORY LINKS ================= --}}
    <div class="mb-4">
        <a href="{{ route('home') }}"
           class="text-decoration-none text-dark mx-2 fw-semibold">
            Home
        </a>

        @foreach(\App\Models\Category::all() as $cat)
            @continue(in_array(str_replace(['-', '_'], ' ', strtolower(trim($cat->category_name))), ['past magazines', 'past magazine'], true))

            <a href="{{ route('frontend.category', $cat) }}"
               class="text-decoration-none text-dark mx-2 fw-semibold">

                {{ $cat->category_name }}

            </a>

        @endforeach

    </div>



    {{-- ================= WHATSAPP BUTTON ================= --}}
    <div class="mb-4">

        <a href="https://whatsapp.com/channel/0029Vb6INDs6GcG69fYFPd3D"
           target="_blank"
           class="btn btn-success fw-bold">

           <i class="fab fa-whatsapp"></i>
           JOIN OUR WHATSAPP CHANNEL

        </a>

    </div>



    {{-- ================= SOCIAL ICONS ================= --}}
    <div class="mb-4">

        <a href="https://www.facebook.com/groups/yukanfaithcommunity/?ref=share&mibextid=KtfwRi"
           class="btn btn-outline-dark btn-sm mx-1">
            <i class="fab fa-facebook-f"></i>
        </a>

        <a href="https://www.linkedin.com/in/kim-yukanfaith-0b769833a"
           class="btn btn-outline-dark btn-sm mx-1">
            <i class="fab fa-linkedin-in"></i>
        </a>

    </div>



    {{-- ================= CONTACT BUTTONS ================= --}}
    <div class="mb-3">

        <a href="https://api.leadconnectorhq.com/widget/form/i6HwfrcEmYIjjDDp6UwR"
           class="btn btn-outline-dark me-2">
            Contact Us
        </a>

        <a href="https://app.jotform.com/250202325316442"
           class="btn btn-outline-dark">
            Business Registration App
        </a>

    </div>



    {{-- ================= COPYRIGHT ================= --}}
    <p class="text-muted small mt-3">
        Copyright © {{ date('Y') }} - All rights reserved
    </p>

</div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jotfor.ms/agent/embedjs/01974a6294487e838ee92a031184ca0df689/embed.js?skipWelcome=1&maximizable=1"></script>

@stack('scripts')

</body>
</html>
