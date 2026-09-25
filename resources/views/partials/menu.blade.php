<div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">

            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fi fi-br-dashboard nav-icon"></i>
                    Dashboard
                </a>
            </li>

            @can('user_manage_access')
                <li class="nav-item nav-dropdown {{ request()->routeIs('admin.permissions.*') || request()->routeIs('admin.roles.*') ? 'open' : '' }}">
                    <a class="nav-link nav-dropdown-toggle" href="#">
                        <i class="fi fi-br-user-gear nav-icon"></i>
                        {{ trans('cruds.userManagement.title') }}
                    </a>
                    <ul class="nav-dropdown-items" style="margin-left:28px;">
                        @can('permission_access')
                            <li class="nav-item">
                                <a href="{{ route('admin.permissions.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                    {{ trans('cruds.permission.title') }}
                                </a>
                            </li>
                        @endcan

                        @can('role_access')
                            <li class="nav-item">
                                <a href="{{ route('admin.roles.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                    {{ trans('cruds.role.title') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('user_access')
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fi fi-br-users nav-icon"></i>
                        {{ trans('cruds.user.title') }}
                    </a>
                </li>
            @endcan

             @can('category_access')
                <li class="nav-item">
                    <a href="{{ route('admin.categories.index') }}"
                        class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="fi fi-br-users nav-icon"></i>
                        Category
                    </a>
                </li>
            @endcan


            @can('article_access')
                <li class="nav-item">
                    <a href="{{ route('admin.articles.index') }}"
                        class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                        <i class="fi fi-br-users nav-icon"></i>
                        Articles
                    </a>
                </li>
            @endcan

            {{-- Events --}}
            @can('event_access')
                <li class="nav-item">
                    <a href="{{ route('admin.events.index') }}"
                        class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                        <i class="fi fi-br-calendar nav-icon"></i>
                        Events
                    </a>
                </li>
            @endcan

            {{-- Subscription Management --}}
            @can('subscription_access')
            <li class="nav-item nav-dropdown 
                {{ request()->routeIs('admin.plans.*') || request()->routeIs('admin.subscriptions.*') ? 'open' : '' }}">

                <a class="nav-link nav-dropdown-toggle" href="#">
                    <i class="fi fi-br-credit-card nav-icon"></i>
                    Subscription Management
                </a>

                <ul class="nav-dropdown-items" style="margin-left:28px;">

                    {{-- Plans --}}
                    @can('plan_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.plans.index') }}"
                            class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                            Plans
                        </a>
                    </li>
                    @endcan

                    {{-- Subscription List --}}
                    @can('subscription_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.subscriptions.index') }}"
                            class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                            Subscription List
                        </a>
                    </li>
                    @endcan

                </ul>
            </li>
            @endcan


          

        </ul>
    </nav>
</div>
