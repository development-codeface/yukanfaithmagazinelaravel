# Subscription upgrades (web and mobile)

Supported: **Digital → Digital + Print** and **Print → Digital + Print**, on the
same purchased package. The subscription must currently be active, paid, and have
a finite expiry date. Trial packages and already combined packages are excluded.
An uncancelled trial with pending annual billing must be completed or cancelled
before using this flow. No downgrade or package-duration change is supported.

## Calculation and records

Upgrade charge = (combined full-period price − current full-period format price)
× remaining seconds / full subscription seconds, rounded once to minor units.
Example: Digital 100, combined 200, 15 of 30 days remaining → charge 50.
Support is excluded and is not collected again. Original start/end dates, amount
paid, support, and transaction ID are preserved. Only the format and print
delivery details change, after verified payment. Print → combined unlocks digital
access; Digital → combined starts a print delivery request. An existing print
review is preserved when its delivery address is unchanged.

New payments snapshot format prices in Stripe metadata and the local subscription.
Trial conversions snapshot annual package prices at trial selection. Later admin
price changes do not affect those snapshots. For older purchases, historical
combined prices cannot be reconstructed: the migration freezes the current plan
prices, using the original package payment (excluding support) as source price.
These are marked `price_snapshot_source: legacy_current_prices`. Review these
baselines before enabling upgrades on production.

The quote changes with time until checkout starts. Once a payment attempt is
created, its price, address, and expiry date are frozen for retries. One upgrade
record per subscription prevents repeated upgrades; one payment channel prevents
simultaneous web/mobile payments for the same upgrade. Resume the original
channel to finish a pending payment. Failed/cancelled payments do not change
access. Returning to packages never confirms a payment by itself.

## Mobile API

Use `Authorization: Bearer <token>` and `Accept: application/json` on every call.
POST bodies use `Content-Type: application/json`. User ownership comes from the
token; client-provided amounts, package IDs, target format, and user IDs cannot
change the server-calculated upgrade.

### Discover available upgrades

`GET /api/user/subscription/upgrades`

No body. Response (relevant fields):

```json
{
  "data": [{
    "subscription_id": 12,
    "plan_id": 4,
    "plan_name": "Annual Reader",
    "from_type": "digital",
    "to_type": "both",
    "current_format_price": "100.00",
    "combined_format_price": "200.00",
    "amount": 5000,
    "upgrade_amount": "50.00",
    "currency": "INR",
    "remaining_days": 15,
    "total_days": 30,
    "ends_at": "2026-12-01T00:00:00+00:00",
    "support_amount": "0.00",
    "price_snapshot_source": "purchase",
    "quote_endpoint": "https://HOST/api/user/subscriptions/12/upgrade",
    "payment_endpoint": "https://HOST/api/user/subscriptions/12/upgrade/payment-intent"
  }]
}
```

An empty `data` means no eligible upgrades. Display the options on the matching
package's combined-format panel. `/api/user/subscription/status` also returns
`upgrade_options_endpoint`.

### Review one quote

`GET /api/user/subscriptions/{subscription_id}/upgrade`

No body. Response: `{"quote": { ...same quote fields... }}`.

### Start upgrade payment

`POST /api/user/subscriptions/{subscription_id}/upgrade/payment-intent`

```json
{
  "delivery_address": {
    "name": "Reader",
    "phone": "1234567890",
    "line1": "12 Main Street",
    "line2": "",
    "city": "Kochi",
    "state": "Kerala",
    "postal_code": "682001",
    "country": "India"
  }
}
```

Address is required for Digital → combined. For Print → combined, omit it to use
the existing delivery address. All address fields except line2 are required.

```json
{
  "upgrade_id": 8,
  "subscription_id": 12,
  "requires_payment": true,
  "amount": 5000,
  "currency": "inr",
  "publishable_key": "pk_test_example",
  "client_secret": "pi_example_secret_example",
  "payment_intent_id": "pi_example",
  "ends_at": "2026-12-01T00:00:00+00:00",
  "confirm_endpoint": "https://HOST/api/user/subscription-upgrades/8/confirm"
}
```

`amount` is in minor units. Initialize Stripe PaymentSheet using the PaymentIntent
client secret; this flow does not require a customer or ephemeral key. After SDK
success, call the confirm endpoint. Do not unlock the combined format locally.
If an earlier payment already succeeded, retrying the start endpoint can instead
return `requires_payment: false`, the paid upgrade, and the updated subscription.

### Confirm payment

`POST /api/user/subscription-upgrades/{upgrade_id}/confirm`

No body is required. The server fetches the stored payment directly from Stripe
and checks status, exact amount, currency, and ownership metadata. Response:

```json
{
  "message": "Package upgraded to Digital + Print. The expiry date is unchanged.",
  "upgrade": {"id": 8, "status": "paid", "amount_minor": 5000},
  "subscription": {"id": 12, "delivery_type": "both", "print_enabled": true}
}
```

Responses include additional model fields. A retry returns the same paid upgrade.
401 = missing/invalid token; 403 = another user's subscription/upgrade; 422 =
ineligible subscription, missing address, payment not completed, or payment
mismatch; 409 = subscription changed during checkout and needs staff review;
502 = payment-provider request failed.

## Web and payment history

On `/user/subscriptions`, eligible packages show **Upgrade** inside the combined
format panel. The review page shows the breakdown, unchanged expiry, and address.
It opens hosted Stripe Checkout and verifies payment on return.

`GET /api/user/payment-history` includes successful upgrades as
`type: subscription_upgrade`, with amount, currency, payment intent ID, and paid
timestamp. Original subscription and support entries remain unchanged. Admin
subscription list shows **Original Total Paid** and **Upgrade Paid** separately.

## Deployment and recovery

Run the new migration:

`php artisan migrate --path=database/migrations/2026_11_11_000001_add_subscription_upgrades.php`

Keep Laravel's scheduler running. The new `upgrades:sync` command runs each minute
and verifies pending upgrade payments, including abandoned browser/app returns.
Run `php artisan upgrades:sync` manually to reconcile once. Monitor failures.
An optional existing subscription webhook can also recover upgrades from
`checkout.session.completed` and `payment_intent.succeeded` events. No additional
webhook secret is introduced. Live Stripe testing must be performed in test mode
before production use; feature tests mock provider responses.

No expiry extension or automatic renewal is created. A payment verified late
still uses the original expiry; it does not give extra days. Provider refunds and
manual changes during checkout require staff handling.
