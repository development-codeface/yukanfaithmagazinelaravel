(() => {
    const form = document.getElementById('order-payment');
    if (!form) return;
    const start = document.getElementById('start-payment');
    const submit = document.getElementById('submit-payment');
    const verify = document.getElementById('verify-payment');
    const error = document.getElementById('payment-error');
    const status = document.getElementById('payment-status');
    let stripe, elements, intentId, busy = false;
    const post = async (url, data = {}) => {
        const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value}, body: JSON.stringify(data)});
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Unable to complete this request. Please try again.');
        return result;
    };
    const run = async action => {
        if (busy) return;
        busy = true;
        error.hidden = true;
        [start, submit, verify].forEach(button => { button.disabled = true; });
        try { await action(); }
        catch (e) { error.textContent = e.message || 'Unable to connect. Please try again.'; error.hidden = false; }
        finally { busy = false; [start, submit, verify].forEach(button => { button.disabled = false; }); }
    };
    const confirm = async () => {
        status.textContent = 'Verifying payment…';
        try {
            const result = await post(form.dataset.confirmUrl, {payment_intent_id: intentId});
            window.location.assign(result.redirect);
        } finally { status.textContent = ''; }
    };
    start.addEventListener('click', () => run(async () => {
        if (!window.Stripe) throw new Error('Payment form could not load. Please refresh and try again.');
        const data = await post(form.dataset.startUrl);
        // An intent can already exist even if a previous request lost its response.
        document.getElementById('cancel-order')?.setAttribute('hidden', '');
        stripe = Stripe(data.publishable_key);
        intentId = data.payment_intent_id;
        verify.hidden = false;
        const result = await stripe.retrievePaymentIntent(data.client_secret);
        if (result.error) throw new Error(result.error.message);
        if (result.paymentIntent.status === 'succeeded') { await confirm(); return; }
        if (result.paymentIntent.status === 'processing') {
            status.textContent = 'Your payment is processing. Check its status again shortly.';
            return;
        }
        elements?.getElement('payment')?.destroy();
        elements = stripe.elements({clientSecret: data.client_secret});
        elements.create('payment').mount('#payment-element');
        start.hidden = true;
        submit.hidden = false;
    }));
    verify.addEventListener('click', () => run(confirm));
    form.addEventListener('submit', event => {
        event.preventDefault();
        run(async () => {
            if (!stripe || !elements) throw new Error('Please start payment first.');
            const result = await stripe.confirmPayment({elements, confirmParams: {return_url: form.dataset.returnUrl}, redirect: 'if_required'});
            if (result.error) throw new Error(result.error.message);
            if (result.paymentIntent?.status === 'succeeded') await confirm();
            else status.textContent = 'Payment is processing or needs another step. Check its status again shortly.';
        });
    });
})();
