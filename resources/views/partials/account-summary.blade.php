<section class="account-summary" aria-label="My subscriptions">
    <h1>My Subscriptions</h1>
    <a href="{{ route('user.payment-history') }}" class="btn btn-outline-light mb-2">Payment History</a>
    <p>{{ auth()->user()->name }} - {{ auth()->user()->email }}</p>
    @if($subscription)
        <p>{{ $subscription->plan?->name ?? 'Active plan' }} · {{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$subscription->delivery_type ?? 'digital'] }} · {{ ucfirst($subscription->status) }}</p>
        <p>Valid until: {{ $subscription->end_date?->format('d M Y') ?? 'No expiry date' }}</p>
    @else
        <p>No active subscription. Choose a package below.</p>
    @endif
    @if($purchasedMagazines->isNotEmpty())<a href="#magazines" class="text-white">View my purchased magazines &rarr;</a>@endif
</section>
