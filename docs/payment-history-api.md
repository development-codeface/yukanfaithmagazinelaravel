# Payment history

`GET /api/user/payment-history?user_id=7&per_page=15&page=1`

Send `Authorization: Bearer <token>` and `Accept: application/json`.
`user_id` is optional; when supplied it must match the token owner (otherwise 403).
`per_page` defaults to 15 and accepts 1–100. `page` starts at 1.

The response is a Laravel paginator with `data`, `current_page`, `per_page`,
`total`, `last_page`, `next_page_url`, and `prev_page_url`.

Each data entry contains:

```json
{
  "source_id": 12,
  "type": "subscription",
  "description": "Basic Digital",
  "amount": "499.00",
  "package_amount": "474.00",
  "support_amount": "25.00",
  "currency": "INR",
  "transaction_id": "pi_example",
  "recorded_at": "2026-11-06 10:00:00",
  "status": "paid"
}
```

Types: `subscription`, `magazine`, `article`, `apparel`. Identify records using
both `type` and `source_id`; IDs can repeat across types. Transaction IDs can be null.
Results are newest first, including expired subscriptions that were paid.
Only positive successful payments recorded in the local database are included;
free trials, pending payments, and failed payments are excluded. This endpoint
does not fetch Stripe's payment attempts or refund ledger.

`amount` is the total paid, including support. For subscriptions, `package_amount`
is the amount excluding `support_amount`; for other types it is null. Support is
returned as `"0.00"` when absent. New subscriptions and apparel use stored currency.
Legacy subscription, article, and magazine records without currency use
`services.stripe.currency`.
Dates use the purchase/paid timestamp where available, otherwise record creation.

Web: `/user/payment-history`, accessible through **Payment History** on the
logged-in dashboard. No database migration or Stripe configuration change is needed.
