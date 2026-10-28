# Article search and membership API

## Article search

Public endpoint; no token required. Matches published article titles, summaries, and content.
Paid content and blocks are hidden from unauthenticated search results.

```http
GET /api/articles/search?q=faith&per_page=10&page=1
Accept: application/json
```

| Parameter | Required | Rules |
| --- | --- | --- |
| `q` | Yes | String, 2–255 characters; at least 2 after trimming |
| `per_page` | No | Integer, 1–100; default 10 |
| `page` | No | Integer, minimum 1; default 1 |
| `category_id` | No | Existing category ID; checks primary and additional categories |

HTTP 200 example, with illustrative resource values:

```json
{
  "data": [
    {
      "id": 42,
      "title": "Faith today",
      "summary": "A reflection on hope",
      "content": null,
      "blocks": null,
      "featured_image": null,
      "left_side_fixed_image": null,
      "access_type": "paid",
      "single_article_price": "25.00",
      "can_read": false,
      "is_locked": true,
      "purchase_required": true,
      "status": "published",
      "published_at": "2026-10-27T06:30:00.000000Z",
      "category": {"id": 1, "name": "Faith"},
      "categories": [{"id": 1, "name": "Faith"}],
      "author": {"id": 2, "name": "Editor"},
      "issue": {"id": 7, "title": "October issue"},
      "gallery_images": [],
      "buy_button_link": null,
      "created_at": "2026-10-27T06:30:00.000000Z",
      "updated_at": "2026-10-27T06:30:00.000000Z"
    }
  ],
  "links": {
    "first": "https://your-domain/api/articles/search?q=faith&per_page=10&page=1",
    "last": "https://your-domain/api/articles/search?q=faith&per_page=10&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {"url": null, "label": "&laquo; Previous", "active": false},
      {"url": "https://your-domain/api/articles/search?q=faith&per_page=10&page=1", "label": "1", "active": true},
      {"url": null, "label": "Next &raquo;", "active": false}
    ],
    "path": "https://your-domain/api/articles/search",
    "per_page": 10,
    "to": 1,
    "total": 1
  }
}
```

No matches: `data: []`, `meta.total: 0`. Invalid parameters return HTTP 422:

```json
{
  "message": "The q field is required.",
  "errors": {"q": ["The q field is required."]}
}
```

## Package format selection

`GET /api/plans` and `GET /api/plans/{plan}` now include the following fields (resource excerpt):

```json
{
  "id": 2,
  "name": "Monthly Reader",
  "price": "100.00",
  "prices": {"digital": "100.00", "print": "150.00", "both": "200.00"},
  "available_types": ["digital", "print", "both"],
  "formats": [
    {"type": "digital", "price": "100.00", "includes_digital": true, "includes_print": false, "requires_delivery_address": false},
    {"type": "print", "price": "150.00", "includes_digital": false, "includes_print": true, "requires_delivery_address": true},
    {"type": "both", "price": "200.00", "includes_digital": true, "includes_print": true, "requires_delivery_address": true}
  ],
  "is_trial": false,
  "purchasable": true
}
```

Unavailable formats are omitted. Legacy `price` is the lowest offered price. Display the selected
format's price and send `delivery_type` to purchase it. The price includes delivery.

```http
POST /api/user/subscription/payment-intent/2
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
Accept: application/json

{"delivery_type":"digital"}
```

For Print or Both, include `delivery_address` as described in [print-packages-api.md](print-packages-api.md).
Complete Stripe payment, then call `POST /api/user/subscription/confirm-payment` with
`{"payment_intent_id":"pi_example"}`. Confirmed subscriptions expose `delivery_type`, `amount_paid`,
`print_enabled`, and `delivery_address`. They retain the format and amount originally purchased,
even if plan prices change. Free non-trial plans can use `POST /api/user/subscribe/{plan}` with
the same format/address fields.

The Free Trial has `purchasable: false` and is granted once per new account. Default: 7 days.
Edit its Duration in admin to change future registrations without code edits. Existing expiry
dates remain unchanged.

## Article Buy Now and registration

