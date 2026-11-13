# Store and account updates

## Apparel banners

Manage images in **Admin → Apparel Store → Apparel Banners**. Each banner has an optional title/link, active status, and display order. Active banners appear above the collection heading on apparel catalogue pages, ordered by display order and then newest first.

Public API: `GET /api/apparel/banners`

```json
{
  "data": [
    {
      "id": 1,
      "title": "New collection",
      "image_url": "https://example.com/uploads/apparel-banners/banner.webp",
      "link": "https://example.com/apparel/category/women",
      "status": true,
      "sort_order": 0
    }
  ]
}
```

Inactive banners are excluded. With no active banners, `data` is an empty array. The image upload accepts JPG, PNG, and WebP files up to 4 MB.

The website filters as the visitor types, with a 300 ms debounce. Search, sorting, and pagination use AJAX and retain query parameters in the URL. Public product APIs continue to accept `search` on `GET /api/apparel/products`.

## Accounts and subscriptions

New website, API, and apparel-checkout registrations send a welcome email through the configured Laravel mail transport. Login does not resend it. Delivery failures are logged and do not prevent registration.

The user account page is `/user/subscriptions`, which includes the account name/email, payment-history link, and current subscription summary. The old `/user/dashboard` URL redirects there. The magazine library appears on this page only when the account has single-magazine purchases; package access alone does not populate it. Digital purchases offer a reader link, and print purchases show delivery status. Explicit checkout destinations are retained. Admin login keeps its admin destination.

Homepage articles show **Read Article + Subscribe** for guests and accounts without active digital package access. With active digital package access, they show **Read Article + Read Magazine**. The reader still enforces digital entitlement. When no issue is tagged to the article, the magazine button is disabled rather than opening an unrelated issue.

The storefront cart uses the local `shopping-cart-add-01-stroke-rounded.svg` Hugeicons asset. Header search uses the local `ai-search-01-stroke-rounded.svg` asset from Hugeicons.

Starting another package purchase or upgrade automatically cancels a scheduled trial auto-debit before payment setup continues. Remaining trial access is preserved. Pending hosted trial checkout sessions are expired, and pending PaymentSheet trial enrollments are invalidated. A provider cancellation failure stops the new purchase attempt. Merely browsing packages or requesting an upgrade quote does not cancel a trial.

When a new package is activated during a trial, its coverage starts immediately after successful payment confirmation; it is not queued behind the trial expiry. The website account summary and API subscription status then show that new package. Existing non-trial package renewal scheduling is unchanged.

## USD configuration

Set `STRIPE_CURRENCY=usd` in each environment and clear the Laravel configuration cache. The local environment and `.env.example` use USD. Current product/plan prices are interpreted as USD; there is no exchange-rate conversion. Existing receipts, subscriptions, and pending payment records keep their stored original currency.

For another deployment, run `php artisan migrate` to create `apparel_banners`, then clear cached configuration, views, and events. No banner content is seeded automatically.
