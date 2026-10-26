Magazine setup

In Admin > Magazine Issues, edit or create an issue. Set its external reader URL, optionally set its single-issue price, and select its articles. Each article belongs to one issue. Past Magazines displays published articles from the Past Magazines category. Map each magazine preview article to its issue to show Buy Now on the article detail page.

Buy Now requires login and preserves the chosen issue through login or registration. Active paid subscriptions grant all issue access under the existing subscription policy. Individual purchases grant permanent access to that issue and its mapped paid articles. A blank price offers subscription access only. Purchased issues and subscription issues appear under My Magazines. External links are checked by the server before redirecting. The external hosting provider controls whether a copied reader URL can be reused.

Payments use existing STRIPE_SECRET and STRIPE_CURRENCY settings. Configure STRIPE_MAGAZINE_WEBHOOK_SECRET and register POST /stripe/magazines/webhook in Stripe for checkout.session.completed and checkout.session.async_payment_succeeded. Use the signing secret for this endpoint. Both the verified return page and signed webhook grant access idempotently. Test with Stripe test keys before using live payments.

Migration: php artisan migrate
