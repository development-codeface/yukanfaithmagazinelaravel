# Print packages

Run `php artisan migrate` before using print packages.

Plans offer optional `digital`, `print`, and `both` formats with independent full package prices.
Plan responses include `prices`, `available_types`, and `formats`. Use the selected format's
`requires_delivery_address`; the top-level print flag means the plan offers printing.
For Print or Both, collect an address before calling either `POST /api/user/subscribe/{plan}`
or `POST /api/user/subscription/payment-intent/{plan}`:

```json
{
  "delivery_type": "both",
  "delivery_address": {
    "name": "Recipient name",
    "phone": "+919876543210",
    "line1": "12 Main Street",
    "line2": "Apartment 2",
    "city": "Kochi",
    "state": "Kerala",
    "postal_code": "682001",
    "country": "India"
  }
}
```

All address fields except `line2` are required strings, with a 200 character maximum.
Missing or invalid addresses return HTTP 422 with field validation errors.
Digital selections require no address. Send `delivery_type` to select a format. If omitted,
Digital is selected when offered; otherwise a sole available format is selected. Ambiguous
or unavailable selections return 422. Blank admin prices disable a format; zero enables a
free format. Each price includes delivery, with no additional shipping charge.

Paid plans use the existing payment-intent and confirm-payment flow. Confirmation still
requires only `payment_intent_id`; address details are recovered from verified Stripe
metadata, not from the confirmation request. Address details are shared with Stripe as
payment metadata. Subscription responses include `delivery_type`, `amount_paid`, `print_enabled`,
and `delivery_address`. Print-only subscriptions do not grant digital reader access.

Successful print purchases appear in the admin subscriptions list with their delivery
address. The admin panel displays an alert until an administrator acknowledges the purchase.

The Free Trial is automatically granted once on registration and cannot be purchased repeatedly.
Its duration is read from the Free Trial package in admin. It defaults to 7 days; changes affect
future registrations and leave existing expiry dates intact.

See [membership-and-search-api.md](membership-and-search-api.md) for search and purchase routing.
