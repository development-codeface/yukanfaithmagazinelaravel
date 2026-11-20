@extends('layouts.frontend')
@section('title', 'Change subscription format')
@section('content')
<div class="container py-5" style="max-width:760px">
    <a href="{{ route('user.subscriptions') }}">&larr; Back to packages</a>
    <h1 class="h2 mt-4">{{ $quote['to_type'] === 'print' ? 'Switch to Print' : 'Upgrade to Digital + Print' }}</h1>
    <p class="text-muted">{{ $quote['plan_name'] }} · Current format: {{ ucfirst($quote['from_type']) }}</p>
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card p-4 mb-4">
        <p>Current full-period format price: <strong>{{ $quote['currency'] }} {{ $quote['current_format_price'] }}</strong></p>
        <p>Selected format full-period price: <strong>{{ $quote['currency'] }} {{ $quote['combined_format_price'] }}</strong></p>
        <p>Remaining period: {{ $quote['remaining_days'] }} of {{ $quote['total_days'] }} days.</p>
        <h2 class="h4">Upgrade payment: {{ $quote['currency'] }} {{ $quote['upgrade_amount'] }}</h2>
        <p class="mb-0">Your expiry date stays <strong>{{ $subscription->end_date->format('d M Y H:i') }}</strong>. Your original support payment is unchanged. No additional support is charged.</p>
    </div>
    @if($quote['to_type'] === 'print')
        <p class="alert alert-info">After payment, this subscription will be print only and will no longer provide digital access. Other eligible purchases or subscriptions may still provide access.</p>
    @endif
    @if($pending)
        <p class="alert alert-info">Your existing checkout keeps the price and delivery address saved when payment started. Continue using the same payment channel.</p>
    @else
        <p class="small text-muted">The final amount is calculated when checkout starts, using the exact time remaining.</p>
    @endif
    <form method="POST" action="{{ route('user.upgrades.checkout', $subscription) }}">
        @csrf
        <input type="hidden" name="target" value="{{ $quote['to_type'] }}">
        <h2 class="h5">Print delivery address</h2>
        @foreach(\App\Support\PrintDelivery::FIELDS as $field)
            <label class="d-block mb-3">
                {{ ['name' => 'Recipient name', 'phone' => 'Phone', 'line1' => 'Address line 1', 'line2' => 'Address line 2 (optional)', 'city' => 'City', 'state' => 'State / Province', 'postal_code' => 'Postal code', 'country' => 'Country'][$field] }}
                <input class="form-control" name="delivery_address[{{ $field }}]" value="{{ old('delivery_address.'.$field, ($pending?->delivery_address ?? $subscription->delivery_address ?? [])[$field] ?? '') }}" maxlength="200" @required($field !== 'line2') @readonly((bool) $pending)>
            </label>
        @endforeach
        <button class="btn btn-dark" type="submit">{{ $pending ? 'Continue existing checkout' : 'Pay and upgrade' }}</button>
    </form>
</div>
@endsection
