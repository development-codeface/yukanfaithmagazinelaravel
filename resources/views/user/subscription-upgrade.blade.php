@extends('layouts.frontend')
@section('title', 'Change subscription format')
@push('styles')
<style>
.upgrade-page { background: #faf8f6; padding: 56px 0 80px; color: #292120; }
.upgrade-wrap { max-width: 1020px; }
.upgrade-back { color: #8e2d26; font-weight: 600; text-decoration: none; }
.upgrade-back:hover { color: #66201c; }
.upgrade-heading { margin: 25px 0 30px; }
.upgrade-heading .eyebrow { color: #8e2d26; font-size: .75rem; letter-spacing: .12em; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
.upgrade-heading h1 { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 700; margin-bottom: 8px; }
.upgrade-heading p { color: #746663; margin: 0; }
.upgrade-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 24px; align-items: start; }
.upgrade-card { background: #fff; border: 1px solid #ebdfda; border-radius: 16px; padding: 28px; box-shadow: 0 10px 35px rgba(66, 37, 30, .05); }
.upgrade-card h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; }
.upgrade-card .intro { color: #746663; font-size: .92rem; margin-bottom: 24px; }
.upgrade-line { display: flex; justify-content: space-between; gap: 16px; padding: 13px 0; border-bottom: 1px solid #f0e8e4; font-size: .94rem; }
.upgrade-line span { color: #746663; }
.upgrade-line strong { text-align: right; white-space: nowrap; }
.upgrade-total { background: #f9efed; border-radius: 10px; padding: 18px; margin: 20px 0; }
.upgrade-total span { display: block; color: #7d514d; font-size: .85rem; font-weight: 600; }
.upgrade-total strong { color: #8e2d26; font-size: 1.8rem; }
.upgrade-note { font-size: .9rem; line-height: 1.6; color: #665957; margin-bottom: 0; }
.upgrade-notice { padding: 15px 17px; margin-top: 18px; border-left: 3px solid #a95a42; background: #fff7f1; border-radius: 5px; color: #59443d; font-size: .9rem; line-height: 1.55; }
.upgrade-address { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.upgrade-field--wide { grid-column: 1 / -1; }
.upgrade-field label { display: block; font-weight: 600; font-size: .88rem; margin-bottom: 7px; }
.upgrade-field .form-control { min-height: 48px; border-color: #ded4cf; border-radius: 8px; }
.upgrade-field .form-control:focus { border-color: #a55a50; box-shadow: 0 0 0 .2rem rgba(142,45,38,.12); }
.upgrade-submit { background: #8e2d26; border: 1px solid #8e2d26; color: #fff; border-radius: 8px; padding: 12px 20px; font-weight: 700; width: 100%; margin-top: 22px; }
.upgrade-submit:hover { background: #75251f; color: #fff; }
@media(max-width: 767px) { .upgrade-page { padding: 36px 0 60px; } .upgrade-grid { grid-template-columns: 1fr; } .upgrade-card { padding: 22px; } }
@media(max-width: 480px) { .upgrade-address { grid-template-columns: 1fr; } .upgrade-field--wide { grid-column: auto; } }
</style>
@endpush
@section('content')
<div class="upgrade-page"><div class="container upgrade-wrap">
    <a class="upgrade-back" href="{{ route('user.subscriptions') }}">&larr; Back to packages</a>
    <header class="upgrade-heading">
        <p class="eyebrow">Your subscription</p>
        <h1>{{ $quote['to_type'] === 'print' ? 'Switch to Print' : 'Upgrade to Digital + Print' }}</h1>
        <p>{{ $quote['plan_name'] }} &middot; Current format: {{ ucfirst($quote['from_type']) }}</p>
    </header>
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="upgrade-grid">
        <section class="upgrade-card" aria-labelledby="upgrade-summary-heading">
            <h2 id="upgrade-summary-heading">Payment summary</h2>
            <p class="intro">Your change is calculated for the remaining subscription period.</p>
            <div class="upgrade-line"><span>Current format price</span><strong>{{ $quote['currency'] }} {{ $quote['current_format_price'] }}</strong></div>
            <div class="upgrade-line"><span>Selected format price</span><strong>{{ $quote['currency'] }} {{ $quote['combined_format_price'] }}</strong></div>
            <div class="upgrade-line"><span>Remaining period</span><strong>{{ $quote['remaining_days'] }} of {{ $quote['total_days'] }} days</strong></div>
            <div class="upgrade-total"><span>Upgrade payment</span><strong>{{ $quote['currency'] }} {{ $quote['upgrade_amount'] }}</strong></div>
            <p class="upgrade-note">Your expiry date stays <strong>{{ $subscription->end_date->format('d M Y H:i') }}</strong>. Your original support payment is unchanged; no additional support is charged.</p>
            @if($quote['to_type'] === 'print')<div class="upgrade-notice">After payment, this subscription will be print only and will no longer provide digital access. Other eligible purchases or subscriptions may still provide access.</div>@endif
            @if($pending)<div class="upgrade-notice">Your existing checkout keeps the price and delivery address saved when payment started. Continue using the same payment channel.</div>@else<div class="upgrade-notice">The final amount is calculated when checkout starts, using the exact time remaining.</div>@endif
        </section>
        <section class="upgrade-card" aria-labelledby="delivery-heading">
            <h2 id="delivery-heading">Print delivery address</h2>
            <p class="intro">Tell us where to deliver your magazine.</p>
            <form method="POST" action="{{ route('user.upgrades.checkout', $subscription) }}">
                @csrf
                <input type="hidden" name="target" value="{{ $quote['to_type'] }}">
                <div class="upgrade-address">
                    @foreach(\App\Support\PrintDelivery::FIELDS as $field)
                        <div class="upgrade-field {{ in_array($field, ['line1', 'line2']) ? 'upgrade-field--wide' : '' }}">
                            <label for="delivery-{{ $field }}">{{ ['name' => 'Recipient name', 'phone' => 'Phone', 'line1' => 'Address line 1', 'line2' => 'Address line 2 (optional)', 'city' => 'City', 'state' => 'State / Province', 'postal_code' => 'Postal code', 'country' => 'Country'][$field] }}</label>
                            <input id="delivery-{{ $field }}" class="form-control" name="delivery_address[{{ $field }}]" value="{{ old('delivery_address.'.$field, ($pending?->delivery_address ?? $subscription->delivery_address ?? [])[$field] ?? '') }}" maxlength="200" @required($field !== 'line2') @readonly((bool) $pending)>
                        </div>
                    @endforeach
                </div>
                <button class="upgrade-submit" type="submit">{{ $pending ? 'Continue existing checkout' : 'Continue to payment' }}</button>
            </form>
        </section>
    </div>
</div></div>
@endsection
