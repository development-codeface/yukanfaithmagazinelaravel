<form id="magazine-payment" method="POST" action="{{ route('user.magazine.checkout', $magazineIssue) }}">
    @csrf
    <label for="magazine-format" class="d-block mb-2">Magazine format</label>
    <select id="magazine-format" name="delivery_type" class="form-select mb-3">
        @foreach($magazineIssue->prices() as $type => $price)
            <option value="{{ $type }}" @selected(old('delivery_type', $selectedType ?? request('type', 'digital')) === $type)>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }} — INR {{ number_format((float) $price, 2) }}</option>
        @endforeach
    </select>
    <p class="small text-muted">Print and Both include delivery. Digital and Both include online reading.</p>
    <div class="d-flex flex-wrap gap-2 align-items-center">
    <button class="btn btn-dark" type="submit">{{ ($showSubscribe ?? false) ? 'Buy Now' : 'Buy magazine' }}</button>
    @if($showSubscribe ?? false)
        <a href="#memberships" class="btn btn-outline-dark">Subscribe</a>
    @endif
    <button class="btn btn-outline-secondary" type="button" id="magazine-edit-address" hidden>Edit delivery address</button>
    </div>
</form>
<dialog id="magazine-address-dialog" aria-labelledby="magazine-address-heading">
    <form id="magazine-address-form">
        <h2 id="magazine-address-heading" class="h4">Magazine delivery address</h2>
        @foreach(\App\Support\PrintDelivery::FIELDS as $field)
            <label class="d-block mb-2">
                {{ ['name' => 'Recipient name', 'phone' => 'Phone', 'line1' => 'Address line 1', 'line2' => 'Address line 2 (optional)', 'city' => 'City', 'state' => 'State / Province', 'postal_code' => 'Postal code', 'country' => 'Country'][$field] }}
                <input class="form-control" name="{{ $field }}" value="{{ old('delivery_address.'.$field) }}" maxlength="200" @required($field !== 'line2')>
            </label>
        @endforeach
        <button type="submit" class="btn btn-dark">Continue to payment</button>
        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('magazine-address-dialog').close()">Cancel</button>
    </form>
</dialog>
<dialog id="magazine-payment-dialog" aria-labelledby="magazine-payment-heading">
    <header class="magazine-payment-header">
        <h2 id="magazine-payment-heading">Buy {{ $magazineIssue->title }}</h2>
        <form method="dialog"><button class="magazine-payment-close" aria-label="Close payment">&times;</button></form>
    </header>
    <div class="magazine-payment-body">
        <p id="payment-loading" role="status">Loading secure payment&hellip;</p>
        <p id="payment-error" role="alert" class="text-danger"></p>
        <button id="payment-retry" type="button" class="btn btn-dark mb-3" hidden>Try again</button>
        <div id="magazine-checkout"></div>
    </div>
</dialog>
<style>
#magazine-payment-dialog { width:min(760px,calc(100vw - 24px)); max-width:none; max-height:90dvh; padding:0; border:0; border-radius:14px; overflow:auto; overscroll-behavior:contain; box-shadow:0 20px 70px #0004; }
#magazine-address-dialog { width:min(540px,95vw); max-height:90vh; overflow:auto; border:0; border-radius:12px; padding:24px; }
#magazine-payment-dialog::backdrop,#magazine-address-dialog::backdrop { background:#0009; }
.magazine-payment-header { position:sticky; top:0; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 20px; background:#fff; border-bottom:1px solid #eee; }
.magazine-payment-header h2 { margin:0; font-size:20px; }
.magazine-payment-close { border:0; background:none; font-size:32px; line-height:1; padding:6px; }
.magazine-payment-body { padding:16px; }
body:has(#magazine-payment-dialog[open]),body:has(#magazine-address-dialog[open]) { overflow:hidden; }
</style>
<script src="https://js.stripe.com/dahlia/stripe.js"></script>
<script>
(() => {
    const form = document.getElementById('magazine-payment');
    const format = document.getElementById('magazine-format');
    const dialog = document.getElementById('magazine-payment-dialog');
    const addressDialog = document.getElementById('magazine-address-dialog');
    const addressForm = document.getElementById('magazine-address-form');
    const editAddress = document.getElementById('magazine-edit-address');
    const loading = document.getElementById('payment-loading');
    const retry = document.getElementById('payment-retry');
    let checkout = null, pending = false, address = null;
    form.querySelector('[type="submit"]').setAttribute('aria-haspopup', 'dialog');
    editAddress.hidden = format.value === 'digital';
    editAddress.addEventListener('click', () => addressDialog.showModal());
    retry.addEventListener('click', () => form.requestSubmit());
    format.addEventListener('change', () => {
        if (checkout) checkout.destroy();
        checkout = null;
        editAddress.hidden = format.value === 'digital';
    });
    addressForm.addEventListener('submit', event => {
        event.preventDefault();
        address = Object.fromEntries(new FormData(addressForm));
        if (checkout) checkout.destroy();
        checkout = null;
        addressDialog.close();
        form.requestSubmit();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (format.value !== 'digital' && !address) { addressDialog.showModal(); return; }
        if (!dialog.open) dialog.showModal();
        if (checkout || pending) return;
        pending = true;
        format.disabled = true;
        loading.hidden = false;
        retry.hidden = true;
        document.getElementById('payment-error').textContent = '';
        try {
            const response = await fetch(form.action, {
                method:'POST',
                headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':form.querySelector('[name="_token"]').value},
                body:JSON.stringify({embedded:true, delivery_type:format.value, delivery_address:format.value === 'digital' ? null : address})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Unable to start payment.'));
            if (data.already_purchased || (data.can_read && !data.requires_purchase)) { window.location.href = @json(route('user.dashboard')); return; }
            const stripe = Stripe(@json(config('services.stripe.key')));
            const instance = await stripe.createEmbeddedCheckoutPage({fetchClientSecret:async () => data.client_secret});
            try { instance.mount('#magazine-checkout'); checkout = instance; }
            catch (error) { instance.destroy(); throw error; }
        } catch (error) {
            document.getElementById('payment-error').textContent = error.message;
            retry.hidden = false;
        } finally {
            pending = false;
            format.disabled = false;
            loading.hidden = true;
        }
    });
})();
</script>
