# Magazine purchase with native PaymentSheet

The magazine API now uses the same PaymentIntent/customer/ephemeral-key flow as package purchases. Deploy the updated `routes/api.php` and API `UserController.php`. No database migration is required for this change.

## 1. Start payment

```http
POST /api/user/magazines/1/checkout
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

Alias: `POST /api/user/magazines/1/payment-intent`.

```json
{
  "delivery_type": "both",
  "delivery_address": {
    "name": "Example Reader",
    "phone": "9876543210",
    "line1": "12 Main Street",
    "city": "Kochi",
    "state": "Kerala",
    "postal_code": "682001",
    "country": "India"
  }
}
```

Choose `digital`, `print`, or `both` from the magazine's offered formats. Digital does not need an address. Print/Both require all address fields above; `line2` is optional. Each address value is a string, maximum 200 characters. The server selects its stored format price; do not send an amount.

200 response example:

```json
{
  "requires_payment": true,
  "requires_purchase": true,
  "magazine_id": 1,
  "delivery_type": "both",
  "price": "150.00",
  "publishable_key": "pk_test_example",
  "publishableKey": "pk_test_example",
  "client_secret": "pi_example_secret_example",
  "paymentIntent": "pi_example_secret_example",
  "payment_intent_id": "pi_example",
  "paymentIntentId": "pi_example",
  "ephemeralKey": "ek_test_example",
  "customer": "cus_example",
  "amount": 15000,
  "currency": "inr"
}
```

The snake_case/camelCase aliases match the package payment API. Map `paymentIntent` (or `client_secret`) to PaymentSheet's PaymentIntent client secret, `ephemeralKey` to customer ephemeral key, and `customer` to customer ID. `amount` uses minor units. Do not log secrets.

When `requires_payment:false`, do not open PaymentSheet. An existing digital entitlement can return 200 with `can_read:true`, `requires_purchase:false`, magazine ID and selected type. Existing ownership returns the purchase summary. A zero-price format creates the order immediately with 201 and the purchase summary; no Stripe payment or confirmation is necessary.

## 2. Complete PaymentSheet, then confirm

After the Stripe SDK reports payment success:

```http
POST /api/user/magazines/confirm-payment
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

```json
{"payment_intent_id":"pi_example"}
```

Required string, maximum 255 characters. Send the PaymentIntent ID, not the client secret or a Checkout Session ID.

201 response (purchase abbreviated):

```json
{
  "message": "Magazine purchased.",
  "requires_payment": false,
  "requires_purchase": false,
  "already_purchased": true,
  "magazine_id": 1,
  "delivery_type": "both",
  "purchase": {
    "id": 4,
    "user_id": 12,
    "magazine_issue_id": 1,
    "delivery_type": "both",
    "amount_paid": "150.00",
    "transaction_id": "pi_example",
    "delivery_address": {
      "name": "Example Reader",
      "phone": "9876543210",
      "line1": "12 Main Street",
      "city": "Kochi",
      "state": "Kerala",
      "postal_code": "682001",
      "country": "India"
    }
  },
  "can_read": true,
  "reader_url": "https://example.com/reader"
}
```

Server retrieves the PaymentIntent, verifies it is a magazine payment for this user and has succeeded, then saves original format, paid amount and address from Stripe metadata. Changing prices after payment creation does not change the purchase. Confirmation can be retried: repeat confirmation returns 200 `Magazine already purchased.` without duplicating the order or replacing the original snapshot.

Print alone returns `can_read:false` and `reader_url:null` unless another digital entitlement exists. Refresh `/api/user/magazines/1/access`, dashboard, or purchase history after confirmation. Print/Both purchases use the existing admin delivery acknowledgement flow.

Errors: 401 token absent/invalid; 404 missing magazine; 422 unsupported format, missing delivery address, unavailable digital reader, wrong account/payment kind, or incomplete payment; 500 Stripe not configured; 502 Stripe request failed. A pending/failed payment never creates an order. For canceled PaymentSheet, do not call fulfillment; retry with a new payment request when appropriate.

## Website compatibility and deployment

The browser endpoint `/user/magazine/{id}/checkout` retains hosted/embedded Checkout Sessions. The legacy API GET `/api/user/magazines/payment/success?session_id=cs_...` still confirms old Checkout Sessions. The existing magazine Checkout webhook still handles Checkout sessions only; native PaymentIntent purchases require the explicit confirmation API above. If the app closes after Stripe succeeds, retain the PaymentIntent ID securely and retry confirmation when reopened. There is no PaymentIntent webhook fallback in this change.

**Breaking API response change:** API magazine `/checkout` no longer returns `checkout_url` or `session_id`, and `embedded` no longer selects web Checkout there. Mobile clients must use PaymentSheet and the new confirmation endpoint. Browser form routes are unchanged. The package confirmation endpoint cannot confirm magazine payments.

Stripe reference: [PaymentSheet PaymentIntent client secret](https://stripe.dev/stripe-ios/stripepaymentsheet/documentation/stripepaymentsheet/paymentsheet/init%28paymentintentclientsecret%3Aconfiguration%3A%29).
