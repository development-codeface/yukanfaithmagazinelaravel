@extends('layouts.frontend')
@section('title', 'Subscriptions & Magazines')
@section('content')
<div class="pricing-page"><div class="container">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach
            Select your package again to correct your details.
        </div>
    @endif
    <header class="pricing-heading">
        <p class="pricing-eyebrow">Read your way</p>
        <h1>Your next great read starts here.</h1>
        <p>Buy a single magazine or choose a package that suits you.</p>
        <a href="{{ route('user.dashboard') }}">Back to my dashboard &rarr;</a>
    </header>
    <nav class="purchase-nav" aria-label="Purchase options">
        @foreach(['packages' => 'Subscription packages', 'magazines' => 'Single magazines'] as $view => $label)
            <a href="{{ route('user.subscriptions', array_filter(['view' => $view, 'magazine' => $selectedMagazine?->id, 'type' => $selectedType])) }}" class="{{ $purchaseView === $view ? 'active' : '' }}" @if($purchaseView === $view) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <section id="memberships" @if($purchaseView !== 'packages') hidden @endif>
        <div class="pricing-section-heading">
            <div><p class="pricing-eyebrow">A little more to look forward to</p><h2>Find your perfect package</h2></div>
            <span>Choose your format inside each package. All prices in INR.</span>
        </div>
        <div class="package-grid">
            @forelse($plans->filter(fn ($plan) => count($plan->prices())) as $plan)
                @include('user.package-card')
            @empty
                <p class="pricing-empty">No subscription packages are available yet.</p>
            @endforelse
        </div>
    </section>
    <section id="single-magazines" class="single-magazine-section" @if($purchaseView !== 'magazines') hidden @endif>
    @if($selectedMagazine)
        <section class="selected-magazine" aria-labelledby="selected-title">
            <div>
                <p class="pricing-eyebrow">Your selected magazine</p>
                <h2 id="selected-title">{{ $selectedMagazine->title }}</h2>
                <p>{{ $selectedMagazine->issue_date?->format('F Y') }}</p>
                <p>{{ \Illuminate\Support\Str::limit($selectedMagazine->description, 180) }}</p>
            </div>
            <div class="selected-magazine-action">
                @if($selectedMagazineCanRead)
                    <p>Already included in your library</p>
                    <a class="btn btn-dark" href="{{ route('user.dashboard') }}#magazines">Go to My Magazines</a>
                @elseif(!$selectedMagazine->prices())
                    <p>Available with an eligible digital package.</p>
                @endif
                @if($selectedMagazine->prices())
                    @include('partials.magazine-payment', ['magazineIssue' => $selectedMagazine])
                @endif
            </div>
            @unless($selectedMagazineCanRead)
                <p class="coverage-note">Package access depends on this magazine’s issue date falling within its coverage period. A single Digital or Both purchase gives lasting reader access. Print-only purchases include delivery without digital access.</p>
            @endunless
        </section>
    @endif

        <div class="pricing-section-heading"><div><p class="pricing-eyebrow">Single purchases</p><h2>Just one magazine?</h2></div><p>Choose Digital, Print, or Both. No package required.</p></div>
        <div class="magazine-grid">
            @forelse($magazines as $magazine)
                <article class="single-magazine-card">
                    <span class="text-muted">{{ $magazine->issue_date?->format('F Y') }}</span>
                    <h3>{{ $magazine->title }}</h3><p>{{ \Illuminate\Support\Str::limit($magazine->description, 100) }}</p>
                    @foreach($magazine->prices() as $type => $price)
                        <strong>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }}: INR {{ number_format((float) $price, 2) }}</strong>
                    @endforeach
                    @if(auth()->user()->canReadMagazine($magazine))
                        <a href="{{ route('user.dashboard') }}#magazines" class="btn btn-outline-dark">In your library</a>
                    @endif
                    <a href="{{ route('user.subscriptions', ['magazine' => $magazine->id, 'type' => $selectedType, 'view' => 'magazines']) }}" class="btn btn-outline-dark">Choose magazine format &rarr;</a>
                </article>
            @empty
                <p>Single magazines are not available yet.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $magazines->fragment('single-magazines')->appends(['type' => $selectedType, 'magazine' => $selectedMagazine?->id, 'view' => 'magazines'])->links('pagination::bootstrap-5') }}</div>
    </section>
