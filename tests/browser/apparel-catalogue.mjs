// Run: node tests/browser/apparel-catalogue.mjs <playwright-core-index.mjs> [base-url]
import assert from 'node:assert/strict';
import {pathToFileURL} from 'node:url';
const {chromium} = await import(pathToFileURL(process.argv[2]).href);
const browser = await chromium.launch({headless: true});
const base = process.argv[3] || 'http://127.0.0.1:8000';
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/apparel', {waitUntil: 'domcontentloaded'});
    await page.locator('#product-filters').waitFor();
    assert.equal(await page.locator('nav[aria-label="Apparel categories"]').getByText('My orders', {exact:true}).count(), 0);
    assert.equal(await page.locator('.store-account').getByText('My orders', {exact:true}).count(), 1);
    assert.equal(await page.locator('.magazine-brand').getAttribute('href'), base);
    assert.match(await page.locator('.apparel-brand img').getAttribute('src'), /apparel-logo\.png$/);
    const cartIcon = page.locator('.cart-link img');
    assert.match(await cartIcon.getAttribute('src'), /shopping-cart-add-01-stroke-rounded\.svg$/);
    assert.ok(await cartIcon.evaluate(image => image.complete && image.naturalWidth > 0));
    const currency = await page.locator('meta[name="apparel-currency"]').getAttribute('content');
    assert.equal(currency, 'USD');
    assert.equal(await page.locator('#product-filters button:visible').count(), 0);
    const response = page.waitForResponse(r => r.url().includes('/apparel?') && r.request().headers()['x-requested-with'] === 'XMLHttpRequest');
    await page.locator('#product-search').fill('no-product-matches-this-search');
    assert.equal((await response).status(), 200);
    await page.getByText('No matching products.', {exact: true}).waitFor();
    assert.equal(await page.locator('#product-results .product-card').count(), 0);
    assert.match(page.url(), /search=no-product-matches-this-search/);
    const clearResponse = page.waitForResponse(r => r.url().includes('/apparel?') && r.request().headers()['x-requested-with'] === 'XMLHttpRequest');
    await page.locator('#product-search').fill('');
    await clearResponse;
    await page.getByText('Products updated.', {exact: true}).waitFor();
    assert.ok(await page.locator('#product-results .product-card').count() > 0);
    const sortResponse = page.waitForResponse(r => r.url().includes('sort=price-asc'));
    await page.locator('#sort').selectOption('price-asc');
    assert.equal((await sortResponse).status(), 200);
    await page.waitForFunction(() => !document.getElementById('product-results').hasAttribute('aria-busy'));
    await page.setViewportSize({width:390,height:844});
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    assert.deepEqual(errors, []);
    await page.goto(base, {waitUntil: 'domcontentloaded'});
    const searchIcon = page.locator('.header-search-toggle img').first();
    await searchIcon.waitFor();
    assert.match(await searchIcon.getAttribute('src'), /ai-search-01-stroke-rounded\.svg$/);
    await page.waitForFunction(() => document.querySelector('.header-search-toggle img')?.naturalWidth > 0);
    console.log('PASS: AJAX search, sorting, USD, mobile layout, and requested cart/search icons');
} finally {
    await browser.close();
}
