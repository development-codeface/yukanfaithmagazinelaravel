// Run with: node tests/browser/apparel.mjs <path-to-playwright-core> [base-url]
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {pathToFileURL} from 'node:url';

const {chromium} = await import(pathToFileURL(process.argv[2]).href);
const base = process.argv[3] || 'http://localhost:8000';
const browser = await chromium.launch({headless: true});
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base, {waitUntil: 'domcontentloaded'});
    const apparel = page.getByRole('link', {name: 'Apparel (opens in a new tab)', exact: true});
    assert.equal(await apparel.getAttribute('target'), '_blank');
    assert.equal(await apparel.getAttribute('href'), base + '/apparel');
    await page.goto(base + '/apparel', {waitUntil: 'networkidle'});
    await page.locator('.product-card h2').first().click();
    await page.waitForSelector('#variant-selectors .color-swatch');
    await page.getByRole('button', {name: 'Red', exact: true}).click();
    assert.equal(await page.locator('input[name="variant_id"]:checked').getAttribute('value'), '4');
    assert.match(await page.locator('#product-price').textContent(), /INR 11.99/);
    await page.getByRole('button', {name: 'Add to cart', exact: true}).click();
    await page.waitForSelector('#cart-drawer:not([hidden])');
    assert.match(await page.locator('#cart-drawer').textContent(), /Red/);
    await page.locator('[data-cart-close]').click();
    await page.screenshot({path: 'storage/app/apparel-product-preview.png', fullPage: true});
    await page.setViewportSize({width: 390, height: 844});
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));

    // Exercise images and unavailable combinations without changing database products.
    const variants = [
        {id: 1, stock: 5, price: 10, images: [11, 12], options: [{key: 'color', name: 'Color', type: 'color', value: 'Pink', hex: '#f4c7c7'}, {key: 'size', name: 'Size', type: 'text', value: 'S'}]},
        {id: 2, stock: 3, price: 12, images: [21], options: [{key: 'color', name: 'Color', type: 'color', value: 'Red', hex: '#dc143c'}, {key: 'size', name: 'Size', type: 'text', value: 'M'}]},
        {id: 3, stock: 0, price: 10, images: [], options: [{key: 'color', name: 'Color', type: 'color', value: 'White', hex: '#ffffff'}, {key: 'size', name: 'Size', type: 'text', value: 'S'}]},
    ];
    await page.setContent(`<meta name="apparel-currency" content="USD"><img id="main-image"><p id="product-price"></p><form id="purchase-form"><input id="quantity" value="1" max="5"><div id="variant-selectors"></div><fieldset class="variant-options">${variants.map(v => `<input type="radio" name="variant_id" value="${v.id}" data-price="${v.price}" data-stock="${v.stock}">`).join('')}</fieldset><button class="add-button">Add to cart</button></form><script type="application/json" id="product-variants">${JSON.stringify(variants)}</script>${[11, 12, 21].map(id => `<button class="thumbnail" data-image-id="${id}" data-variant-id="${id === 21 ? 2 : 1}" data-image="${base}/image-${id}.jpg"></button>`).join('')}`);
    await page.addScriptTag({content: await readFile('public/js/apparel.js', 'utf8')});
    assert.match(await page.locator('#main-image').getAttribute('src'), /image-11/);
    await page.getByRole('button', {name: 'Red', exact: true}).click();
    assert.match(await page.locator('#main-image').getAttribute('src'), /image-21/);
    assert.equal(await page.locator('input[name="variant_id"]:checked').getAttribute('value'), '2');
    assert.equal(await page.locator('#product-price').textContent(), '$12.00');
    assert.equal(await page.locator('#quantity').getAttribute('max'), '3');
    assert.equal(await page.getByRole('button', {name: 'S', exact: true}).isDisabled(), true);
    assert.equal(await page.getByRole('button', {name: 'White', exact: true}).isDisabled(), true);
    assert.equal(await page.locator('.thumbnail:not([hidden])').count(), 1);
    await page.getByRole('button', {name: 'Pink', exact: true}).click();
    await page.locator('.thumbnail[data-image-id="12"]').click();
    assert.match(await page.locator('#main-image').getAttribute('src'), /image-12/);

    // Verify browser payment recovery with provider responses mocked, without charges.
    await page.setContent(`<form id="order-payment" data-start-url="/mock-start" data-confirm-url="/mock-confirm" data-return-url="${base}/return"><input name="_token" value="test-csrf"><div id="payment-error" hidden></div><div id="payment-status"></div><div id="payment-element"></div><button type="button" id="start-payment">Pay now</button><button id="submit-payment" hidden>Pay</button><button type="button" id="verify-payment" hidden>Check status</button></form><form id="cancel-order"></form>`);
    await page.evaluate(() => {
        let attempts = 0, starts = 0;
        window.paymentRequests = [];
        window.fetch = async (url, options) => {
            window.paymentRequests.push({url, body: JSON.parse(options.body)});
            if (url === '/mock-start' && ++starts === 1) throw new Error('Network interrupted. Retry payment.');
            return {ok: true, json: async () => url === '/mock-start' ? {publishable_key: 'pk_test', client_secret: 'pi_test_secret', payment_intent_id: 'pi_test'} : {redirect: '#payment-verified'}};
        };
        window.Stripe = () => ({
            retrievePaymentIntent: async () => ({paymentIntent: {status: 'requires_payment_method'}}),
            elements: () => ({create: () => ({mount: () => { document.getElementById('payment-element').textContent = 'Mock payment fields'; }}), getElement: () => ({destroy() {}})}),
            confirmPayment: async () => ++attempts === 1 ? {error: {message: 'Your card was declined.'}} : {paymentIntent: {status: 'succeeded'}},
        });
    });
    await page.addScriptTag({content: await readFile('public/js/apparel-payment.js', 'utf8')});
    await page.locator('#start-payment').click();
    await page.waitForFunction(() => !document.getElementById('payment-error').hidden);
    assert.match(await page.locator('#payment-error').textContent(), /Network interrupted/);
    assert.equal(await page.locator('#start-payment').isEnabled(), true);
    await page.locator('#start-payment').click();
    await page.waitForFunction(() => !document.getElementById('submit-payment').hidden);
    assert.equal(await page.locator('#cancel-order').getAttribute('hidden'), '');
    await page.locator('#submit-payment').click();
    await page.waitForFunction(() => !document.getElementById('payment-error').hidden);
    assert.match(await page.locator('#payment-error').textContent(), /declined/);
    assert.equal(await page.locator('#submit-payment').isEnabled(), true);
    await page.locator('#submit-payment').click();
    await page.waitForURL('**#payment-verified');
    const requests = await page.evaluate(() => window.paymentRequests);
    assert.equal(requests.at(-1).url, '/mock-confirm');
    assert.equal(requests.at(-1).body.payment_intent_id, 'pi_test');
    assert.deepEqual(errors, []);
    console.log('PASS: navigation, real cart, mobile width, color images, sizes, stock, price and payment retry/decline/verification UI.');
} finally {
    await browser.close();
}