```http
GET /api/user/articles/42/buy-options
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Response example (`magazine` is a full MagazineIssueResource, abbreviated here):

```json
{
  "article_id": 42,
  "magazine_id": 7,
  "magazine": {"id": 7, "title": "October issue", "price": "25.00", "can_read": false},
  "can_read": false,
  "next_action": "subscriptions",
  "dashboard_endpoint": "https://your-domain/api/user/dashboard",
  "plans_endpoint": "https://your-domain/api/plans",
  "single_purchase_endpoint": "https://your-domain/api/user/magazines/7/checkout"
}
```

`next_action` is `dashboard` for eligible users and `subscriptions` for others. Reader links
stay hidden without access. Unpublished or unmapped articles return 404. Guests register or
log in first.

Clients may send optional `magazine_id` with `/api/register` or `/api/login`. The existing
user/token response gains:

```json
{
  "purchase_context": {"magazine_id": 7, "can_read": false, "next_action": "subscriptions"}
}
```

Registration grants the trial before checking eligibility. Keep `magazine_id` in client state
when opening subscriptions. Show its single-magazine price and available packages. The endpoint
`GET /api/user/magazines/{id}/access` explains subscription coverage dates.

On the website, the magazine selection survives registration/login and format tab changes.
Eligible article Buy Now clicks and successful web purchases lead to the dashboard. Read More
then opens the external magazine reader.

## Library and coverage

- Dashboard articles are individual purchases or articles in owned/covered magazines. Unmapped
  paid articles remain available with active digital subscriptions. Standalone unpurchased free
  articles remain in the public catalogue.
- Dashboard magazines are single purchases or issues covered by active Digital/Both packages.
- Print-only subscriptions collect addresses and notify admin but do not unlock digital reading.
- Issue dates must fall within subscription start/end calendar dates; `published_at` is the
  fallback when issue date is missing. Coverage no longer expands to the signup calendar month.
- Subscriptions must also be active now. Single-magazine ownership survives subscription expiry.
- Admins set article `issue_id` on article create/edit forms. Editing a magazine preserves mappings.

## Single-magazine formats

Magazine list/detail resources and article-to-magazine responses now expose `prices` and
`available_types`. Magazine resources also include `formats`, with the same structure used
by packages. Each magazine can offer Digital, Print, Both, or any subset. Its legacy `price`
is the minimum offered price. Existing magazine prices become Digital prices.

Select the format when starting single-magazine checkout:

```http
POST /api/user/magazines/7/checkout
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json

{
  "delivery_type": "both",
  "delivery_address": {
    "name": "Reader",
    "phone": "+919876543210",
    "line1": "12 Main Street",
    "city": "Kochi",
    "state": "Kerala",
    "postal_code": "682001",
    "country": "India"
  }
}
```

Digital requires no address. Print/Both require all address fields above; `line2` is optional.
All address fields are strings with a 200 character maximum. The server chooses the selected
format's stored price, including delivery. Unsupported formats and missing addresses return 422.
`embedded: true` may be added for the existing embedded Stripe checkout.

Checkout response example:

```json
{
  "magazine_id": 7,
  "delivery_type": "both",
  "price": "50.00",
  "can_read": false,
  "requires_purchase": true,
  "session_id": "cs_example",
  "client_secret": null,
  "checkout_url": "https://checkout.stripe.com/example"
}
```

Confirm using the existing `GET /api/user/magazines/payment/success?session_id=cs_example`.
Its response includes `delivery_type`, the saved `purchase`, and current `can_read`/`reader_url`.
Print-only purchases do not grant digital access; their reader URL stays null unless the user
has another digital entitlement. Repeated confirmations/webhooks do not create duplicate orders.
The verified payment preserves its original format, amount, and address even if prices change.

`GET /api/user/magazine-purchases` returns paginated order history, including Print orders.
Each resource contains `id`, `magazine_issue_id`, `delivery_type`, `amount_paid`, `delivery_address`,
`print_acknowledged_at`, `created_at`, and a magazine resource with access-controlled reader links.
`GET /api/user/dashboard` also includes `magazine_purchases`.
`GET /api/user/magazines` remains the digital reader library; it excludes Print-only orders.
The access endpoint adds `purchased_types`, `prices`, and `available_types`.

Admins manage magazine prices on issue create/edit and review deliveries under
Subscription Management → Magazine purchases. New print deliveries trigger a persistent admin
alert until acknowledged. Users see print magazine orders in their dashboard.

## Author names and purchase context (28 October 2026)

Article creation/editing now require `author_name` (string, maximum 255 characters).
Authors do not need user accounts. The migration copies existing bylines from users.
Article API resources expose `author_name` and retain `author: {id, name}` for compatibility.
New bylines have a null author ID. The admin list no longer filters by free/paid type.

Normal subscription-page visits show no selected magazine. Buy Now passes the magazine ID
through login/registration and into the subscription URL. Format/navigation links retain
that explicit ID; later visits without it do not restore a previous selection.

API clients should retain purchase context only during the current Buy Now flow and clear
it when leaving checkout. Registration/login accept optional `magazine_id` and `delivery_type`
(`digital`, `print`, or `both`; default Digital). Their `purchase_context` now includes
`delivery_type`, `show_selected_magazine`, and a full `magazine` resource. Without a magazine
ID, these fields are null, false, and null. Previous API selections are never restored.

`GET /api/user/articles/{article}/buy-options?delivery_type=print` also returns
`delivery_type` and `show_selected_magazine`. Print/Both selections lead to
`next_action: subscriptions` even when the user already has digital access.
Send the selected format to the existing checkout endpoint. Print/Both still require
an address. Existing format pricing, trial, coverage, and payment confirmation contracts
are described above.
