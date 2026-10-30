@extends('apparel.layout')
@section('title', 'Checkout')
@section('content')
@php
$nameParts = explode(' ', auth()->user()?->name ?? '', 2);
$total = $items->sum(fn ($item) => $item['price'] * $item['quantity']);
@endphp
<div class="checkout-layout">
<section class="checkout-details">
<a class="checkout-brand" href="{{ route('apparel.index') }}">YukanFaith Apparel</a>
<ol class="checkout-steps" aria-label="Checkout progress">
@foreach(['Contact', 'Shipping', 'Payment', 'Review'] as $step)<li data-progress="{{ $loop->index }}" @if($loop->first) aria-current="step" @endif><span>{{ $loop->iteration }}</span>{{ $step }}</li>@endforeach
</ol>
<div id="checkout-error" class="notice error" role="alert" hidden></div>
<form id="checkout-wizard" class="checkout-form" method="post" action="{{ route('apparel.checkout.store') }}" data-account-url="{{ route('apparel.checkout.account') }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
@csrf
<input type="hidden" name="checkout_token" value="{{ session('apparel_checkout_token') }}">
<section data-checkout-step="0">
<h1>Contact Information</h1><p>Add details below to receive delivery updates.</p>
<div class="checkout-fields">
<label>First name <em>*</em><input name="first_name" required maxlength="100" autocomplete="given-name" value="{{ old('first_name', $nameParts[0]) }}" placeholder="First name"></label>
<label>Last name <em>*</em><input name="last_name" required maxlength="100" autocomplete="family-name" value="{{ old('last_name', $nameParts[1] ?? '') }}" placeholder="Last name"></label>
</div>
<label>Email <em>*</em><input type="email" name="email" required maxlength="255" autocomplete="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="john.doe@example.com"></label>
<label>Phone <em>*</em><input type="tel" name="phone" required maxlength="50" autocomplete="tel" value="{{ old('phone') }}" placeholder="+1 555 123 4567"></label>
<div id="checkout-account-fields" @if(auth()->check()) hidden @endif>
<p>New here? We will create your account so you can track orders. Already registered? Enter your existing password to sign in.</p>
<label>Password <em>*</em><input type="password" name="password" autocomplete="current-password" maxlength="255" @required(!auth()->check())></label>
<label>Confirm password (new accounts only)<input type="password" name="password_confirmation" autocomplete="new-password" maxlength="255"></label>
<small>New passwords need at least 8 characters, uppercase and lowercase letters, a number and a symbol.</small>
<p><a href="{{ route('password.request') }}">Forgot your password?</a></p>
</div>
<div class="checkout-actions"><a href="{{ route('apparel.cart') }}">Back to cart</a><button type="button" data-checkout-next>Next: Shipping</button></div>
</section>
<section data-checkout-step="1" hidden>
<h1>Shipping Address</h1><p>Add a shipping address for your order.</p>
<label>Country <em>*</em><input name="country" required maxlength="100" autocomplete="country-name" value="{{ old('country') }}" placeholder="Country"></label>
<label>Street address <em>*</em><input name="street" required maxlength="255" autocomplete="address-line1" value="{{ old('street') }}" placeholder="Enter your address"></label>
<label>Street address line 2 (optional)<input name="street2" maxlength="255" autocomplete="address-line2" value="{{ old('street2') }}" placeholder="Apartment, suite, etc."></label>
<div class="checkout-fields"><label>City <em>*</em><input name="city" required maxlength="100" autocomplete="address-level2" value="{{ old('city') }}" placeholder="Enter city"></label><label>State/Region <em>*</em><input name="region" required maxlength="100" autocomplete="address-level1" value="{{ old('region') }}" placeholder="State/Region"></label></div>
<label>ZIP / Postal code <em>*</em><input name="postal_code" required maxlength="30" autocomplete="postal-code" value="{{ old('postal_code') }}" placeholder="Enter postal code"></label>
<div class="checkout-actions"><button type="button" class="secondary" data-checkout-back>Back</button><button type="button" data-checkout-next>Next: Payment</button></div>
</section>
<section data-checkout-step="2" hidden>
<h1>Payment Method</h1>
<div class="payment-later"><strong>Pay securely with Stripe</strong><p>After reviewing your order, continue to the secure payment form. Your order is confirmed as paid only after payment succeeds.</p></div>
<div class="checkout-actions"><button type="button" class="secondary" data-checkout-back>Back</button><button type="button" data-checkout-next>Next: Review</button></div>
</section>
<section data-checkout-step="3" hidden>
<h1>Review your order</h1><p>Check your contact and delivery details before placing your order.</p>
<div class="checkout-review"><h2>Contact</h2><p data-review-contact></p><h2>Shipping address</h2><p data-review-address></p><h2>Payment</h2><p>Secure online payment with Stripe</p></div>
<label>Order notes (optional)<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
<div class="checkout-actions"><button type="button" class="secondary" data-checkout-back>Back</button><button type="submit">Continue to payment</button></div>
</section>
</form>
<noscript><p>Please enable JavaScript to continue with checkout.</p></noscript>
</section>
<aside class="checkout-summary" aria-labelledby="summary-heading"><div class="summary-inner">
<h2 id="summary-heading">Order Summary</h2>
@foreach($items as $item)
@php($image = $item['variant']?->images->first() ?? $item['product']->primaryImage)
<article class="summary-item">
<div class="summary-image">@if($image)<img src="{{ asset($image->image_path) }}" alt="{{ $item['product']->name }}">@endif</div>
<div><p>{{ $item['product']->name }}</p>
@if($item['variant'])
@forelse($item['variant']->optionValues as $value)<small>{{ $value->option->name }}: {{ $value->value }}</small>@empty<small>{{ implode(' / ', array_filter([$item['variant']->color_name, $item['variant']->size, $item['variant']->option_value])) }}</small>@endforelse
@endif
<small>Quantity: {{ $item['quantity'] }}</small></div><strong>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
</article>
@endforeach
<div class="summary-costs"><p><span>Items ({{ $items->sum('quantity') }})</span><span>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($total, 2) }}</span></p><p><span>Shipping</span><span>Not included</span></p><p><span>Taxes</span><span>Not included</span></p></div>
<div class="summary-total"><span>Total</span><strong>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($total, 2) }}</strong></div>
<p class="summary-note">Order total: {{ \App\Support\ApparelMoney::prefix() }}{{ number_format($total, 2) }}. Products only; shipping and tax charges are not included.</p>
</div></aside>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/apparel-checkout.js') }}" defer></script>@endpush
