@php
    $newPrintPurchases = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'print_acknowledged_at')
        ? \App\Models\Subscription::where('print_enabled', true)->where('payment_status', 1)->whereNull('print_acknowledged_at')->count()
        : 0;
    $newMagazineDeliveries = \Illuminate\Support\Facades\Schema::hasColumn('magazine_purchases', 'print_acknowledged_at')
        ? \App\Models\MagazinePurchase::whereIn('delivery_type', ['print', 'both'])->whereNull('print_acknowledged_at')->count()
        : 0;
    $notificationCount = $newPrintPurchases + $newMagazineDeliveries;
@endphp

<li class="nav-item dropdown admin-notifications">
    <button class="nav-link admin-notification-toggle" type="button" id="admin-notifications-toggle"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
            aria-label="Notifications: {{ $notificationCount }} pending deliveries">
        <i class="far fa-bell" aria-hidden="true"></i>
        @if($notificationCount)
            <span class="admin-notification-badge" aria-hidden="true">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-right admin-notification-menu" aria-labelledby="admin-notifications-toggle">
        <div class="admin-notification-heading">
            <strong>Notifications</strong>
            <span>{{ $notificationCount }} pending</span>
        </div>
        @if($newPrintPurchases)
            <a class="dropdown-item admin-notification-item" href="{{ route('admin.subscriptions.index') }}">
                <span class="admin-notification-icon"><i class="fas fa-box" aria-hidden="true"></i></span>
                <span><strong>Print package deliveries <span class="admin-notification-count">{{ $newPrintPurchases }}</span></strong>
                    <small>View orders and delivery addresses <span aria-hidden="true">&rarr;</span></small></span>
            </a>
        @endif
        @if($newMagazineDeliveries)
            <a class="dropdown-item admin-notification-item" href="{{ route('admin.magazine-purchases.index') }}">
                <span class="admin-notification-icon"><i class="far fa-newspaper" aria-hidden="true"></i></span>
                <span><strong>Magazine deliveries <span class="admin-notification-count">{{ $newMagazineDeliveries }}</span></strong>
                    <small>View printed magazine orders <span aria-hidden="true">&rarr;</span></small></span>
            </a>
        @endif
        @if(!$notificationCount)
            <div class="admin-notification-empty">
                <i class="far fa-check-circle" aria-hidden="true"></i>
                <strong>You're all caught up</strong>
                <span>No pending delivery notifications.</span>
            </div>
        @else
            <div class="admin-notification-footer">Orders stay here until marked as reviewed.</div>
        @endif
    </div>
</li>
