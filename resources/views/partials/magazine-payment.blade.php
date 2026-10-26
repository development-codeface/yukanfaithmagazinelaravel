        <form id="magazine-payment" method="POST" action="{{ route('user.magazine.checkout', $magazineIssue) }}">
            @csrf
            <button class="btn btn-dark">Buy Now — {{ strtoupper(config('services.stripe.currency', 'inr')) }} {{ number_format($magazineIssue->price, 2) }}</button>
        </form>
        <dialog id="magazine-payment-dialog" aria-labelledby="magazine-payment-heading">
            <header class="magazine-payment-header">
                <h2 id="magazine-payment-heading">Buy {{ $magazineIssue->title }}</h2>
                <form method="dialog"><button class="magazine-payment-close" aria-label="Close payment" autofocus>&times;</button></form>
            </header>
            <div class="magazine-payment-body">
                <p id="payment-loading" role="status">Loading secure payment&hellip;</p>
                <p id="payment-error" role="alert" class="text-danger"></p>
                <button id="payment-retry" type="button" class="btn btn-dark mb-3" hidden>Try again</button>
                <div id="magazine-checkout"></div>
            </div>
        </dialog>
        <style>
        #magazine-payment-dialog { width: min(760px, calc(100vw - 24px)); max-width: none; max-height: 90dvh; padding: 0; border: 0; border-radius: 14px; overflow: auto; overscroll-behavior: contain; box-shadow: 0 20px 70px rgba(0,0,0,.25); }
        #magazine-payment-dialog::backdrop { background: rgba(0,0,0,.6); }
        .magazine-payment-header { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; background: #fff; border-bottom: 1px solid #eee; }
        .magazine-payment-header h2 { margin: 0; font-size: 20px; }
        .magazine-payment-close { border: 0; background: none; font-size: 32px; line-height: 1; padding: 6px; }
        .magazine-payment-body { padding: 16px; }
        body:has(#magazine-payment-dialog[open]) { overflow: hidden; }
        @media (max-width: 575px) { .magazine-payment-header { padding: 12px; } .magazine-payment-body { padding: 8px; } }
        </style>
        <script src="https://js.stripe.com/dahlia/stripe.js"></script>
        <script>
        (() => {
        const form = document.getElementById('magazine-payment');
        const dialog = document.getElementById('magazine-payment-dialog');
        const loading = document.getElementById('payment-loading');
        const retry = document.getElementById('payment-retry');
        let checkout = null;
        let pending = false;
        form.querySelector('button').setAttribute('aria-haspopup', 'dialog');
        form.querySelector('button').setAttribute('aria-controls', dialog.id);
        retry.addEventListener('click', () => form.requestSubmit());
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!dialog.open) dialog.showModal();
            if (checkout || pending) return;
            pending = true;
            loading.hidden = false;
            retry.hidden = true;
            document.getElementById('payment-error').textContent = '';
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.querySelector('[name="_token"]').value},
                    body: JSON.stringify({embedded: true})
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to start payment.');
                if (data.can_read) { window.location.href = @json(route('user.magazine.reader', $magazineIssue)); return; }
                const stripe = Stripe(@json(config('services.stripe.key')));
                const instance = await stripe.createEmbeddedCheckoutPage({fetchClientSecret: async () => data.client_secret});
                try {
                    instance.mount('#magazine-checkout');
                    checkout = instance;
                } catch (error) {
                    instance.destroy();
                    throw error;
                }
            } catch (error) {
                document.getElementById('payment-error').textContent = error.message;
                retry.hidden = false;
            } finally {
                pending = false;
                loading.hidden = true;
            }
        });
        })();
        </script>
