Magazine setup

In Admin > Magazine Issues, edit or create an issue. Set its external reader URL, optionally set its single-issue price, and select its articles. Each article belongs to one issue. Past Magazines displays published articles from the Past Magazines category. Map each magazine preview article to its issue to show Buy Now on the article detail page.

The homepage keeps the original cover-image article carousel and Read More links to article details. Articles prefixed with [TEST] are excluded from the homepage so the demo seed data does not replace the original cards; they remain available in Admin > Articles and through their article URLs. Legacy article Buy Button Links do not bypass access checks: assign those articles to the correct magazine in Admin > Magazine Issues, with its issue date, reader URL and single-purchase price, to enable purchasing.

Buy Now requires login and preserves the chosen issue through login or registration. New accounts receive an active one-month Free Trial, whether they select the current issue or an older one. Monthly subscriptions and trials cover only the calendar month of their start date, while active quarterly/yearly subscriptions cover issue dates between their start and end dates (inclusive). Subscription access requires an active, paid/complimentary subscription whose start and end dates include today. Individual purchases grant permanent access to that issue and its mapped paid articles, including after subscription expiry. My Magazines lists only accessible issues. Issue Date determines coverage, with Published At as a fallback for legacy issues without Issue Date; issues missing both dates require a single purchase. Admin create/edit supports Publication date. Ensure existing issues have correct dates.

The homepage lists articles. Read More opens the article detail page; Buy Now uses that article's linked magazine and its Issue Date for access checks. Guests return through the article Buy Now action after login/registration. Entitled users go to the external reader; users without access pay through embedded Stripe Checkout on the article detail page. Paid article bodies stay locked until entitled, while the preview and Buy Now remain visible. The standalone magazine page also supports the same checkout. Set STRIPE_KEY as well as STRIPE_SECRET. A successful verified payment redirects to the external reader. Public API responses hide external reader links from unauthorised users. The external hosting provider controls whether a copied reader URL can be reused.

API (Accept: application/json; authenticated endpoints require Authorization: Bearer TOKEN):

- GET /api/articles/{article}/magazine: public article-to-magazine lookup; accepts article ID or slug. Published articles only (unknown/unpublished articles return 404). Returns article_id, is_linked, and magazine (null if unlinked). Linked magazine fields: id, title, issue_date, published_at, price, currency and single_purchase_available. Does not expose the external reader URL or check user entitlement. Mobile clients must call the authenticated magazine access endpoint using the returned magazine.id before opening a reader or paying. single_purchase_available indicates the issue has a price and reader link; payment still requires Stripe configuration.

Example linked response:

```json
{
  "article_id": 12,
  "is_linked": true,
  "magazine": {
    "id": 1,
    "title": "January 2026",
    "issue_date": "2026-01-01",
    "published_at": "2026-01-01T09:00:00+00:00",
    "price": "100.00",
    "currency": "inr",
    "single_purchase_available": true
  }
}
```

- GET /api/magazines: paginated public catalogue, prices, dates; no protected reader URLs.
- GET /api/user/magazines: paginated accessible subscription issues and permanent purchases.
- GET /api/user/magazines/{id}/access: can_read, requires_purchase, price, currency and reader_url (only if entitled).
- POST /api/user/magazines/{id}/checkout: server-priced Checkout Session; send embedded=true for client_secret, otherwise checkout_url. If already entitled returns can_read and reader_url.
- GET /api/user/magazines/payment/success?session_id=cs_...: retrieves payment from Stripe, verifies ownership/payment and records purchase idempotently.
- GET /api/user/magazines/{id}/reader: protected reader response.
- POST /api/register: creates account and complimentary trial; then call the chosen issue's access endpoint.

API clients confirm the session using the authenticated success endpoint after Checkout completes; the signed webhook also fulfills purchases if the browser does not return. Browser Checkout return URLs use web login; native clients should retain session_id and confirm through the API.

Payments use existing STRIPE_SECRET and STRIPE_CURRENCY settings. Configure STRIPE_MAGAZINE_WEBHOOK_SECRET and register POST /stripe/magazines/webhook in Stripe for checkout.session.completed and checkout.session.async_payment_succeeded. Use the signing secret for this endpoint. Both the verified return page and signed webhook grant access idempotently. Test with Stripe test keys before using live payments.

