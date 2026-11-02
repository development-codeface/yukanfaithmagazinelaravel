// node tests/browser/apparel-checkout.mjs <path-to-playwright-core> [base-url]
import assert from 'node:assert/strict';
import {pathToFileURL} from 'node:url';

const {chromium} = await import(pathToFileURL(process.argv[2]).href);
const base = process.argv[3] || 'http://localhost:8000';
const browser = await chromium.launch({headless: true});
try {
    const page = await browser.newPage({viewport: {width: 1366, height: 900}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/apparel', {waitUntil: 'networkidle'});
    await page.locator('.product-card h2').first().click();
    await page.getByRole('button', {name: 'Add to cart', exact: true}).click();
    await page.waitForSelector('#cart-drawer:not([hidden])');
    await page.getByRole('link', {name: 'Go to checkout', exact: true}).click();
    await page.waitForSelector('#checkout-submit');
    assert.equal(await page.getByRole('heading', {name: 'Shipping Address', exact: true}).isVisible(), true);
    assert.equal(await page.locator('[data-checkout-next]').count(), 0);
    assert.equal(await page.locator('input[name="password_confirmation"]').evaluate(input => input.required), true);
    await page.getByRole('radio', {name: 'Sign in', exact: true}).check();
    assert.equal(await page.locator('input[name="password_confirmation"]').isDisabled(), true);
    await page.getByRole('radio', {name: 'Create account', exact: true}).check();
    assert.equal(await page.locator('input[name="password_confirmation"]').isEnabled(), true);
    await page.getByRole('radio', {name: 'Sign in', exact: true}).check();
    const fields = {first_name: 'Sample', last_name: 'Customer', email: 'customer@example.com', phone: '+919876543210', password: 'ExamplePassword123!', country: 'India', street: '12 Sample Street', city: 'Chennai', region: 'Tamil Nadu', postal_code: '600001'};
    for (const [name, value] of Object.entries(fields)) await page.locator(`[name="${name}"]`).fill(value);
    await page.screenshot({path: 'storage/app/apparel-checkout-preview.png', fullPage: true});
    await page.setViewportSize({width: 390, height: 844});
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({path: 'storage/app/apparel-checkout-mobile-preview.png', fullPage: true});
    let attempts = 0, orders = 0;
    await page.route('**/apparel/checkout/account', async route => {
        const body = route.request().postData();
        assert.match(body, /login/);
        assert.doesNotMatch(body, /name="password_confirmation"/);
        attempts++;
        await route.fulfill({status: attempts === 1 ? 422 : 200, contentType: 'application/json', body: JSON.stringify(attempts === 1
            ? {message: 'Unable to sign in.', errors: {password: ['Check your password and try again.']}}
            : {authenticated: true, csrf_token: 'rotated-test-token'})});
    });
    await page.route('**/apparel/checkout', async route => {
        orders++;
        const body = route.request().postData();
        assert.match(body, /rotated-test-token/);
        assert.match(body, /hosted/);
        assert.match(body, /12 Sample Street/);
        assert.doesNotMatch(body, /name="password"/);
        await route.fulfill({contentType: 'application/json', body: JSON.stringify({redirect: base + '/apparel?checkout-test=complete'})});
    });
    await page.locator('#checkout-submit').click();
    await page.waitForSelector('#checkout-error:not([hidden])');
    assert.match(await page.locator('#checkout-error').textContent(), /Check your password/);
    assert.equal(await page.locator('[name="street"]').inputValue(), fields.street);
    assert.equal(orders, 0);
    assert.equal(await page.locator('#checkout-submit').isEnabled(), true);
    await page.locator('#checkout-submit').click();
    await page.waitForURL('**/apparel?checkout-test=complete');
    assert.equal(attempts, 2);
    assert.equal(orders, 1);
    assert.deepEqual(errors, []);
    console.log('PASS: one-page checkout, account modes, preserved address on login errors, CSRF rotation, direct payment redirect and mobile layout. Provider/auth responses mocked; no accounts, orders or charges created.');
} finally {
    await browser.close();
}
