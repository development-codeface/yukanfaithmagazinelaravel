# Selected trial, public packages, and optional support

Registration (web and API) now creates an account without granting a trial.
Previously granted trials and purchases are retained. Upgrades are unchanged.

## Setup

1. Run `php artisan migrate` on deployment. The new migration is
   `2026_11_09_000001_add_trial_billing_and_package_support.php` (already run locally).
2. In Admin → Plans → edit **Free Trial**, select the annual Basic Digital
   conversion package. It must be active, have a positive Digital price, and a
   duration of one year or twelve months. Save. The new trial always lasts 7 days.
   The existing annual package can be selected without changing its name/prices.
3. Configure the existing Stripe keys/currency. No webhook secret is required.
4. Run Laravel's scheduler continuously. On production, add a cron entry
   (replace the path with the deployed application path):
   `* * * * * cd /path/to/mag && php artisan schedule:run >> /dev/null 2>&1`.
   On Windows/local development, keep `php artisan schedule:work` running in a
   terminal from the project directory. `php artisan trials:sync` runs one sync
   manually. The scheduled command runs every minute without overlapping runs.
   Monitor command failures and application logs. Without a running scheduler,
   browser-return confirmation still works, but abandoned returns and later
   billing changes will not be recovered automatically.
5. Optionally configure `POST https://YOUR-DOMAIN/stripe/subscriptions/webhook`
   for faster updates. Subscribe that endpoint to `checkout.session.completed`,
   `customer.subscription.created`, `customer.subscription.updated`,
   `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed`.
   Put its signing secret in `STRIPE_SUBSCRIPTION_WEBHOOK_SECRET`.
6. Enable Stripe trial reminders/payment-failure notifications in the Stripe
   dashboard, then test real Checkout and scheduled sync in Stripe test mode.
   Clear/rebuild Laravel config cache after changing environment values.

Local configuration: Free Trial (ID 5) converts to Annual Reader Digital (ID 4),
899.00 in the configured Stripe currency, as confirmed. The subscription webhook
signing secret is optional; Checkout uses the existing Stripe API secret.
Package support amounts default to
zero until the administrator sets them.

The trial card is shown when a conversion package is configured. The client must
accept the disclosed charge before starting Checkout. Stripe collects the card;
this application never receives card numbers. Setup grants 7 days of Digital
access. Stripe attempts the snapshotted annual Digital charge at trial end.
Only a paid invoice grants the annual access period. Payment failure gives no
new annual access. Stripe can require further authentication; the charge is not
guaranteed to succeed.

Stripe represents this as an annual subscription. On Checkout completion the
backend schedules `cancel_at` for trial end plus one calendar year, so there is
**one annual purchase and no following-year renewal**. The authenticated return
confirms setup immediately. The scheduler retrieves stored Checkout sessions,
verifies ownership, recovers completed sessions, synchronizes subscription state,
and checks all pages of paid invoices directly with Stripe. It also schedules
cancellation when the browser never returns. A scheduler outage delays recovery
and access updates; restore it promptly, especially before trial/year end.
Repeated callbacks are safe. Completing setup never extends a trial twice.
One trial per account, including accounts with a previously granted trial.

Cancellation is available on `/user/subscriptions` from the dashboard. During the
trial it prevents the scheduled annual payment and preserves the remaining free
days. After the annual payment it preserves paid access until the end of the
year; it does not issue a refund. The first annual charge can already be in
progress if cancellation happens after the trial ends.

## Mobile trial API

Use a bearer token and `Accept: application/json` on all `/api/user/*` requests.

- `GET /api/plans` is public; the Free Trial includes `trial_days`,
  `trial_conversion`, and `trial_checkout_endpoint`. Paid packages include
  `support_amount` and `support_optional`.
- `POST /api/user/trial/{trial_plan_id}/checkout`
  with `{"accept_trial_billing": true}` returns `checkout_url`, `session_id`,
  `trial_days`, and `requires_payment_setup: true`.
- Open `checkout_url` in a secure browser. This is a hosted Checkout flow, not a
  PaymentIntent/PaymentSheet. A cancelled Checkout does not grant trial access.
- After completion, `POST /api/user/trial/confirm` with
  `{"session_id": "cs_..."}`. The Stripe webhook also handles completion if the
  app/browser closes when configured; the scheduler also recovers it without a
  webhook. The success URL is the website's authenticated return page;
  mobile should resume the app and confirm its saved session, or poll trial status.
- `GET /api/user/trial` returns `{"trial": null}` or the current enrollment,
  including `status`, `annual_amount`, `trial_ends_at`, `ends_at`, and
  `cancel_requested`.
- `POST /api/user/trial/cancel` cancels the authenticated user's scheduled billing.

Existing subscribe/payment-intent endpoints route a trial plan to the same hosted
Checkout and require `accept_trial_billing`. Do not pass the trial response to a
PaymentSheet. No user ID is required; ownership comes from the bearer token.
Do not offer a second Digital purchase while scheduled trial billing is active;
the backend requires cancellation first to avoid duplicate charges.

## Optional support with paid packages

Admin sets one fixed optional `support_amount` per package. Zero hides the option.
The web form offers a checkbox. For mobile, send `include_support: true` (or omit
it/false) to the existing subscription payment-intent endpoint, with the selected
`delivery_type` and delivery address when required. The server ignores client
amounts and uses the configured support amount. Support is one-time and paid
together with the selected package; there is no separate support payment endpoint.
The free trial itself has no support charge.

The PaymentIntent response includes `plan_amount`, `support_amount`, and total
`amount` in minor units. Stripe payment metadata preserves the support snapshot;
subsequent package edits do not rewrite purchases. `subscriptions.amount_paid`
remains the **total paid**, including support; `subscriptions.support_amount`
is the support component. Package amount = total minus support. Admin subscription
history displays Package Amount / Support / Total Paid. No support is displayed
as **Not available**. Payment history also returns the support component.

## Public website

`/user/subscriptions` now shows packages without authentication. Package selection
requires login and returns to the selected package. The homepage's Subscribe
header button uses this public page. Latest and past magazine previews show Read
More and Subscribe for guests; signed-in users see Read Article, and Subscribe /
Buy this magazine if they lack reading access. Server-side access checks remain.

## Verification

Feature tests use mocked Stripe responses and cover public browsing, registration
without a trial, consent, payment-method confirmation, ownership, cancellation,
single-year scheduling, signed webhooks, failed payments, replay handling, and
support totals/snapshots. Tests also cover checkout without a webhook secret,
scheduled checkout/payment recovery, duplicate syncs, and per-enrollment provider
failures. Live Stripe Checkout and Test Clock testing still needs the configured
conversion package, working Stripe keys, and a running scheduler.
