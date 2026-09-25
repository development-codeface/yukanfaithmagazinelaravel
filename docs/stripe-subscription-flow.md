# Stripe Subscription Flow

## Overview

The Laravel app now uses Stripe for paid subscription plans.

- Web users pay through Stripe Checkout.
- Mobile users pay through Stripe PaymentIntent using the Stripe mobile SDK.
- Laravel activates the subscription only after verifying payment with Stripe.
- Free plans still activate without Stripe payment.

## Environment Setup

Add these values to `.env`:

```env
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx
STRIPE_CURRENCY=inr
```

Use Stripe test keys for development and live keys for production.

## Web Flow

1. User logs in.
2. User opens:

```http
GET /user/subscriptions
```

3. User selects a paid plan.
4. Laravel receives:

```http
POST /user/subscribe/{plan}
```

5. Laravel creates a Stripe Checkout Session.
6. User is redirected to Stripe Checkout.
7. User completes payment on Stripe.
8. Stripe redirects back to:

```http
GET /user/subscribe/stripe/success?session_id={CHECKOUT_SESSION_ID}
```

9. Laravel retrieves the Stripe Checkout Session and verifies:

- Payment status is `paid`.
- Stripe metadata user ID matches the logged-in user.
- Stripe metadata plan ID exists.

10. Laravel creates an active subscription:

- `status = active`
- `payment_status = 1`
- `amount_paid = Stripe amount`
- `transaction_id = Stripe payment intent/session reference`

11. User is redirected back to:

```http
GET /user/subscriptions
```

If user cancels Stripe Checkout:

```http
GET /user/subscribe/stripe/cancel
```

## Mobile App Flow

### 1. Get Plans

```http
GET /api/plans
```

### 2. Create PaymentIntent

```http
POST /api/user/subscription/payment-intent/{plan_id}
Authorization: Bearer API_TOKEN
```

Response for paid plans:

```json
{
  "requires_payment": true,
  "publishable_key": "pk_test_xxxxx",
  "client_secret": "pi_xxxxx_secret_xxxxx",
  "payment_intent_id": "pi_xxxxx",
  "amount": 99900,
  "currency": "inr",
  "plan": {}
}
```

Response for free plans:

```json
{
  "message": "Subscription activated successfully.",
  "requires_payment": false,
  "subscription": {}
}
```

### 3. Confirm Payment In Mobile SDK

Use the Stripe mobile SDK with the `client_secret`.

The app should confirm the card/payment method using Stripe SDK. After SDK success, keep the `payment_intent_id`.

### 4. Confirm Payment With Laravel

```http
POST /api/user/subscription/confirm-payment
Authorization: Bearer API_TOKEN
Content-Type: application/json

{
  "payment_intent_id": "pi_xxxxx"
}
```

Laravel verifies the PaymentIntent with Stripe and activates the subscription.

Success response:

```json
{
  "message": "Payment successful. Subscription activated.",
  "subscription": {}
}
```

### 5. Check Subscription Status

```http
GET /api/user/subscription/status
Authorization: Bearer API_TOKEN
```

## Important Notes

- The previous API endpoint `POST /api/user/subscribe/{plan}` now only activates free plans.
- Paid plans return `402 Payment Required` from that endpoint.
- Mobile apps must use the PaymentIntent endpoints for paid plans.
- This implementation uses one-time Stripe payments and the app's own subscription duration logic.
- This is not Stripe recurring billing yet.

## Files Changed

- `app/Http/Controllers/UserDashboardController.php`
- `app/Http/Controllers/Api/UserController.php`
- `routes/web.php`
- `routes/api.php`
- `config/services.php`
- `.env.example`
- `app/Models/Subscription.php`
- `database/migrations/2026_09_24_000001_add_stripe_payment_fields_to_subscriptions.php`
- `docs/stripe-subscription-flow.md`

## Verification Done

- PHP syntax checks passed for updated controllers and config.
- Stripe routes are registered.
- `/user/subscriptions` renders successfully.
- Migration ran successfully locally.
