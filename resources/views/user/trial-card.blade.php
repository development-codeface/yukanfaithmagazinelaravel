@php($annual = \App\Models\Plan::find($plan->trial_conversion_plan_id))
@if($annual && $annual->status && (float) $annual->digital_price > 0)
<article class="package-card" id="package-{{ $plan->id }}">
    <h3>{{ $plan->name }}</h3>
    <p class="package-price">{{ $plan->trialDays() }} days free</p>
    <p>Digital magazine access. Payment details required.</p>
    <p>Then {{ strtoupper(config('services.stripe.currency', 'usd')) }} {{ number_format((float) $annual->digital_price, 2) }} for one year of {{ $annual->name }} Digital. No annual renewal.</p>
    <p>Cancel from this page before the trial ends to avoid the annual charge. One trial per account.</p>
    @auth
        @if(($trialEnrollment && $trialEnrollment->status !== 'pending') || auth()->user()->subscriptions()->whereHas('plan', fn ($q) => $q->where('is_trial', true))->exists())
            <p class="text-muted">You have already used your trial.</p>
        @elseif(!config('services.stripe.secret'))
            <p class="text-muted">Trial payment setup is temporarily unavailable.</p>
        @else
            <form method="POST" action="{{ route('user.trial.checkout', $plan) }}">
                @csrf
                <label class="mb-3"><input type="checkbox" name="accept_trial_billing" value="1" required> I agree to the annual Digital charge after {{ $plan->trialDays() }} days unless I cancel.</label>
                <button class="btn btn-dark w-100">Start trial and set up payment</button>
            </form>
        @endif
    @else
        <a class="btn btn-dark" href="{{ route('user.package.select', $plan) }}">Sign in to start trial</a>
    @endauth
</article>
@endif
