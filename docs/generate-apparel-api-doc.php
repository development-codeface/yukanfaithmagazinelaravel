<?php
// Generate a dependency-free Word document from the current API response shapes.
$sections = [];
$base = 'http://localhost:8000/api/apparel';
$name = 'Made For More Black Mug – 2 Corinthians 4:18 Inspirational Coffee Cup (11oz/15oz)';
$sku = '42-161-HO1YE-5PFpe-8bJkp-wk2GV-uQ848-zrZwk-l7Sqm-LJsdc-UM3QW-8U49s';
$date = '2026-10-30T06:00:00.000000Z';
$cart = function ($quantity) use ($name) {
    return ['data' => ['items' => $quantity ? [['product_id' => 1, 'variant_id' => 3, 'quantity' => $quantity, 'key' => '1:3', 'available' => true, 'name' => $name, 'price' => 11.99, 'subtotal' => round(11.99 * $quantity, 2)]] : [], 'count' => $quantity, 'total' => round(11.99 * $quantity, 2), 'currency' => 'inr', 'can_checkout' => $quantity > 0]];
};
$order = ['id' => 101, 'number' => 'cc54aec6-47f7-4d22-a966-264730f19a21', 'status' => 'pending', 'payment_status' => 'pending', 'payment_intent_id' => null, 'total' => '23.98', 'currency' => 'INR', 'paid_at' => null, 'name' => 'Sample Customer', 'email' => 'customer@example.com', 'phone' => '+919876543210', 'address' => '12 Sample Street, Chennai, Tamil Nadu 600001, India', 'notes' => null, 'tracking_number' => null, 'items' => [['id' => 201, 'apparel_order_id' => 101, 'product_id' => 1, 'variant_id' => 3, 'name' => $name, 'options' => 'Black', 'sku' => $sku, 'price' => '11.99', 'quantity' => 2, 'created_at' => $date, 'updated_at' => $date]], 'created_at' => $date];
function example($title, $method, $path, $body, $response, $status = 200, $note = '') {
    global $sections, $base;
    $sections[] = compact('title', 'method', 'path', 'body', 'response', 'status', 'note');
}
example('1. View cart', 'GET', '/cart', null, $cart(2));
example('2. Clear cart', 'DELETE', '/cart', null, $cart(0));
example('3. Add an item', 'POST', '/cart/items', ['product_id' => 1, 'variant_id' => 3, 'quantity' => 2], $cart(2), 200, 'Example assumes an empty cart. Adding the same product and variant again increases its quantity.');
example('4. Update item quantity', 'PATCH', '/cart/items/1:3', ['quantity' => 1], $cart(1), 200, 'Use the key returned in the cart response. Quantity replaces the existing quantity.');
example('5. Remove an item', 'DELETE', '/cart/items/1:3', null, $cart(0), 200, 'Example assumes this is the only item in the cart.');
$url = $base.'/orders';
example('6. List orders', 'GET', '/orders?per_page=1&page=1', null, ['data' => [$order], 'links' => ['first' => $url.'?page=1', 'last' => $url.'?page=1', 'prev' => null, 'next' => null], 'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'links' => [['url' => null, 'label' => '&laquo; Previous', 'active' => false], ['url' => $url.'?page=1', 'label' => '1', 'active' => true], ['url' => null, 'label' => 'Next &raquo;', 'active' => false]], 'path' => $url, 'per_page' => 1, 'to' => 1, 'total' => 1]], 200, 'Shows only the logged-in user’s orders. Pagination links are illustrative; their exact labels and query strings can vary.');
example('7. Create an order', 'POST', '/orders', ['checkout_token' => '6b02d4bb-5f69-43bd-bc1d-0b018230f149', 'name' => $order['name'], 'email' => $order['email'], 'phone' => $order['phone'], 'address' => $order['address'], 'notes' => null], ['data' => $order], 201, 'Requires a non-empty cart. This example checks out two mugs. Generate a new UUID checkout_token for each new checkout; reuse it when retrying the same checkout. Creating an order clears the cart.');
example('8. View an order', 'GET', '/orders/101', null, ['data' => $order], 200, '101 is an example numeric order ID. Use the id returned when creating your order.');
$cancelled = $order;
$cancelled['status'] = 'cancelled';
example('9. Cancel an order', 'POST', '/orders/101/cancel', null, ['data' => $cancelled], 200, 'Alternative to payment: cancellation requires a pending, unpaid API order with no payment intent. An already cancelled API order also returns successfully.');
example('10. Start payment', 'POST', '/orders/101/payment-intent', null, ['requires_payment' => true, 'order_id' => 101, 'publishable_key' => 'pk_test_EXAMPLE', 'publishableKey' => 'pk_test_EXAMPLE', 'client_secret' => 'pi_EXAMPLE_secret_EXAMPLE', 'paymentIntent' => 'pi_EXAMPLE_secret_EXAMPLE', 'payment_intent_id' => 'pi_EXAMPLE', 'paymentIntentId' => 'pi_EXAMPLE', 'customer' => 'cus_EXAMPLE', 'ephemeralKey' => 'ek_test_EXAMPLE', 'amount' => 2398, 'currency' => 'inr'], 200, 'Requires a pending, unpaid API order and configured Stripe credentials. Stripe amount uses the smallest currency unit: 2398 means INR 23.98. Use the returned secrets in the Stripe SDK to complete payment.');
$paid = $order;
$paid['status'] = 'processing';
$paid['payment_status'] = 'paid';
$paid['payment_intent_id'] = 'pi_EXAMPLE';
$paid['paid_at'] = '2026-10-30T06:05:00.000000Z';
example('11. Confirm payment', 'POST', '/orders/101/confirm-payment', ['payment_intent_id' => 'pi_EXAMPLE'], ['data' => $paid], 200, 'Call after Stripe payment succeeds. Pass the payment_intent_id returned by the start-payment API. The backend verifies payment with Stripe.');

function esc($text) { return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function para($text, $style = '') {
    $props = $style ? '<w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr>' : '';
    return '<w:p>'.$props.'<w:r><w:t xml:space="preserve">'.esc($text).'</w:t></w:r></w:p>';
}
function codeblock($text) {
    $xml = '';
    foreach (explode("\n", $text) as $line) $xml .= para($line, 'Code');
    return $xml;
}
function jsonExample($data) { return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
$xml = para('Apparel Cart & Orders API', 'Title');
$xml .= para('Sample requests and responses', 'Subtitle');
$xml .= para('Base URL: '.$base);
$xml .= para('Headers for every request:', 'Heading2');
$xml .= codeblock("Accept: application/json\nAuthorization: Bearer YOUR_API_TOKEN\nContent-Type: application/json");
$xml .= para('Use your login API token. JSON bodies are shown only where required.');
$xml .= para('Responses below are illustrative examples based on the backend code, not executed checkout or payment results. Product 1 and active variant 3 exist in the local database. Order IDs, customer details, timestamps, Stripe values and INR currency are examples; replace them with your actual values.');
$xml .= para('Cart item key: product_id:variant_id (example: 1:3). For a product without variants, the key ends in :0. Order URLs use numeric order IDs.');
foreach ($sections as $section) {
    $xml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    $xml .= para($section['title'], 'Heading1');
    if ($section['note']) $xml .= para($section['note']);
    $xml .= para('Request', 'Heading2');
    $xml .= codeblock($section['method'].' '.$base.$section['path']);
    $xml .= $section['body'] === null ? para('Body: none.') : codeblock(jsonExample($section['body']));
    $xml .= para('Response: '.$section['status'].' '.($section['status'] === 201 ? 'Created' : 'OK'), 'Heading2');
    $xml .= codeblock(jsonExample($section['response']));
}
$xml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'.para('Common error responses', 'Heading1');
$xml .= para('401 — Missing or invalid token', 'Heading2').codeblock(jsonExample(['message' => 'Unauthenticated.']));
$xml .= para('422 — Creating an order with an empty cart', 'Heading2').codeblock(jsonExample(['message' => 'Your cart is empty.', 'errors' => ['cart' => ['Your cart is empty.']]]));
$xml .= para('422 — Unavailable item, missing required variant, or insufficient stock', 'Heading2').codeblock(jsonExample(['message' => 'An item is unavailable, requires a variant, or has insufficient stock. Update your cart.', 'errors' => ['cart' => ['An item is unavailable, requires a variant, or has insufficient stock. Update your cart.']]]));
$xml .= para('502 — Stripe payment could not start', 'Heading2').codeblock(jsonExample(['message' => 'Unable to start Stripe payment. Retry this order.']));
$xml .= para('502 — Stripe payment could not be verified', 'Heading2').codeblock(jsonExample(['message' => 'Unable to verify Stripe payment.']));
$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$xml.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="850" w:right="850" w:bottom="850" w:left="850"/></w:sectPr></w:body></w:document>';
$styles = '<?xml version="1.0" encoding="UTF-8"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="100"/></w:pPr></w:pPrDefault></w:docDefaults>';
foreach (['Title' => 36, 'Subtitle' => 26, 'Heading1' => 30, 'Heading2' => 24, 'Code' => 17] as $id => $size) {
    $styles .= '<w:style w:type="paragraph" w:styleId="'.$id.'"><w:name w:val="'.$id.'"/><w:pPr><w:spacing w:after="'.($id === 'Code' ? '0' : '140').'"/>'.($id !== 'Code' ? '<w:keepNext/>' : '').'</w:pPr><w:rPr><w:sz w:val="'.$size.'"/>'.($id === 'Code' ? '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/>' : '<w:b/><w:color w:val="17365D"/>').'</w:rPr></w:style>';
}
$styles .= '</w:styles>';
$path = __DIR__.'/apparel-cart-orders-api.docx';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create Word document');
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
$zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
$zip->addFromString('word/document.xml', $document);
$zip->addFromString('word/styles.xml', $styles);
$zip->close();
$check = new ZipArchive();
$check->open($path);
for ($i = 0; $i < $check->numFiles; $i++) {
    if (simplexml_load_string($check->getFromIndex($i)) === false) throw new RuntimeException('Invalid XML in document');
}
$check->close();
echo $path.PHP_EOL;
