        <form id="magazine-payment" method="POST" action="{{ route('user.magazine.checkout', $magazineIssue) }}">
            @csrf
            <button class="btn btn-dark">Buy Now — {{ strtoupper(config('services.stripe.currency', 'inr')) }} {{ number_format($magazineIssue->price, 2) }}</button>
        </form>
        <div id="magazine-checkout" class="mt-3"></div>
        <p id="payment-error" role="alert" class="text-danger"></p>
        <script src="https://js.stripe.com/dahlia/stripe.js"></script>
        <script>
        document.getElementById('magazine-payment').addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = this.querySelector('button');
            button.disabled = true;
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
                const checkout = await stripe.createEmbeddedCheckoutPage({fetchClientSecret: async () => data.client_secret});
                checkout.mount('#magazine-checkout');
                this.hidden = true;
            } catch (error) {
                document.getElementById('payment-error').textContent = error.message;
                button.disabled = false;
            }
        });
        </script>