</div></div>
<dialog id="delivery-dialog" aria-labelledby="delivery-title">
    <form method="POST" id="delivery-form">
        @csrf
        <input type="hidden" name="delivery_type" id="delivery-type">
        <h2 id="delivery-title">Print delivery address</h2>
        <p>Delivery is included in your selected package price.</p>
        @foreach(\App\Support\PrintDelivery::FIELDS as $field)
            <label class="d-block mb-2">
                {{ ['name' => 'Recipient name', 'phone' => 'Phone', 'line1' => 'Address line 1', 'line2' => 'Address line 2 (optional)', 'city' => 'City', 'state' => 'State / Province', 'postal_code' => 'Postal code', 'country' => 'Country'][$field] }}
                <input class="form-control" name="delivery_address[{{ $field }}]" value="{{ old('delivery_address.'.$field) }}" maxlength="200" {{ $field !== 'line2' ? 'required' : '' }} autocomplete="{{ ['name' => 'shipping name', 'phone' => 'shipping tel', 'line1' => 'shipping address-line1', 'line2' => 'shipping address-line2', 'city' => 'shipping address-level2', 'state' => 'shipping address-level1', 'postal_code' => 'shipping postal-code', 'country' => 'shipping country-name'][$field] }}">
            </label>
        @endforeach
        <button class="btn btn-dark" type="submit">Continue to checkout</button>
        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('delivery-dialog').close()">Cancel</button>
    </form>
