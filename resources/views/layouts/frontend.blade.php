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
    <link rel="stylesheet" href="{{ asset('css/site-design.css') }}">


    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f1b18;
            background: #fff;
        }

        .site-header {
            background: #fff;
        }

        .site-header .container {
            position: relative;
        }

section.container.home-hero-section {
    max-width: 1440px;
    width: 96% !important;
    margin-inline: auto;
}
.site-header-main .btn {
color: white;
    padding: 8px 16px;
    border-radius: 0px;
    background-color: #1e3a5f;
}
.site-header-main .btn:hover {
    background-color: #e89145;
color: white;
    padding: 8px 16px;
border-color: #e89145;
    border-radius: 0px;
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
/*
        .header-action {
            min-width: 170px;
        } */

        .header-tools {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            min-width: 190px;
        }

        .header-search-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid #e89145;
            border-radius: 999px;
            background: #e89145;
            color: #ffffff;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
        }

      .header-search-toggle:hover,
        .header-search-toggle:focus {
            background: #1e3a5f;
            border-color: #1e3a5f;
            color: #fff;
            outline: 0;
        }

        .header-search-panel {
            /* Search icons are stored locally with the other storefront assets. */
            width: min(520px, 100%);
            margin: 18px 0 0 auto;
            padding: 8px;
            border: 1px solid #e7e0dc;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 16px 38px rgba(35, 28, 24, .10);
        }

        .header-search-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .header-search-toggle:hover img,
        .header-search-toggle:focus img { filter: brightness(0) invert(1); }

        .header-search-form .form-control {
            min-height: 44px;
            border: 0;
            border-radius: 999px;
            box-shadow: none;
            padding-left: 16px;
        }

        .header-search-form .form-control:focus {
            box-shadow: none;
        }

        .header-search-form .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 999px;
            padding: 0;
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

        .login-register-btn {
            min-width: 142px;
        }

        .category-menu {
            /* border-top: 1px solid #f0ece8; */
            margin-top: 22px;
            padding-top: 18px;
        }

nav.site-header.py-4 .container {
    max-width: min(96%, 1440px);
    margin-inline: auto;
    /* max-width: 1440px; */
}

.magazine-menu-toggle {
    display: none;
    border: 1px solid #d8d8d8;
    border-radius: 4px;
    background: #fff;
    color: #172d43;
    padding: 8px 12px;
    font-size: 14px;
}

.category-menu .mobile-header-actions {
    display: none;
}

        .category-menu a {
padding: 8px 12px;
    font-size: 13px;
    color: #000000 !important;
    font-weight: 500 !important;
    letter-spacing: 1px;
    text-transform: uppercase;
}
 .category-menu .mobile-header-actions > a{
text-decoration: auto;
    color: white !important;
    padding: 12px 16px;
    background-color: #1e3a5f;
}
 .category-menu .mobile-header-actions > a:hover{
text-decoration: auto;
    color: white !important;
    padding: 12px 16px;
    background-color: #e89145;
}
        .category-menu a:hover {
    color: #7f1d1d !important;
    padding: 8px 12px;
        }

        .category-menu a.active {
      background: #1e3a5f;
    color: white !important;
    padding: 8px 12px;
        }

        /* .hero-slider {
            height: 85vh;
        } */

        .hero-slider img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
       .copy-right-container{
    padding: 25px 0px;
    display: flex;
    align-items: center;
    flex-direction: column;
    background-color: #213f61;
}
.copy-right-container .btn {
    color: white;
border-radius: 0px !important;
    border: 1px solid white;
}
.copy-right-container p{
    color: white;
margin-bottom: 0px;

}


@media (min-width: 991px) {
.site-header-main {
    padding: 0 100px;
}
}

