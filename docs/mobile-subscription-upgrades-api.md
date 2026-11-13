# Mobile API: Subscription upgrades

This document describes the current Laravel implementation for mobile developers.
Example IDs, amounts, dates, tokens, and hostnames are illustrative.

## Connection and authentication

Base URL: `https://YOUR_DOMAIN/api/user`

Send these headers on every request:

```http
Authorization: Bearer YOUR_LOGIN_TOKEN
Accept: application/json
Content-Type: application/json
```

Use the user's existing login token. Do not send a user ID. All four endpoints
require authentication and operate on the authenticated user's subscriptions.
All successful responses below return HTTP `200`.

## Supported behavior

- Digital (`digital`) or Print (`print`) can upgrade to Digital + Print (`both`).
- The subscription must be paid, active, already started, and not expired.
- Trials and subscriptions already using `both` cannot upgrade.
- An uncancelled trial with scheduled annual billing blocks upgrades.
- Valid stored prices for the current format and combined format are required.
- The package and expiry date stay the same. No renewal or extra days are added.
- The server calculates the charge from stored purchase prices:
  `(combined price - current format price) * remaining seconds / total seconds`.
- Support contributions are excluded. Amounts are rounded to integer minor units.
- Example: INR 100 current price, INR 200 combined price, half the period
  remaining gives INR 50.00 (`amount: 5000`).

## 1. List available upgrades

```http
GET /api/user/subscription/upgrades
```

Request body: none. Query parameters: none.

Response:

```json
{
  "data": [
    {
      "subscription_id": 12,
      "plan_id": 4,
      "plan_name": "Reader Package",
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
      "upgrade_url": "https://YOUR_DOMAIN/user/subscriptions/12/upgrade",
      "quote_endpoint": "https://YOUR_DOMAIN/api/user/subscriptions/12/upgrade",
      "payment_endpoint": "https://YOUR_DOMAIN/api/user/subscriptions/12/upgrade/payment-intent"
    }
  ]
}
```

If no subscriptions qualify, the response is `{"data": []}`. This is a successful
response; hide or disable the upgrade action. `upgrade_url` is for the website;
use `quote_endpoint` and `payment_endpoint` for the native flow.

## 2. Get an upgrade quote

```http
GET /api/user/subscriptions/12/upgrade
```

Path parameter: `12` is the **subscription ID**, not the plan ID.
Request body: none. Query parameters: none.

Response:

```json
{
  "quote": {
    "subscription_id": 12,
    "plan_id": 4,
    "plan_name": "Reader Package",
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
    "price_snapshot_source": "purchase"
  }
}
```

Unlike the list endpoint, this response does not include the three URL fields.

| Field | Type | Meaning |
| --- | --- | --- |
| `subscription_id`, `plan_id` | integer | Purchased subscription and its package |
| `plan_name` | string | Package display name |
| `from_type` | string | `digital` or `print` |
| `to_type` | string | Always `both` |
| `current_format_price`, `combined_format_price` | decimal string | Full-period prices in major currency units |
| `amount` | integer | Upgrade charge in minor units; INR 50 = 5000 paise |
| `upgrade_amount` | decimal string | Display charge in major currency units |
| `currency` | string | Uppercase in quotes, for example `INR` |
| `remaining_days`, `total_days` | number | Duration in days, rounded to two decimals |
| `ends_at` | ISO 8601 string | Existing subscription expiry |
| `support_amount` | decimal string | Always `0.00` for this upgrade |
| `price_snapshot_source` | string or null | Stored pricing provenance; treat as informational |

Quotes change with time until payment preparation. Once a pending upgrade exists,
its amount is reused and remaining days reflect its original quote time.

## 3. Create or resume upgrade payment

```http
POST /api/user/subscriptions/12/upgrade/payment-intent
```

Request:

```json
{
  "delivery_address": {
    "name": "Example Reader",
    "phone": "9876543210",
    "line1": "12 Main Street",
    "line2": "Apartment 4",
    "city": "Kochi",
    "state": "Kerala",
    "postal_code": "682001",
    "country": "India"
  }
}
```

`delivery_address` must be an object. Each field is a string of at most 200
characters. All fields except `line2` are required; `line2` may be omitted or null.
For Digital subscriptions, supply a valid delivery address. For Print
subscriptions with a valid saved address, send `{}` to reuse that address.
An address object supplied by the app replaces the saved address for validation;
partial addresses are not merged with the existing one.

Do not send amount, currency, user ID, plan ID, or target format. These are
determined by the server.

