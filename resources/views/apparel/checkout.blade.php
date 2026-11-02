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
@foreach(['Your details', 'Secure payment', 'Confirmation'] as $step)<li @if($loop->first) aria-current="step" @endif><span>{{ $loop->iteration }}</span>{{ $step }}</li>@endforeach
</ol>
<div id="checkout-error" class="notice error" role="alert" hidden></div>
<form id="checkout-wizard" class="checkout-form" method="post" action="{{ route('apparel.checkout.store') }}" data-account-url="{{ route('apparel.checkout.account') }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
@csrf
<input type="hidden" name="checkout_token" value="{{ session('apparel_checkout_token') }}">
<input type="hidden" name="payment_flow" value="hosted">
<h1>Checkout</h1><p>Enter your contact and delivery details, then continue to secure payment.</p>
@auth
<div class="checkout-signed-in">Signed in as <strong>{{ auth()->user()->email }}</strong></div>
@else
<fieldset class="checkout-account-choice"><legend>Your account</legend>
<label><input type="radio" name="account_mode" value="register" @checked(old('account_mode', 'register') === 'register')> Create account</label>
<label><input type="radio" name="account_mode" value="login" @checked(old('account_mode') === 'login')> Sign in</label>
</fieldset>
@endauth
<h2 class="checkout-section-heading">Contact Information</h2>
<div class="checkout-fields">
<label>First name <em>*</em><input name="first_name" required maxlength="100" autocomplete="given-name" value="{{ old('first_name', $nameParts[0]) }}" placeholder="First name"></label>
<label>Last name <em>*</em><input name="last_name" required maxlength="100" autocomplete="family-name" value="{{ old('last_name', $nameParts[1] ?? '') }}" placeholder="Last name"></label>
</div>
<label>Email <em>*</em><input type="email" name="email" required maxlength="255" autocomplete="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="john.doe@example.com"></label>
<label>Phone <em>*</em><input type="tel" name="phone" required maxlength="50" autocomplete="tel" value="{{ old('phone') }}" placeholder="+1 555 123 4567"></label>
<div id="checkout-account-fields" @if(auth()->check()) hidden @endif>
<p id="account-help">Create an account to track your order, or choose Sign in if you already have one.</p>
<label>Password <em>*</em><input type="password" name="password" autocomplete="current-password" maxlength="255" @required(!auth()->check())></label>
<div id="checkout-register-fields"><label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" maxlength="255" @required(!auth()->check())></label>
<small>Use at least 8 characters, uppercase and lowercase letters, a number and a symbol.</small></div>
<p><a href="{{ route('password.request') }}">Forgot your password?</a></p>
</div>
<h2 class="checkout-section-heading">Shipping Address</h2><p>Where should we deliver your order?</p>
<label>Country <em>*</em><input name="country" required maxlength="100" autocomplete="country-name" value="{{ old('country') }}" placeholder="Country"></label>
<label>Street address <em>*</em><input name="street" required maxlength="255" autocomplete="address-line1" value="{{ old('street') }}" placeholder="Enter your address"></label>
<label>Street address line 2 (optional)<input name="street2" maxlength="255" autocomplete="address-line2" value="{{ old('street2') }}" placeholder="Apartment, suite, etc."></label>
<div class="checkout-fields"><label>City <em>*</em><input name="city" required maxlength="100" autocomplete="address-level2" value="{{ old('city') }}" placeholder="Enter city"></label><label>State/Region <em>*</em><input name="region" required maxlength="100" autocomplete="address-level1" value="{{ old('region') }}" placeholder="State/Region"></label></div>
<label>ZIP / Postal code <em>*</em><input name="postal_code" required maxlength="30" autocomplete="postal-code" value="{{ old('postal_code') }}" placeholder="Enter postal code"></label>
<label>Order notes (optional)<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
<div class="checkout-payment-note"><strong>Next: secure payment</strong><p>Enter your payment details on Stripe's secure page. We will confirm your order once payment succeeds.</p></div>
<p class="checkout-pay-total">Total to pay: <strong>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($total, 2) }}</strong></p>
<p id="checkout-status" role="status" aria-live="polite"></p>
<div class="checkout-actions"><a href="{{ route('apparel.cart') }}">Back to cart</a><button type="submit" id="checkout-submit">Continue to secure payment</button></div>
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
