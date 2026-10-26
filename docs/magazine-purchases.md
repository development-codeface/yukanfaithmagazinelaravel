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