Normal response:

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
  "confirm_endpoint": "https://YOUR_DOMAIN/api/user/subscription-upgrades/8/confirm"
}
```

Save `upgrade_id` for confirmation and recovery after app restart. Configure the
mobile Stripe integration with `publishable_key` and use `client_secret` for the
PaymentIntent payment flow. This response contains no customer or ephemeral key.
The backend never returns its Stripe secret API key.

If a pending attempt's payment already succeeded, this endpoint verifies and
applies the upgrade, returning this alternate shape:

```json
{
  "requires_payment": false,
  "upgrade": {
    "id": 8,
    "subscription_id": 12,
    "status": "paid",
    "amount_minor": 5000,
    "currency": "inr"
  },
  "subscription": {
    "id": 12,
    "delivery_type": "both",
    "print_enabled": true,
    "end_date": "2026-12-01T00:00:00.000000Z"
  }
}
```

The nested models above are abbreviated; actual responses include additional
database fields, and `subscription` includes its `plan` relation. When
`requires_payment` is false, use the returned subscription and do not reopen
payment. The top-level payment credentials are absent in this branch.

Retries of a pending native upgrade reuse the upgrade and PaymentIntent. Its
price and delivery address stay frozen; sending a new address does not update
that pending attempt. An upgrade started on the website must continue through
the original website payment channel.

## 4. Confirm the upgrade

Call after the mobile payment flow reports success, or to recover a previously
completed payment whose confirmation response was lost.

```http
POST /api/user/subscription-upgrades/8/confirm
```

Path parameter: `8` is the **upgrade ID** returned by step 3.
Request body: `{}` (or no body). No PaymentIntent ID or client secret is required.

Response (nested models abbreviated):

```json
{
  "message": "Package upgraded to Digital + Print. The expiry date is unchanged.",
  "upgrade": {
    "id": 8,
    "subscription_id": 12,
    "from_type": "digital",
    "to_type": "both",
    "status": "paid",
    "amount_minor": 5000,
    "currency": "inr",
    "payment_flow": "native",
    "payment_intent_id": "pi_example"
  },
  "subscription": {
    "id": 12,
    "plan_id": 4,
    "delivery_type": "both",
    "print_enabled": true,
    "status": "active",
    "payment_status": true,
    "end_date": "2026-12-01T00:00:00.000000Z"
  }
}
```

The server checks the stored payment with Stripe, including payment status,
amount, currency, and ownership metadata. Only then does it apply the upgrade.
Repeated confirmation of an already paid upgrade is safe and returns success.
Original subscription amount paid, support contribution, transaction ID, and
start/end dates are preserved; the upgrade charge is a separate record.

## Errors

Always inspect the HTTP status and JSON `message`. Error messages below are
examples; do not depend on exact strings for app business logic.

| HTTP status | Meaning / app action |
| --- | --- |
| `401` | Missing or invalid login token; ask the user to sign in |
| `403` | Subscription or upgrade belongs to another user |
| `404` | Subscription or upgrade ID does not exist |
| `409` | Subscription changed during checkout; show the message and contact support, especially if payment succeeded |
| `422` | Ineligible subscription, invalid address, payment still incomplete, payment mismatch, or wrong payment channel |
| `502` | Stripe request or connection failed; preserve the upgrade ID and allow retry |
| `503` | Server Stripe secret is not configured; payment setup unavailable |

Authentication error:

```json
{"message": "Unauthenticated."}
```

Address validation error (`422`; relevant fields shown):

```json
{
  "message": "The delivery address.name field is required.",
  "errors": {
    "delivery_address.name": [
      "The delivery address.name field is required."
    ]
  }
}
```

Incomplete payment (`422`):

```json
{"message": "Payment is not complete."}
```

Other possible `422` messages include:

- `Only paid packages can be upgraded.`
- `Only a currently active subscription can be upgraded.`
- `This package already includes Digital + Print.`
- `Combined format pricing is not available for this subscription.`
- `Too little time remains for a paid upgrade.`
- `Continue the upgrade using the original payment channel.`
- `This subscription has already been upgraded.`

## Mobile integration sequence

1. List options and show the action for the matching subscription.
2. Fetch its quote and display charge, currency, and unchanged expiry date.
3. Collect the delivery address when needed and start payment.
4. Save `upgrade_id`. If `requires_payment` is true, present Stripe payment.
5. After payment success, call confirm. Refresh subscription state using the
   confirmed response. Enable the combined format after server confirmation.
6. If payment is cancelled, leave subscription access unchanged. Resume by
   calling the start endpoint for the same subscription.
7. If payment succeeds but confirmation fails due to a network error, retry
   confirmation with the saved upgrade ID. If payment is still processing,
   keep the UI pending and retry confirmation later rather than treating it as
   completed.

This document was checked against `SubscriptionUpgradeController`,
`SubscriptionUpgradeService`, `PrintDelivery`, and `ApiTokenAuth`. It describes
the backend contract; live mobile/Stripe behavior was not exercised for this
documentation update.