</dialog>
<script>
function openDeliveryDialog(form) {
    document.getElementById('delivery-form').action = form.action;
    document.getElementById('delivery-type').value = form.querySelector('[name="delivery_type"]').value;
    document.getElementById('delivery-dialog').showModal();
    return false;
}
document.querySelectorAll('.package-format-tabs').forEach(tablist => {
    const tabs = [...tablist.querySelectorAll('[role="tab"]')];
    function select(tab) {
        tabs.forEach(button => {
            const active = button === tab;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
            document.getElementById(button.getAttribute('aria-controls')).hidden = !active;
        });
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => select(tab));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault(); select(tabs[next]); tabs[next].focus();
        });
    });
});
</script>
@endsection
@push('styles')
<style>
[hidden] { display:none !important; }
.pricing-page { padding:56px 0 72px; background:#faf8f5; color:#201918; min-height:75vh; }
.pricing-heading { max-width:760px; margin-bottom:32px; }
.pricing-heading h1 { font-size:clamp(32px,4vw,52px); font-weight:750; letter-spacing:-1px; margin:8px 0 16px; }
.pricing-heading p,.format-description { color:#706762; }
.pricing-heading a,.pricing-eyebrow { color:#8e2d26; }
.pricing-eyebrow { font-size:12px; text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-bottom:8px; }
.purchase-nav { display:inline-flex; gap:6px; flex-wrap:wrap; background:#eee9e3; border:1px solid #e4ddd4; padding:6px; border-radius:14px; margin-bottom:36px; }
.purchase-nav a { color:#71645b; font-weight:650; text-decoration:none; padding:13px 24px; border-radius:10px; }
.purchase-nav a.active { color:#fff; background:#8e2d26; box-shadow:0 3px 10px #8e2d2620; }
.pricing-section-heading { display:flex; align-items:end; justify-content:space-between; gap:24px; margin-bottom:24px; }
.pricing-section-heading h2 { font-size:30px; margin:0; }
.pricing-section-heading>p,.pricing-section-heading>span { font-size:14px; color:#706762; margin:0; }
.selected-magazine { display:grid; grid-template-columns:1fr auto; gap:16px 32px; border:1px solid #d9cbc5; background:#fcf8f5; border-radius:12px; padding:28px; margin-bottom:48px; }
.selected-magazine h2 { font-size:24px; }
.selected-magazine p { margin-bottom:8px; }
.selected-magazine-action { align-self:center; }
.single-price { font-size:28px; font-weight:750; }
.coverage-note { grid-column:1/-1; font-size:13px; color:#706762; border-top:1px solid #e5dbd4; padding-top:16px; }
.format-tabs { display:inline-flex; flex-wrap:wrap; border-radius:32px; background:#f1efed; padding:6px; gap:4px; margin:4px 0 16px; }
.format-tabs a { padding:10px 24px; border-radius:28px; color:#5d534d; text-decoration:none; font-weight:600; }
.format-tabs .active { background:#fff; color:#201918; box-shadow:0 2px 6px #0000000c; }
.format-description { font-size:14px; min-height:24px; margin-bottom:32px; }
.package-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:24px; }
.package-card { display:flex; flex-direction:column; padding:28px; border:1px solid #e4ddd4; border-radius:18px; background:#fff; box-shadow:0 8px 28px #3d271508; }
.package-card:hover { border-color:#bda598; }
.package-format-tabs { display:flex; background:#f6f3ef; padding:5px; border-radius:12px; gap:4px; margin:4px 0 8px; }
.package-format-tabs button { flex:1; min-width:0; padding:10px 4px; border:1px solid transparent; border-radius:9px; background:transparent; color:#7b7066; font-size:12px; }
.package-format-tabs button span,.package-format-tabs button strong { display:block; }
.package-format-tabs button strong { font-size:12px; margin-top:5px; }
.package-format-tabs button[aria-selected="true"] { background:#fff; border-color:#ddc9bc; color:#8e2d26; box-shadow:0 2px 5px #00000008; }
.package-format-tabs button:focus-visible { outline:2px solid #8e2d26; outline-offset:2px; }
.package-format-panel { flex:1; display:flex; flex-direction:column; }
.package-card.current { background:#fcf8f5; }
.package-card-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
.package-card h3 { font-size:25px; margin:0; }
.package-description { color:#706762; min-height:48px; font-size:14px; }
.package-price { font-size:30px; font-weight:750; margin:18px 0 4px; }
.package-duration { color:#706762; border-bottom:1px solid #e6e1dc; padding-bottom:22px; }
.format-label { font-size:15px; font-weight:650; }
.package-card ul { padding-left:18px; line-height:1.9; color:#5d534d; margin-bottom:24px; }
.package-coverage { font-size:13px; line-height:1.6; }
.package-select { margin-top:auto; padding-top:24px; }
.package-select .btn { padding:13px 20px; border-radius:8px; }
.pricing-empty { padding:32px 0; }
.single-magazine-section { scroll-margin-top:32px; }
.magazine-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
.single-magazine-card { display:flex; flex-direction:column; gap:12px; border:1px solid #e6e1dc; border-radius:16px; padding:24px; background:#fff; }
.single-magazine-card h3 { font-size:21px; margin:0; }
.single-magazine-card p { font-size:14px; color:#706762; flex:1; }
#delivery-dialog { width:min(95vw,540px); max-height:90vh; overflow-y:auto; border:0; border-radius:12px; padding:28px; }
#delivery-dialog::backdrop { background:#0008; }
#delivery-dialog h2 { font-size:24px; }
@media(max-width:991px) { .package-grid,.magazine-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .pricing-page { padding:28px 0 48px; } .package-grid,.magazine-grid,.selected-magazine { grid-template-columns:1fr; } .pricing-section-heading { align-items:start; flex-direction:column; gap:12px; } .package-card { border-left:1px solid #e6e1dc; padding:24px; } .format-tabs a { padding:10px 16px; } }
</style>
@endpush
