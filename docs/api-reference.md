# Complete API reference

Reviewed: 28 October 2026 against `routes/api.php`, registered Laravel routes, controllers, resources, middleware, models, and payment helpers.

Examples use fictional IDs, URLs and payment references. Named response types below (`Article`, `Magazine`, etc.) refer to the full objects in the response section; they are not literal strings sent by the server. Examples with abbreviated models are identified. This documents implemented behavior, including limitations, rather than proposed endpoints.

## Contents

- [Connection and conventions](#connection-and-conventions)
- [Current flows](#current-flows)
- [Authentication](#authentication)
- [Plans and subscriptions](#plans-and-subscriptions)
- [Saved cards](#saved-cards)
- [Articles](#articles)
- [Magazine catalogue](#magazine-catalogue)
- [Magazine checkout, library and reader](#magazine-checkout-library-and-reader)
- [Dashboard](#dashboard)
- [Categories, banners, events and apparel](#categories-banners-events-and-apparel)
- [Response objects](#response-objects)
- [Stripe webhook](#stripe-magazine-webhook-outside-api)
- [Review findings and limits](#review-findings-and-limits)

## Connection and conventions

Base URL: your application's configured URL plus `/api`, for example `https://YOUR_HOST/api`. If Apache serves the project in a subdirectory, include that directory. Paths below include `/api`.

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer TOKEN_FROM_LOGIN_OR_REGISTER
```

Only `/api/user/*` requires the bearer header. Public routes do not run custom token authentication, so adding a bearer token there does not unlock paid resources. API calls need no browser cookies or CSRF token. GET parameters belong in the query string; other request examples are JSON bodies. GET requests have no body.

Tokens are opaque random strings, not JWTs. Login issues another token. Logout revokes only the current token. The current middleware provides no expiry or refresh flow. Store tokens and payment client secrets privately.

Path parameters normally use existing numeric IDs. Articles additionally support title slugs, and generated article URLs can contain slugs; numeric article IDs are more stable for clients.

| Status | Meaning |
| --- | --- |
| 200 | Successful read/update, repeat confirmation, or Checkout Session creation |
| 201 | Registration, subscription activation, legacy article purchase |
| 400 | Invalid webhook signature/timestamp |
| 401 | Missing/invalid bearer token: `{"message":"Unauthenticated."}` |
| 402 | Payment needed for selected package |
| 403 | Reader access denied, wrong payment account, or another customer's card |
| 404 | Missing record, unpublished article, inactive plan/banner/event |
| 422 | Validation, unavailable format, trial purchase, or incomplete payment |
| 429 | API throttle exceeded; respect retry headers |
| 500 | Package/card Stripe configuration absent or unexpected failure |
| 502 | Upstream Stripe operation failed |
| 503 | Magazine payment/webhook configuration unavailable |

Typical validation error (text varies by field/framework):

```json
{"message":"The selected delivery type is invalid.","errors":{"delivery_type":["The selected delivery type is invalid."]}}
```

Registration explicitly uses `message:"Validation failed."` plus the errors map. Missing records/abort failures use framework JSON messages. Debug deployments can include diagnostic fields.

### Response envelopes

Single top-level resource: `{"data":Resource}`. Nonpaginated collection: `{"data":[Resource,...]}`. Paginated collection:

```json
{
  "data": [],
  "links": {"first":"https://YOUR_HOST/api/articles?page=1","last":"https://YOUR_HOST/api/articles?page=1","prev":null,"next":null},
  "meta": {"current_page":1,"from":null,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://YOUR_HOST/api/articles?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://YOUR_HOST/api/articles","per_page":12,"to":null,"total":0}
}
```

Nested resources in explicit JSON objects (dashboard, checkout, login) do not add another `data` wrapper. Decimal prices usually serialize as strings. Stripe `amount` is an integer in minor units: INR 100.00 = 10000. Raw model responses can include additional database columns. User dates use a different serialization format from many resource dates; parse the date strings returned.

## Current flows

### Browse and Buy Now

1. Browse articles or search. Resolve an article's magazine through `/api/articles/{article}/magazine`.
2. A guest retains magazine ID/format locally and sends `magazine_id` and `delivery_type` to register/login. Registration grants the configured trial before eligibility is checked.
3. A logged-in user calls `/api/user/articles/{article}/buy-options` with the selected format.
4. `next_action:dashboard` means Digital access already exists. `subscriptions` means present purchase choices. Print/Both still lead to purchase even with Digital access.
5. On the website, the selected magazine appears above packages with Buy Now and Subscribe. Subscribe scrolls to packages. Main purchase tabs are removed; individual package format selectors remain.
6. Keep selected-magazine context only during that Buy Now flow. Ordinary subscription visits show no remembered magazine. Clear client state after completion/leaving checkout.

### Package subscription

1. Fetch plans and choose a purchasable format. Collect an address for Print/Both.
2. Zero-price nontrial format: POST `/api/user/subscribe/{plan}`. Paid format: POST `/api/user/subscription/payment-intent/{plan}`.
3. Complete Stripe client payment with returned client secret/customer/ephemeral key.
4. POST `payment_intent_id` to confirm-payment. Refresh dashboard/status and issue access.
5. Same-format renewal may start at the latest subscription's end, so purchased access can be scheduled rather than active immediately.

Packages are fixed-duration purchases in this API. No automatic recurring billing/cancellation/refund API is defined. Trial is granted once at signup; its duration is controlled in admin (default seven days), and existing expiry dates are preserved when configuration changes.

### One magazine

1. Choose an offered magazine format and collect its address if required.
2. POST API magazine checkout; use returned PaymentIntent client secret/customer/ephemeral key in native PaymentSheet.
3. After Stripe success, POST payment_intent_id to /api/user/magazines/confirm-payment. Website purchases retain Checkout Sessions and their separate success/webhook flow.
4. Digital/Both appears in the reader library. Print appears in purchase history/delivery lists.

Native API magazine purchases use PaymentSheet and explicit confirmation. Website Checkout retains its browser return URLs.

### Access rules

Free published articles are public. Paid articles require an individual purchase, access to their linked magazine, or (when unlinked) an active Digital/Both package. Magazine access requires Digital/Both single ownership or a currently active paid Digital/Both package covering its issue date (`published_at` fallback). Coverage uses subscription calendar start/end dates. Print alone gives no Digital reading. Single ownership survives subscription expiry. Dashboard articles include purchased/covered library items, not all public free articles.

## Authentication

### GET /api/ping

Public, no parameters. Checks connectivity. 200: `{"message":"API is working!"}`.

### POST /api/register

Public. Creates account and grants signup trial.

| Field | Rules |
| --- | --- |
| name | Required string, max 255 |
| email | Required valid unique email, max 255 |
| password | Required string, min 8; uppercase, lowercase, number and symbol |
| password_confirmation | Must match password |
| magazine_id | Optional nullable existing magazine integer ID |
| delivery_type | Optional nullable digital/print/both; omit to default digital |

```json
{"name":"Example Reader","email":"reader@example.com","password":"Example123!","password_confirmation":"Example123!","magazine_id":7,"delivery_type":"print"}
```

201 response (User and Magazine abbreviated; see response objects):

```json
{"message":"User registered successfully.","user":{"id":12,"name":"Example Reader","email":"reader@example.com"},"token":"OPAQUE_TOKEN","purchase_context":{"magazine_id":7,"can_read":true,"delivery_type":"print","show_selected_magazine":true,"magazine":{"id":7,"title":"October issue","can_read":false,"pdf_url":null},"next_action":"subscriptions"}}
```

422 invalid/duplicate input. Trial subscription is retrieved from status/dashboard. Nested magazine access can differ from context access on login/register; see findings.

### POST /api/login

Public. Required valid email and string password. Optional magazine_id/delivery_type as registration.

```json
{"email":"reader@example.com","password":"Example123!","magazine_id":7,"delivery_type":"digital"}
```

200 has `user`, `token`, `purchase_context` exactly as register, without `message`. Wrong credentials return 422 with `message:"The provided credentials are incorrect."` and `errors.email` containing that text.

Without magazine_id, context is:

```json
{"magazine_id":null,"can_read":null,"delivery_type":null,"show_selected_magazine":false,"magazine":null,"next_action":"dashboard"}
```

### POST /api/user/logout

Bearer required. Empty body. 200: `{"message":"Logged out successfully."}`. Other tokens remain valid.

## Plans and subscriptions

### GET /api/plans

Public. Optional `?search=monthly` matches name/description. Active plans, newest first, no pagination. 200: `{"data":[Plan,...]}`. Trial can appear as `is_trial:true,purchasable:false`; do not offer it as a checkout purchase.

### GET /api/plans/{plan}

Public. Example `/api/plans/2`. 200: `{"data":Plan}`. Missing/inactive 404; inactive message `Plan not found.`.

### POST /api/user/subscribe/{plan}

Bearer required. Activates a zero-price nontrial format.

```json
{"delivery_type":"digital"}
```

Print/Both require `delivery_address` (Address rules below). If format omitted: Digital when offered, otherwise sole offered format. Ambiguous/unavailable formats, trial, inactive plan, or invalid address return 422.

201: `{"message":"Subscription activated successfully.","subscription":SubscriptionWithPlan}`.

Paid format returns 402:

```json
{"message":"Payment is required for this plan.","payment_endpoint":"https://YOUR_HOST/api/user/subscription/payment-intent/2"}
```

### POST /api/user/subscription/payment-intent/{plan}

Bearer required. Starts package payment using stored selected-format price. Request:

```json
{"delivery_type":"both","delivery_address":{"name":"Example Reader","phone":"1234567890","line1":"12 Main Street","line2":null,"city":"Kochi","state":"Kerala","postal_code":"682001","country":"India"}}
```

Paid response (raw selected Plan abbreviated):

```json
{"requires_payment":true,"delivery_type":"both","publishable_key":"pk_test_example","publishableKey":"pk_test_example","client_secret":"pi_example_secret_example","paymentIntent":"pi_example_secret_example","payment_intent_id":"pi_example","paymentIntentId":"pi_example","ephemeralKey":"ek_test_example","customer":"cus_example","amount":20000,"currency":"inr","plan":{"id":2,"name":"Monthly Reader","price":200,"selected_delivery_type":"both","print_enabled":true}}
```

Snake/camel aliases carry identical values. `plan` is a raw selected model, not PlanResource. For a zero-price selection, 201: `{"message":"Subscription activated successfully.","requires_payment":false,"subscription":SubscriptionWithPlan}` with no Stripe secrets.

422 selection/address/trial/inactive; 500 `Stripe is not configured.`; 502 `Unable to start Stripe payment.`.

### POST /api/user/subscription/confirm-payment

Bearer required. Required payment_intent_id string ≤255:

```json
{"payment_intent_id":"pi_example"}
```

201: `{"message":"Payment successful. Subscription activated.","subscription":SubscriptionWithPlan}`. Repeat confirmation: 200 `{"message":"Subscription already activated.","subscription":SubscriptionWithPlan}`.

Server verifies user/plan metadata and payment success; amount, format and address are recovered from verified payment metadata, preserving the purchase snapshot after price changes. Do not send a new address/amount at confirmation.

422 mismatch: `{"message":"Stripe payment does not match this account."}`. 422 incomplete: `{"message":"Stripe payment is not complete.","payment_status":"processing"}`. 500 Stripe missing; 502 `Unable to verify Stripe payment.`.

### GET /api/user/subscription/status

Bearer required, no parameters. No subscription:

```json
{"has_active_subscription":false,"can_read_paid_articles":false,"subscription":null,"plan":null,"expires_at":null}
```

When active, subscription is SubscriptionWithPlan, plan is a raw Plan, expires_at its end date. Returns latest current subscription, not complete history. Active subscription does not imply coverage for every magazine; use access endpoint.

## Saved cards

All require bearer auth. No raw card numbers go to these endpoints: collect cards through Stripe SDK. All can return 500 `Stripe is not configured.` or the endpoint-specific 502 below.

### POST /api/user/payment-methods/setup-intent

Empty body. Complete returned setup intent in Stripe SDK:

```json
{"publishableKey":"pk_test_example","setupIntent":"seti_example_secret_example","setupIntentId":"seti_example","ephemeralKey":"ek_test_example","customer":"cus_example"}
```

502: `Unable to start card setup.`.

### GET /api/user/payment-methods

No parameters. Lists customer's saved cards:

```json
{"customer":"cus_example","default_payment_method":"pm_example","payment_methods":[{"id":"pm_example","is_default":true,"brand":"visa","last4":"4242","exp_month":12,"exp_year":2030,"country":"IN","funding":"credit"}]}
```

Empty list is `[]`; default may be null. 502: `Unable to load saved cards.`.

### POST /api/user/payment-methods/{paymentMethod}/default

Example `/api/user/payment-methods/pm_example/default`, empty body:

```json
{"message":"Default card updated successfully.","customer":"cus_example","default_payment_method":"pm_example"}
```

403 `Payment method does not belong to this account.`; 502 `Unable to update default card.`.

### DELETE /api/user/payment-methods/{paymentMethod}

Example `/api/user/payment-methods/pm_example`, no body. 200: `{"message":"Card deleted successfully."}`. Same 403 ownership check; 502 `Unable to delete saved card.`.

## Articles

Public lists return metadata for published articles with paid content locked. Reading as a token user uses the protected article endpoint. Admin Type filter removal did not remove compatibility free/paid API routes.

| Public endpoint | Request and purpose | Response |
| --- | --- | --- |
| GET /api/articles | `?category_id=1&access_type=paid&search=faith&sort_by=published_at&sort_order=desc&per_page=12&page=1`; title/summary/content search; primary/additional category matching | Paginated Article array |
| GET /api/articles/latest | No parameters; all published, latest published_at first (not one item) | `{"data":[Article,...]}` |
| GET /api/articles/featured | No parameters; newest six published, no featured flag filter | `{"data":[Article,...]}` |
| GET /api/articles/free | `?per_page=12&page=1`; free published | Paginated Article array |
| GET /api/articles/paid | `?per_page=12&page=1`; paid published, publicly locked | Paginated Article array |
| GET /api/articles/search | `?q=faith&category_id=1&per_page=10&page=1`; q required string 2–255, ≥2 nonspace characters; optional existing category, per_page integer 1–100, page integer ≥1 | Paginated Article array; 422 validation |
| GET /api/articles/category/{categoryId} | `/api/articles/category/1?page=1`; primary/additional category, fixed 10/page | Paginated Article array; unmatched category can be empty |
| GET /api/articles/pastMagazines | `?page=1`; hardcoded category **6**, fixed 10/page | Paginated Article array |
| GET /api/articles/{article} | `/api/articles/42`; free published detail | `{"data":Article}`; 404 missing/unpublished; 403 paid |

Paid public detail 403 example:

```json
{"message":"Please login and use /api/user/articles/42 with an active subscription to read this article."}
```

### GET /api/articles/stats

Public, no parameters. Latest Article abbreviated:

```json
{"total_articles":25,"published_articles":20,"draft_articles":5,"latest_article":{"id":42,"title":"Faith today"}}
```

Counters include nonpublic article totals. Latest uses newest creation among published. Empty dataset behavior needs follow-up (see findings).

### GET /api/articles/{article}/magazine

Public. Resolves the magazine before login; example `/api/articles/42/magazine`:

```json
{"article_id":42,"is_linked":true,"magazine":{"id":7,"title":"October issue","issue_date":"2026-10-28","published_at":"2026-10-28T00:00:00+00:00","price":"25.00","prices":{"digital":"25.00","print":"40.00","both":"50.00"},"available_types":["digital","print","both"],"currency":"inr","single_purchase_available":true}}
```

This compact magazine object is not MagazineResource and contains no reader URL. Unmapped article: `{"article_id":42,"is_linked":false,"magazine":null}`. 404 missing/unpublished.

### GET /api/user/articles/{article}/buy-options

Bearer required. Optional delivery_type enum, default digital; example `?delivery_type=print`:

```json
{"article_id":42,"magazine_id":7,"magazine":{"id":7,"title":"October issue","can_read":true},"can_read":true,"delivery_type":"print","show_selected_magazine":true,"next_action":"subscriptions","dashboard_endpoint":"https://YOUR_HOST/api/user/dashboard","plans_endpoint":"https://YOUR_HOST/api/plans","single_purchase_endpoint":"https://YOUR_HOST/api/user/magazines/7/checkout"}
```

Magazine abbreviated; actual is full Magazine. Dashboard action only when Digital selected and readable. Print/Both action remains subscriptions. 404 unpublished/unmapped; 422 invalid enum. Offered-format availability is checked later at checkout.

### GET /api/user/articles/{article}

Bearer required. `/api/user/articles/42`: `{"data":Article}` with unlocked content when eligible. Free published articles also succeed. 404 missing/unpublished. Paid without access returns 403:

```json
{"message":"Please subscribe or buy this article to read it.","single_article_price":"25.00","purchase_endpoint":"https://YOUR_HOST/api/user/articles/faith-today/purchase"}
```

### POST /api/user/articles/{article}/purchase — legacy direct grant

Bearer required. **Records paid access without verifying Stripe. Do not treat this as trusted paid checkout.** Current Buy Now uses linked-magazine verified Checkout.

Optional transaction_id string ≤255 and amount_paid numeric ≥0. Empty body accepted, amount defaults article price.

```json
{"transaction_id":"external_reference","amount_paid":25}
```

201 response (Article abbreviated, purchase raw model):

```json
{"message":"Article purchased successfully.","purchase":{"id":3,"user_id":12,"article_id":42,"amount_paid":25,"payment_status":1,"transaction_id":"external_reference","purchased_at":"2026-10-28T10:00:00.000000Z"},"article":{"id":42,"title":"Faith today","can_read":true}}
```

Repeat updates same user/article purchase. Free: 422 `This article is free.`; unpublished/missing: 404. Client-supplied transaction reference is not proof of payment.

## Magazine catalogue

Public; pdf_url hidden without digital entitlement.

| Endpoint | Request and purpose | Response |
| --- | --- | --- |
| GET /api/magazines | `?search=October&sort_by=issue_date&sort_order=desc&per_page=12&page=1`; sort_by title/issue_date/published_at/created_at, order asc/desc, per_page integer 1–100 | Paginated Magazine array; 422 invalid validated parameters |
| GET /api/magazines/latest | No parameters; latest eight by published_at | `{"data":[Magazine,...]}` |
| GET /api/magazines/search | `?q=October&page=1`; q required string 2–255; title/description, fixed 10/page | Paginated Magazine array; 422 invalid q |
| GET /api/magazines/{magazine_issue} | `/api/magazines/7`; includes published articles | `{"data":Magazine}` including articles; missing 404 |
| GET /api/magazines/stats | No parameters | `{"total_issues":12,"latest_issue":Magazine,"total_articles":8}`; count is for newest-created issue, not all issues |

## Magazine checkout, library and reader

All endpoints in this section require bearer authentication.

### POST /api/user/magazines/{magazineIssue}/checkout

Native PaymentSheet payment, matching package payments. Alias: **POST /api/user/magazines/{magazineIssue}/payment-intent**. Bearer required. Send delivery_type and, for Print/Both, delivery_address. Response includes requires_payment, client_secret/paymentIntent, payment_intent_id/paymentIntentId, customer, ephemeralKey, publishable_key/publishableKey, amount (minor units), currency, price and delivery_type. No Checkout URL or Session ID is returned. Zero-price or already-owned formats return requires_payment:false; skip PaymentSheet.

### POST /api/user/magazines/confirm-payment

Bearer required. Body: payment_intent_id string (required, maximum 255). Verifies Stripe success, magazine payment kind and account, then saves format/amount/address from payment metadata. 201 newly purchased, 200 already purchased; response includes purchase, can_read, reader_url, delivery_type, magazine_id, requires_payment:false and requires_purchase:false. Invalid/mismatched/pending payments return 422. Missing Stripe config 500; upstream failure 502. Confirmation is safe to retry.

Full request/response examples, SDK mapping, error cases and migration guidance: [Magazine PaymentSheet API](magazine-paymentsheet-api.md).

### GET /api/user/magazines/payment/success

Example `?session_id=cs_test_example`. Session ID required string matching `^cs_[a-zA-Z0-9_]+$`. Server verifies paid payment-mode session and user metadata.

```json
{"message":"Magazine purchased.","magazine_id":7,"already_purchased":true,"delivery_type":"both","purchase":{"id":3,"user_id":12,"magazine_issue_id":7,"delivery_type":"both","amount_paid":"50.00","transaction_id":"cs_test_example","delivery_address":{"name":"Example Reader","phone":"1234567890","line1":"12 Main Street","city":"Kochi","state":"Kerala","postal_code":"682001","country":"India"}},"can_read":true,"requires_purchase":false,"reader_url":"https://example.com/reader"}
```

purchase is a raw model; timestamps/acknowledgement columns can also appear. Print alone returns can_read false and reader_url null. Repeat confirmations do not duplicate same-format orders. 422 invalid ID/unpaid/nonpayment session; 403 wrong user; 404 missing magazine; 503 Stripe absent; 502 `Unable to verify payment. Please try again.`.

### GET /api/user/magazines/{magazineIssue}/access

Example `/api/user/magazines/7/access`, no body. Explains issue-specific coverage:

```json
{"magazine_id":7,"issue_date":"2026-10-28","effective_issue_date":"2026-10-28","already_purchased":false,"purchased_types":["print"],"prices":{"digital":"25.00","print":"40.00"},"available_types":["digital","print"],"access_source":"subscription","has_active_subscription":true,"subscription":{"id":4,"plan_id":2,"plan_name":"Monthly Reader","status":"active","payment_status":true,"starts_at":"2026-10-28T10:00:00+00:00","expires_at":"2026-11-28T10:00:00+00:00","is_active":true,"coverage_start":"2026-10-28","coverage_end":"2026-11-28","magazine_in_period":true,"delivery_type":"digital","grants_access":true},"subscription_checks":[{"id":4,"plan_id":2,"plan_name":"Monthly Reader","status":"active","payment_status":true,"starts_at":"2026-10-28T10:00:00+00:00","expires_at":"2026-11-28T10:00:00+00:00","is_active":true,"coverage_start":"2026-10-28","coverage_end":"2026-11-28","magazine_in_period":true,"delivery_type":"digital","grants_access":true}],"can_read":true,"requires_purchase":false,"single_purchase_available":true,"price":"25.00","currency":"inr","reader_url":"https://example.com/reader"}
```

subscription_checks contains all subscriptions. Selected subscription prefers a granting one, then active, then newest, else null. already_purchased means Digital ownership, not merely a Print order. access_source is single_purchase/subscription/null. effective_issue_date falls back to publication date. Final can_read controls reading. Missing magazine 404.

### GET /api/user/magazines

`?page=1`, fixed 12/page, latest issue date first. Paginated Magazine array of owned/covered Digital/Both library. Print-only ownership excluded. Articles omitted unless loaded, but total_articles still returned.

### GET /api/user/magazine-purchases

`?page=1`, fixed 12/page, newest first. Paginated MagazinePurchase array including Print and original amount/format/address. Does not include package subscriptions or legacy article purchases.

### GET /api/user/magazines/{magazine_issue}/reader

Example `/api/user/magazines/7/reader`, no body. Magazine abbreviated:

```json
{"magazine":{"id":7,"title":"October issue","can_read":true,"articles":[]},"reader":{"type":"pdf","pdf_url":"https://example.com/reader","has_pdf":true,"has_articles":false}}
```

Actual magazine includes full published Article objects. Reader type pdf when URL exists, otherwise article; pdf label can represent external reader, not necessarily a direct PDF. Missing issue 404. Denied 403: `A subscription covering this issue or a single magazine purchase is required.`.

## Dashboard

### GET /api/user/dashboard

Bearer required, no query filters. Empty-library example, User abbreviated:

```json
{"user":{"id":12,"name":"Example Reader","email":"reader@example.com"},"subscription":null,"can_read_paid_articles":false,"purchased_article_ids":[],"latest_articles":[],"magazines":[],"magazine_purchases":[]}
```

subscription = latest current SubscriptionWithPlan/null. latest_articles = newest six eligible published Article objects. magazines = all accessible Magazine objects newest issue date first. magazine_purchases = all MagazinePurchase resource objects, including Print. These arrays are not paginated. purchased_article_ids includes successful legacy article purchases. can_read_paid_articles indicates active digital package capability, not ownership/coverage for every issue.

## Categories, banners, events and apparel

All public. Each resource's full fields appear in response objects below.

| Endpoint | Request and explanation | Response |
| --- | --- | --- |
| GET /api/categories | `?search=faith&per_page=50&page=1`, name/description search | Paginated Category array, subcategories/sliders included |
| GET /api/categories/all | No parameters, navigation/dropdowns | `{"data":[Category,...]}` |
| GET /api/categories/{category} | `/api/categories/1?page=1`; primary/additional articles, title year/month order then publication/ID | `{"data":Category}` plus articles (internally 10/page); missing 404. Nested pagination metadata is not exposed; use article category route for top-level pagination |
| GET /api/banners | `?per_page=10&page=1`, active, newest | Paginated Banner array |
| GET /api/banners/active | No parameters, all active | `{"data":[Banner,...]}` |
| GET /api/banners/{banner} | `/api/banners/1` | `{"data":Banner}`; missing/inactive 404, inactive message `Banner not found.` |
| GET /api/ad-banners | No parameters; active advertisements, model-defined order | `{"data":[{"id":1,"title":"Sponsor","image_url":"https://YOUR_HOST/uploads/ad.jpg","link":"https://example.com","status":true}]}` |
| GET /api/events | `?search=retreat&sort_by=created_at&sort_order=desc&per_page=20&page=1`; title/description search | Paginated Event array; intended active only, search grouping caveat below |
| GET /api/events/upcoming | No parameters; latest eight active, no future-date filter | `{"data":[Event,...]}` |
| GET /api/events/search | `?q=retreat&page=1`; q required string 2–255; title/subtitle/description search, active only, 10/page | Paginated Event array; 422 invalid q |
| GET /api/events/{event} | `/api/events/1` | `{"data":Event}`; missing/inactive 404, inactive message `Event not found.` |
| GET /api/events/stats | No parameters | `{"total_events":10,"active_events":8,"inactive_events":2,"latest_event":Event}`; latest by creation can be inactive |
| GET /api/apparel/categories | `?search=shirts`; active name/description match, sort_order/name | `{"data":[ApparelCategory,...]}`; product count is not restricted to published by controller |
| GET /api/apparel/products | `?category_id=1&category_slug=shirts&search=cotton&featured=1&sort_by=price&sort_order=asc&per_page=12&page=1` | Paginated ApparelProduct array, published only, category/images/variants/options loaded |

Apparel product filters are optional; both category filters apply if both sent, and refer to apparel categories, not article categories. Search matches name/SKU/short_description. featured=true applies featured filter. Allowed sort_by name/price/created_at/sort_order, fallback sort_order. sort_order desc only for literal desc, otherwise asc. per_page defaults 12, max 50; send positive integers. Variant status is returned; controller does not filter to active variants. No apparel detail/cart/order/checkout API is registered.

## Response objects

These define full fields referenced above, not request schemas. Conditional relations can be omitted. Empty values may be null. Raw models are explicitly identified and can include more database fields.

### User (raw)

Includes id, name, email, created_at, updated_at and stored user columns such as region_id, stripe_customer_id, email_verified_at, deleted_at when present. Password and remember_token are hidden. Dates generally use `YYYY-MM-DD HH:mm:ss`; email verification has a model-specific configured display format.

### Article

```json
{"id":42,"title":"Faith today","summary":"A reflection","content":"<p>Readable content</p>","blocks":[{"id":10,"type":"text_image","order":0,"data":{"content":"<p>Body</p>","image":"uploads/articles/blocks/photo.jpg","image_url":"https://YOUR_HOST/uploads/articles/blocks/photo.jpg"}}],"featured_image":"https://YOUR_HOST/uploads/articles/featured/photo.jpg","left_side_fixed_image":null,"access_type":"free","single_article_price":"25.00","can_read":true,"is_locked":false,"purchase_required":false,"status":"published","published_at":"2026-10-28T10:00:00.000000Z","category":{"id":1,"name":"Faith"},"categories":[{"id":1,"name":"Faith"}],"author_name":"Guest Writer","author":{"id":null,"name":"Guest Writer"},"issue":{"id":7,"title":"October issue"},"gallery_images":[{"id":1,"image_path":"uploads/articles/gallery/a.jpg","image_url":"https://YOUR_HOST/uploads/articles/gallery/a.jpg"}],"buy_button_link":null,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

Locked paid: content/blocks/buy_button_link null, can_read false, is_locked/purchase_required true. Other metadata/gallery remains exposed. categories/gallery/blocks conditional on loading. Block data varies by editor type. Left fixed image adds a synthetic first block with null ID and shifts later orders. New plain author names have author.id null; legacy user-linked authors may retain an ID. Author user accounts are unnecessary.

### Magazine

```json
{"id":7,"title":"October issue","description":"Monthly magazine","issue_date":"2026-10-28T00:00:00.000000Z","published_at":"2026-10-28T10:00:00.000000Z","has_reader":true,"has_pdf":true,"pdf_url":null,"price":"25.00","prices":{"digital":"25.00","print":"40.00","both":"50.00"},"available_types":["digital","print","both"],"formats":[{"type":"digital","price":"25.00","includes_digital":true,"includes_print":false,"requires_delivery_address":false},{"type":"print","price":"40.00","includes_digital":false,"includes_print":true,"requires_delivery_address":true},{"type":"both","price":"50.00","includes_digital":true,"includes_print":true,"requires_delivery_address":true}],"can_read":false,"reader_type":"pdf","cover_image":null,"total_articles":3,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

articles:[Article,...] only when loaded (detail/reader). pdf_url absolute only with request user's entitlement; otherwise null. has_reader always true in current resource; not proof of actual availability. Blank format price disables it; legacy price is lowest offered, not selected-format price.

### Plan

```json
{"id":2,"print_enabled":true,"requires_delivery_address":true,"name":"Monthly Reader","description":"One month","price":"100.00","prices":{"digital":"100.00","print":"150.00","both":"200.00"},"available_types":["digital","print","both"],"formats":[{"type":"digital","price":"100.00","includes_digital":true,"includes_print":false,"requires_delivery_address":false},{"type":"print","price":"150.00","includes_digital":false,"includes_print":true,"requires_delivery_address":true},{"type":"both","price":"200.00","includes_digital":true,"includes_print":true,"requires_delivery_address":true}],"is_trial":false,"purchasable":true,"duration":{"value":1,"type":"months","days":30,"label":"1 month"},"features":["Magazine access"],"is_active":true,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

Use selected format's requires_delivery_address. Top-level flag means plan offers printing. Month/year duration days may be approximations; actual subscription dates are authoritative. Raw plans embedded in subscriptions/payment responses use database fields (duration scalar, duration_type), not this normalized duration object.

### SubscriptionWithPlan (raw)

```json
{"id":4,"user_id":12,"plan_id":2,"start_date":"2026-10-28T10:00:00.000000Z","end_date":"2026-11-28T10:00:00.000000Z","status":"active","payment_status":true,"amount_paid":"200.00","transaction_id":"pi_example","delivery_type":"both","print_enabled":true,"delivery_address":{"name":"Example Reader","phone":"1234567890","line1":"12 Main Street","line2":null,"city":"Kochi","state":"Kerala","postal_code":"682001","country":"India"},"print_acknowledged_at":null,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z","plan":{"id":2,"name":"Monthly Reader","price":"100.00","duration":1,"duration_type":"months","digital_price":"100.00","print_price":"150.00","both_price":"200.00"}}
```

Nested raw plan above abbreviated. Current nested plan configuration can change; subscription amount_paid/format/address are the purchase snapshot. Digital has print_enabled false and address null. print_acknowledged_at null means admin has not acknowledged the print purchase; no shipment tracking is implemented by this field.

### Address

delivery_address must be an object for Print/Both. Required strings ≤200: name, phone, line1, city, state, postal_code, country. line2 optional nullable string ≤200. Phone/postal code stay strings. Country is free text, not constrained ISO. Digital needs no address. Delivery is included in selected price. Address is shared with Stripe as payment metadata.

### MagazinePurchase (history resource)

```json
{"id":3,"magazine_issue_id":7,"delivery_type":"print","amount_paid":"40.00","delivery_address":{"name":"Example Reader","phone":"1234567890","line1":"12 Main Street","city":"Kochi","state":"Kerala","postal_code":"682001","country":"India"},"print_acknowledged_at":null,"created_at":"2026-10-28T10:00:00.000000Z","magazine":{"id":7,"title":"October issue","can_read":false,"pdf_url":null}}
```

Nested magazine abbreviated; full Magazine when loaded. This history resource differs from raw confirmation purchase, which includes user_id, transaction_id, timestamps and stored columns.

### Category

```json
{"id":1,"name":"Faith","description":"Faith articles","category_subtitle":"Reflections","subtitle_description":"Read more","parent_category_id":null,"sub_categories":[{"id":2,"name":"Prayer","description":null,"category_subtitle":null,"subtitle_description":null,"parent_category_id":1,"banner_image":null}],"banner_image":null,"banner_sliders":[{"id":1,"image":"https://YOUR_HOST/uploads/banner.jpg","link":"https://example.com"}],"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

Detail adds articles array; article relations not eagerly loaded there can omit blocks/gallery/additional categories.

### Banner and Event

```json
{"id":1,"image":"https://YOUR_HOST/uploads/banner.jpg","status":1,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

```json
{"id":1,"title":"Retreat","subtitle":"Join us","description":"Event details","image":"https://YOUR_HOST/uploads/event.jpg","button_link":"https://example.com/register","status":1,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

### ApparelCategory

```json
{"id":1,"name":"Shirts","slug":"shirts","description":"Cotton shirts","image":null,"sort_order":1,"products_count":5,"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

products_count conditional, usually omitted from nested product category.

### ApparelProduct, Variant and Image

```json
{"id":5,"category_id":1,"category":{"id":1,"name":"Shirts","slug":"shirts","description":null,"image":null,"sort_order":1,"created_at":null,"updated_at":null},"name":"Cotton shirt","slug":"cotton-shirt","sku":"SHIRT-01","price":"500.00","compare_at_price":"600.00","stock":10,"is_featured":true,"short_description":"Soft cotton","description":"<p>Details</p>","size_guide":null,"product_details":null,"care_instructions":null,"shipping_delivery":null,"return_policy":null,"primary_image":{"id":1,"url":"https://YOUR_HOST/uploads/shirt.jpg","alt_text":"Shirt","sort_order":0},"images":[{"id":1,"url":"https://YOUR_HOST/uploads/shirt.jpg","alt_text":"Shirt","sort_order":0}],"variants":[{"id":2,"option_type":"color_size","option_value":"Blue / M","color_name":"Blue","color_hex":"#0000ff","size":"M","sku":"SHIRT-BL-M","price":"500.00","stock":4,"status":true,"sort_order":0,"options":[{"name":"Color","type":"color","value":"Blue","color_hex":"#0000ff"},{"name":"Size","type":"text","value":"M","color_hex":null}],"images":[]}],"created_at":"2026-10-28T10:00:00.000000Z","updated_at":"2026-10-28T10:00:00.000000Z"}
```

primary_image and variant price can be null. Options describe configured option names/types/values. Inspect variant status before offering it.

## Stripe magazine webhook (outside /api)

### POST /stripe/magazines/webhook

Server-to-server web route. No bearer/CSRF; requires Stripe-Signature header over original raw body and configured magazine_webhook_secret. HMAC SHA-256 signature with 300-second timestamp tolerance.

Illustrative request (must have real signature):

```json
{"type":"checkout.session.completed","data":{"object":{"id":"cs_test_example","mode":"payment","payment_status":"paid","amount_total":2500,"metadata":{"user_id":"12","magazine_issue_id":"7","delivery_type":"digital","print_enabled":"0"}}}}
```

Print/Both has delivery metadata set at checkout. Handles checkout.session.completed and checkout.session.async_payment_succeeded when paid, payment mode, and magazine/user IDs present. 200 `{"received":true}` also for ignored events. 400 invalid/missing/stale signature; 503 missing secret. Same-format fulfillment is idempotent. This webhook does not activate package Payment Intent subscriptions.

## Review findings and limits

These are existing implementation findings, not fixes made by this documentation task.

1. **Legacy article purchase grants access without payment verification.** Empty body succeeds. Retire or verify trusted payment before using it as paid checkout; use verified magazine flow for current Buy Now.
2. **Login/register nested magazine access can disagree with purchase_context.can_read.** Context uses authenticated User variable; nested MagazineResource uses request.user(), whose resolver is not set by login/register. Trust context routing, then re-fetch protected access with new token.
3. **Public routes do not resolve bearer tokens.** Public paid detail stays 403 and public list content stays locked; use protected readers/libraries.
4. **Event index search has an ungrouped description OR.** It can match inactive events. Dedicated events/search groups conditions correctly. Event stats latest may also expose inactive event intentionally by current query.
5. **Several list inputs lack bounds/allowlists.** Article/event index sort fields and some per_page parameters are not validated. Use existing sort fields and positive page sizes; invalid values can produce server/query errors instead of 422.
6. **Empty stats datasets need dedicated handling/tests.** Latest resources can be constructed from null records and resource code dereferences model fields/relations. Do not assume all stats endpoints safely serialize an empty installation.
7. **Reader URLs are hidden, not signed/expiring here.** External/public reader host authorization is outside this API. has_reader is always true and new-checkout can_read is hardcoded false; use protected access for decisions.
8. **Capability, active subscription and issue coverage differ.** Queued renewal may not be active. Print does not grant digital. Access diagnostics require non-null end dates although general active-subscription logic accepts null ends; indefinite subscriptions need follow-up verification.
9. **Purchase context is routing metadata.** Login/buy-options validate enum but not offered format. Checkout validates availability. Send explicit supported enum values; nullable validation means explicit null can differ from omission.
10. **No missing APIs should be assumed.** This route file has no profile update, API password reset, token refresh, author CRUD, apparel orders, refunds, or cancellation endpoints. Admin article creation/editing are web forms accepting author_name.
11. **Raw models are not stable normalized resources.** Tolerate extra fields and differences in nested raw Plan versus PlanResource. amount_paid, not current plan.price, is purchase amount.
12. **Magazine native confirmation must be retried after interruption.** The existing Checkout webhook does not fulfill PaymentIntents. Retain the PaymentIntent ID to retry native confirmation if the app closes after payment.

## Verification and related guides

Every route in routes/api.php was reviewed against controllers/resources and the registered route inventory. Automated tests check application behavior with mocked Stripe operations; no real charge, email, external reader authorization, or live webhook delivery was performed. Test outcome is recorded in the delivery message.

Verification on 28 October 2026: all **54 registered API routes** are represented in this document, and **40 JSON code examples** parsed successfully. `php artisan test --compact`: **66 passed, 1 failed (455 assertions)**. The failure is `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response`: its homepage request returns 500 because the in-memory SQLite database has no articles table. This is a test setup failure in the generic homepage example; the other tests passed. No application code was changed by this documentation review.

Focused guides: [membership/search](membership-and-search-api.md), [print packages](print-packages-api.md), [Stripe subscriptions](stripe-subscription-flow.md), [magazine purchases](magazine-purchases.md), [advertisements](ad-banners.md). This consolidated reference describes current source when an older example differs.

Format display labels: plan and magazine resource `formats` entries now include `label` alongside `type`, for example `{"type":"both","label":"Digital + Print","price":"150.00"}`. Mobile apps should display `label` and continue submitting `delivery_type:"both"`. The stored/API value remains unchanged.
## Apparel ecommerce

See [Apparel ecommerce API](apparel-ecommerce-api.md) for catalog search/details,
category products, saved cart, orders, Stripe PaymentSheet and webhook integration.