@media (max-width: 1199px) {
button.magazine-menu-toggle {
    position: absolute;
    right: 30px;
    top: -9px;
}
/* nav.site-header.py-4 .container{
display: flex;
} */

.magazine-menu-toggle i {
font-size: 22px;
}
        .magazine-menu-toggle {
border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 12px 0 0 auto;
    }

    .category-menu {
        display: none !important;
        flex-direction: column;
        align-items: stretch;
        gap: 0 !important;
        margin-top: 10px;
        padding: 8px 0;
        border-top: 1px solid #eee;
    }

    .category-menu.is-open {
        display: flex !important;
    }

    .category-menu a {
        padding: 10px 12px;
    }

    .site-header-main .header-action,
    .site-header-main .header-tools {
        display: none;
    }

    .category-menu .mobile-header-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid #eee;
    }

    .category-menu .mobile-header-actions > a,
    .category-menu .mobile-header-actions .user-menu > a,
    .category-menu .mobile-header-actions .user-menu button {
        display: block;
        width: 100%;
        text-align: center;
    }

    .category-menu .mobile-header-actions .user-menu {
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    .category-menu .mobile-header-actions .user-menu form {
        width: 100%;
    }
}

@media (max-width: 768px) {
            .site-header-main {
                /* flex-direction: column; */
            }

            /* .header-action {
                min-width: 0;
                order: 2;
            } */
/*
            .site-brand {
                order: 1;
            } */

            /* .user-menu {
                justify-content: center;
                order: 3;
            } */

            .header-tools {
                justify-content: center;
                order: 3;
                width: 100%;
            }

            .header-search-panel {
                margin-right: auto;
            }

            .hero-slider {
                height: 60vh;
            }
        }

section.past-magazines-section .container{
    max-width: 1440px;
}

    </style>
    @stack('styles')
</head>

<body>

<!-- ================= HEADER ================= -->
<nav class="site-header  py-4">
    <div class="container">
        @php
            $headerSearch = request('search', '');
        @endphp

        <div class="site-header-main">

            @auth
                <a href="{{ route('user.subscriptions') }}" class="btn  header-action">
                    Subscription
                </a>
            @else
                <a href="{{ route('user.subscriptions') }}" class="btn  header-action">
                    Subscribe
                </a>
            @endauth

            <a href="{{ route('home') }}" class="site-brand" aria-label="Yukan Faith Magazine home">
                 <img src="{{ asset('uploads/yukan.png') }}" alt="Yukan Faith Magazine">
            </a>

            @auth
                <div class="header-tools">
                    <a href="{{ route('apparel.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark" aria-label="Apparel (opens in a new tab)">Apparel</a>
                    <button class="header-search-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#headerSearchPanel" aria-expanded="{{ $headerSearch ? 'true' : 'false' }}" aria-controls="headerSearchPanel" aria-label="Search articles"><img src="{{ asset('icons/ai-search-01-stroke-rounded.svg') }}" width="24" height="24" alt="" aria-hidden="true"></button>
                    <!-- <button class="header-search-toggle"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#headerSearchPanel"
                            aria-expanded="{{ $headerSearch ? 'true' : 'false' }}"
                            aria-controls="headerSearchPanel"
                            aria-label="Search articles">
                        <img src="{{ asset('icons/ai-search-01-stroke-rounded.svg') }}" width="24" height="24" alt="" aria-hidden="true">
                    </button> -->

                    <div class="user-menu">
                        <a href="{{ route('user.subscriptions') }}" class="{{ request()->routeIs('user.subscriptions') ? 'active' : '' }}">
                            <i class="fa-solid fa-table-columns me-1"></i> My Account
                        </a>
                        <!-- <a href="{{ route('user.subscriptions') }}#articles">
                            <i class="fa-regular fa-newspaper me-1"></i> Articles
                        </a> -->

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">
                                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="header-tools">
