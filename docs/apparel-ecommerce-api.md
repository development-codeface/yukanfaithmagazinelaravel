# Apparel ecommerce API

Base path: `/api/apparel`. Send `Accept: application/json` and JSON request bodies.
Public catalog endpoints need no token. All cart and order endpoints require
`Authorization: Bearer <token>` from the existing login/register APIs.
The cart persists per account, separately from the website's session cart.

## Catalog

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/categories` | Existing active category list |
| GET | `/products` | Paginated products |
| GET | `/products/search?search=mug` | Search name, SKU, short description |
| GET | `/products/{product-slug}` | Single product, images, active variants and details |
| GET | `/categories/{category-slug}/products` | Paginated products in an active category |

Product lists accept `search`, `category_id`, `category_slug`, `featured=1`,
`sort_by=name|price|created_at|sort_order`, `sort_order=asc|desc`, `per_page=1..50`,
and `page`. Results use Laravel resource pagination (`data`, `links`, `meta`).
Only published products in active categories are returned. Product and category
path parameters are **slugs**; cart product and variant references are numeric IDs.

## Cart

| Method | Path | Body |
| --- | --- | --- |
| GET | `/cart` | None |
| POST | `/cart/items` | `product_id`, optional `variant_id`, `quantity` |
| PATCH | `/cart/items/{key}` | `quantity` (replaces quantity) |
| DELETE | `/cart/items/{key}` | None |
| DELETE | `/cart` | None |

Adding the same product/variant increments its quantity. A variant must belong to
the product and be active. Products with variants require a variant selection.
Quantities must be positive integers; remove an item using DELETE.
Use the returned item `key`, such as `42:7` or `42:0`, for update/remove calls.

Cart responses contain `data.items`, `count` (units), `total`, `currency`, and
`can_checkout`. Unavailable lines remain visible with `available: false` so the
client can remove them; their value is excluded from the displayed total.
Cart additions do not reserve stock. Availability and prices are checked again
when creating an order.

## Purchase flow (same PaymentSheet pattern as magazines/plans)

1. Browse, choose a product/variant, and add to the authenticated cart.
2. Check `GET /cart` and collect the delivery/contact details.
3. Generate a UUID `checkout_token` on the client. Create the order with
   `POST /orders`. Keep this UUID for retries of the same checkout.
4. Call `POST /orders/{id}/payment-intent` with an empty JSON body.
5. Initialize the existing Stripe PaymentSheet using `publishableKey`,
   `paymentIntent` (the client secret), `customer`, and `ephemeralKey`.
6. After the sheet succeeds, call `POST /orders/{id}/confirm-payment` with
   `{"payment_intent_id":"pi_..."}`. The server retrieves the intent from Stripe,
   verifies its status, purchase type, owner, order, currency and received amount,
   then marks the order paid and processing.
7. Display order details/history. The signed webhook also completes paid orders
   if the client closes before step 6.

Example order request:

```json
{
  "checkout_token": "8987ce92-d025-4c29-8af4-d490167befb1",
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+1 555 0100",
  "address": "123 Main Street\nBoston, MA 02108\nUnited States",
  "notes": "Leave at reception"
}
```

Order creation returns HTTP 201 and `data` containing the order and item snapshots.
Reusing the checkout token returns the original order, without decrementing stock
or clearing a new cart again. Amounts and payment status cannot be supplied by the
client. Orders reserve inventory and clear the purchased cart within a transaction.
The total is the sum of item prices; no tax, shipping charge or discount engine is
configured. Currency follows `STRIPE_CURRENCY` and is saved on each order.
The existing application assumes two decimal currency units.

Payment-intent responses include both snake_case and existing PaymentSheet field
aliases (`client_secret`/`paymentIntent`, `payment_intent_id`/`paymentIntentId`,
`publishable_key`/`publishableKey`), plus amount in minor units and currency.
Retrying uses the order's existing intent. Do not create a new order after an
ambiguous network/payment result: retry confirmation or inspect the original order.

## Orders

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/orders?per_page=15&page=1` | Account order history, including existing website orders |
| GET | `/orders/{id}` | Own order, items, payment/fulfilment status, tracking number |
| POST | `/orders` | Create pending order from saved API cart |
| POST | `/orders/{id}/payment-intent` | Start/reuse Stripe PaymentSheet payment for API order |
| POST | `/orders/{id}/confirm-payment` | Verify payment and complete order |
| POST | `/orders/{id}/cancel` | Cancel API order before a payment intent exists; release stock once |

Orders belonging to another account return 404. Validation or unavailable stock
returns 422; missing/invalid authentication returns 401; provider errors return 502;
missing Stripe configuration returns 503. A successful confirmation is repeatable.

There is no automatic reservation expiry. Pending orders retain stock until staff
cancel them or the customer cancels before starting payment. Orders with an intent
require staff reconciliation/cancellation/refund in Stripe; automatic refunds and
cancellation after a payment attempt are not implemented. Admin cancellation is
blocked for API orders with an intent to prevent releasing stock for a live payment.

## Setup and webhook

Run `php artisan migrate` before using the new cart/payment fields. Configure the
existing `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_CURRENCY` plus
`STRIPE_APPAREL_WEBHOOK_SECRET`. Register a Stripe endpoint at
`POST https://<your-host>/api/apparel/payments/webhook`, subscribed to
`payment_intent.succeeded`, and copy that endpoint's signing secret to the new env
setting. Refresh the Laravel configuration cache after changing environment values.
This endpoint uses the raw request body and a timestamped Stripe signature; it
does not use bearer authentication. Duplicate events are safe. Non-apparel events
are acknowledged and ignored.

Saved payment methods use the existing `/api/user/payment-methods` endpoints and
the same Stripe customer as magazine and plan purchases. No card data is stored.
Integration reference: [Stripe PaymentSheet](https://docs.stripe.com/payments/accept-a-payment?platform=react-native&ui=payment-sheet).