Migration: php artisan migrate

Mobile Buy Now API sequence and responses

All three APIs already exist; the access response now includes purchase and subscription diagnostics. Use Accept: application/json on every request and Authorization: Bearer TOKEN on /api/user routes. The authenticated user's token determines whose purchases are checked; do not send a user_id.

1. GET /api/articles/{article}/magazine (public): resolve the article's linked issue. See the linked/unlinked response above. Stop magazine checkout if is_linked is false.
2. GET /api/user/magazines/{id}/access: check permanent ownership and subscription coverage. already_purchased is true only for a single purchase, while can_read can also be true for an eligible subscription. Use can_read to show Read Magazine instead of Buy Now. A login session alone does not grant access.

Example: no purchase and no subscription (HTTP 200):

```json
{
  "magazine_id": 1,
  "issue_date": "2026-01-01",
  "effective_issue_date": "2026-01-01",
  "already_purchased": false,
  "access_source": null,
  "has_active_subscription": false,
  "subscription": null,
  "subscription_checks": [],
  "can_read": false,
  "requires_purchase": true,
  "single_purchase_available": true,
  "price": "100.00",
  "currency": "inr",
  "reader_url": null
}
```

subscription is the covering active subscription if one exists, otherwise an active subscription, otherwise the latest recorded subscription (including expired ones). subscription_checks lists all recorded subscriptions so overlapping packages are evaluated. Each subscription object has this shape:

```json
{
  "id": 5,
  "plan_id": 2,
  "plan_name": "Monthly Reader",
  "status": "active",
  "payment_status": true,
  "starts_at": "2026-10-26T12:00:00+00:00",
  "expires_at": "2026-11-26T12:00:00+00:00",
  "is_active": true,
  "coverage_start": "2026-10-01",
  "coverage_end": "2026-10-31",
  "magazine_in_period": false,
  "grants_access": false
}
```

is_active checks status, payment/complimentary entitlement, start and expiry. magazine_in_period only checks the magazine date against package coverage; grants_access requires both. Monthly/trial coverage uses the start month; longer plans use their date range. effective_issue_date uses publication date only if issue date is missing. Dates missing from a subscription cannot grant magazine access.

For an owned issue: already_purchased=true, access_source="single_purchase", can_read=true, requires_purchase=false and reader_url is provided even after subscription expiry. For subscription access: already_purchased=false, access_source="subscription", can_read=true and requires_purchase=false. If an active package does not cover this issue, can_read=false and requires_purchase=true.

3. POST /api/user/magazines/{id}/checkout: call only when purchase is needed and available. Server rechecks access and uses its stored price. No price or user_id should be supplied.

Default body {} returns hosted checkout (HTTP 200):

```json
{
  "magazine_id": 1,
  "can_read": false,
  "requires_purchase": true,
  "session_id": "cs_test_example",
  "client_secret": null,
  "checkout_url": "https://checkout.stripe.com/..."
}
```

Body {"embedded":true} returns the same structure with client_secret populated and checkout_url=null for embedded web checkout. If already entitled, no payment session is created; the response is {"can_read":true,"reader_url":"https://reader.example.com/issue"}.

4. GET /api/user/magazines/payment/success?session_id=cs_test_example: verify the session after payment (HTTP 200):

```json
{
  "message": "Magazine purchased.",
  "magazine_id": 1,
  "already_purchased": true,
  "can_read": true,
  "requires_purchase": false,
  "reader_url": "https://reader.example.com/issue"
}
```

Checkout errors: 422 if issue price/reader is unavailable; 503 if Stripe secret is missing; 502 with {"message":"Unable to start payment. Please try again."} for a Stripe request failure. Confirmation rejects unpaid sessions with 422 and another user's session with 403; Stripe verification failure returns JSON with status 502. Unauthenticated protected requests return 401.

Native app limitation: checkout still uses website success/return URLs requiring a web login. Retain session_id and verify through the authenticated API; native deep-link return handling is not implemented. The API provides a Checkout Session, not a native PaymentIntent/PaymentSheet integration.
