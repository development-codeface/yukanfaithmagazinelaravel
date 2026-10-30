<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'All products') | YukanFaith Apparel</title>
    <link rel="stylesheet" href="{{ asset('css/apparel.css') }}">
    <meta name="apparel-currency" content="{{ strtoupper(config('services.stripe.currency', 'inr')) }}">
    @if(request()->routeIs('apparel.cart'))<noscript><style>.cart-panel[hidden]{display:flex}</style></noscript>@endif
</head>
<body class="{{ request()->routeIs('apparel.checkout') ? 'checkout-page' : '' }}">
<header class="store-header">
    <div class="store-container header-inner">
        <a class="brand" href="{{ route('apparel.index') }}"><img src="{{ asset('uploads/yukan.png') }}" alt=""><span>YukanFaith Apparel</span></a>
        <nav aria-label="Apparel categories">
            <a href="{{ route('apparel.orders.index') }}">My orders</a>
            <a href="{{ route('apparel.index') }}" @if(request()->routeIs('apparel.index')) aria-current="page" @endif>All products</a>
            @foreach($categories as $navCategory)
                <a href="{{ route('apparel.category', $navCategory->slug) }}" @if(request()->is('apparel/category/'.$navCategory->slug)) aria-current="page" @endif>{{ $navCategory->name }}</a>
            @endforeach
        </nav>
        <div class="store-account">
            @auth
                <span>Signed in as {{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
            @else
                <a href="{{ route('login') }}">Sign in</a>
                <a href="{{ route('register') }}">Create account</a>
            @endauth
        </div>
        <a class="cart-link" data-cart-open href="{{ route('apparel.cart') }}" aria-controls="cart-drawer" aria-expanded="false" aria-label="View shopping cart">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 3h3l3 13h11l3-10H6"/><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/></svg>
            <span data-cart-count>{{ collect(session('apparel_cart', []))->sum('quantity') }}</span>
        </a>
    </div>
</header>
<main class="store-container">
    @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
<footer class="store-container store-footer"><a href="{{ route('home') }}">YukanFaith Magazine</a><span>&copy; {{ date('Y') }} YukanFaith Apparel</span></footer>
<div id="cart-drawer-root" data-cart-url="{{ route('apparel.cart') }}" data-initial-open="{{ request()->routeIs('apparel.cart') || session('cart_open') ? 'true' : 'false' }}">
    @include('apparel.partials.cart-drawer', ['items' => app(\App\Http\Controllers\ApparelStoreController::class)->cartItems(request())])
</div>
<script src="{{ asset('js/apparel.js') }}" defer></script>
@stack('scripts')
</body>
</html>
