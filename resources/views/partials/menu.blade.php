@php
    $menuGroups = [
        ['label' => 'Content', 'icon' => 'far fa-newspaper', 'items' => [
            ['Articles', 'admin.articles', 'article_access'],
            ['Categories', 'admin.categories', 'category_access'],
            ['Magazine Issues', 'admin.magazine-issues', 'article_access'],
            ['Events', 'admin.events', 'event_access'],
        ]],
        ['label' => 'Banners', 'icon' => 'far fa-images', 'items' => [
            ['Banners', 'admin.banners', null],
            ['Ads Banners', 'admin.ad-banners', null],
        ]],
        ['label' => 'Subscriptions', 'icon' => 'far fa-credit-card', 'items' => [
            ['Plans', 'admin.plans', ['subscription_access', 'plan_access']],
            ['Subscription List', 'admin.subscriptions', 'subscription_access'],
            ['Magazine Delivery Requests', 'admin.magazine-purchases', 'subscription_access'],
        ]],
        ['label' => 'Apparel Store', 'icon' => 'fas fa-shopping-bag', 'items' => [
            ['Shop Categories', 'admin.apparel-categories', null],
            ['Products', 'admin.apparel-products', null],
            ['Orders', 'admin.apparel-orders', null],
        ]],
        ['label' => 'Users & Access', 'icon' => 'fas fa-users', 'items' => [
            ['Users', 'admin.users', 'user_access'],
            ['Roles', 'admin.roles', ['user_manage_access', 'role_access']],
            ['Permissions', 'admin.permissions', ['user_manage_access', 'permission_access']],
        ]],
    ];
@endphp

<div class="sidebar admin-sidebar-compact">
    <nav class="sidebar-nav" aria-label="Admin navigation">
        <ul class="nav">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt nav-icon" aria-hidden="true"></i> Dashboard
                </a>
            </li>
            @foreach($menuGroups as $group)
                @php
                    $visibleItems = array_filter($group['items'], function ($item) {
                        foreach ((array) ($item[2] ?? []) as $permission) {
                            if (!\Illuminate\Support\Facades\Gate::allows($permission)) return false;
                        }
                        return true;
                    });
                    $isOpen = false;
                    foreach ($visibleItems as $item) {
                        if (request()->routeIs($item[1].'.*')) $isOpen = true;
                    }
                @endphp
                @if($visibleItems)
                    <li class="nav-item nav-dropdown {{ $isOpen ? 'open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle" href="#">
                            <i class="{{ $group['icon'] }} nav-icon" aria-hidden="true"></i> {{ $group['label'] }}
                        </a>
                        <ul class="nav-dropdown-items">
                            @foreach($visibleItems as $item)
                                <li class="nav-item">
                                    <a href="{{ route($item[1].'.index') }}" class="nav-link {{ request()->routeIs($item[1].'.*') ? 'active' : '' }}">
                                        {{ $item[0] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>
</div>