<button class="header-search-toggle"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#headerSearchPanel"
                            aria-expanded="{{ $headerSearch ? 'true' : 'false' }}"
                            aria-controls="headerSearchPanel"
                            aria-label="Search articles">
                        <img src="{{ asset('icons/ai-search-01-stroke-rounded.svg') }}" width="24" height="24" alt="" aria-hidden="true">
                    </button>
                    <a href="{{ route('apparel.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark" aria-label="Apparel (opens in a new tab)">Apparel</a>


                    <a href="{{ route('login') }}" class="btn  login-register-btn">
                         Login/ Register
                    </a>
                </div>
            @endauth

        </div>

        <div class="collapse {{ $headerSearch ? 'show' : '' }}" id="headerSearchPanel">
            <div class="header-search-panel">
                <form method="GET" action="{{ url('/') }}" class="header-search-form">
                    <label for="header-article-search" class="visually-hidden">Search articles</label>
                    <input
                        type="search"
                        id="header-article-search"
                        name="search"
                        value="{{ $headerSearch }}"
                        class="form-control"
                        placeholder="Search articles">
                    <button class="btn btn-dark" type="submit" aria-label="Submit search">
                        <img src="{{ asset('icons/ai-search-01-stroke-rounded.svg') }}" width="24" height="24" alt="" aria-hidden="true" style="filter:brightness(0) invert(1)">
                    </button>
                    @if($headerSearch)
                        <a href="{{ url('/') }}" class="btn btn-outline-dark" aria-label="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Categories -->
        <button class="magazine-menu-toggle" type="button" aria-controls="magazine-category-menu" aria-expanded="false" aria-label="Open categories">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="category-menu d-flex justify-content-center flex-wrap gap-4" id="magazine-category-menu">
            <!-- <a href="{{ route('apparel.index') }}" class="text-decoration-none text-secondary fw-semibold">Apparel</a> -->
            <a href="{{ route('home') }}"
               class="text-decoration-none text-secondary {{ request()->routeIs('home') ? 'active' : '' }}">
                Home
            </a>
            @foreach(\App\Models\Category::all() as $cat)
                @continue(in_array(str_replace(['-', '_'], ' ', strtolower(trim($cat->category_name))), ['past magazines', 'past magazine'], true))
                <a href="{{ route('frontend.category', $cat) }}"
                   class="text-decoration-none text-secondary {{ request()->routeIs('frontend.category') && request()->route('category')?->id === $cat->id ? 'active' : '' }}">
                    {{ $cat->category_name }}
                </a>
            @endforeach
            <div class="mobile-header-actions">
                @auth
                    <a href="{{ route('user.subscriptions') }}" class="btn header-action">Subscription</a>
                    <a href="{{ route('apparel.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark">Apparel</a>
                    <a href="{{ route('user.dashboard') }}" class="btn btn-outline-dark">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-outline-dark">Logout</button></form>
                @else
                    <a href="{{ route('user.subscriptions') }}" class="btn header-action">Subscribe</a>
                    <a href="{{ route('apparel.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark">Apparel</a>
                    <a href="{{ route('login') }}" class="btn login-register-btn">Login / Register</a>
                @endauth
                <button class="btn btn-outline-dark mobile-search-action" type="button" data-bs-toggle="collapse" data-bs-target="#headerSearchPanel" aria-controls="headerSearchPanel" aria-expanded="{{ $headerSearch ? 'true' : 'false' }}">
                    <i class="fa-solid fa-magnifying-glass me-1" aria-hidden="true"></i>Search articles
                </button>
            </div>
        </div>

    </div>
</nav>

<script>
    (() => {
        const toggle = document.querySelector('.magazine-menu-toggle');
        const menu = document.getElementById('magazine-category-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', () => {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Open categories' : 'Close categories');
            menu.classList.toggle('is-open', !isOpen);
        });
    })();
</script>

@yield('content')

<footer class="border-top mt-5 pt-5  bg-light">

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

</div>

 <div class="copy-right-container">
 <div class="mb-3">

        <a href="https://api.leadconnectorhq.com/widget/form/i6HwfrcEmYIjjDDp6UwR"
           class="btn  me-2">
            Contact Us
        </a>

        <a href="https://app.jotform.com/250202325316442"
           class="btn ">
            Business Registration App
        </a>

    </div>



    {{-- ================= COPYRIGHT ================= --}}
    <p class=" small mt-3">
        Copyright © {{ date('Y') }} - All rights reserved
    </p>
    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jotfor.ms/agent/embedjs/01974a6294487e838ee92a031184ca0df689/embed.js?skipWelcome=1&maximizable=1"></script>

@stack('scripts')

</body>
</html>
