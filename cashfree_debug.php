<?php
/**
 * Cashfree Credentials Debug Script
 * Run: php cashfree_debug.php
 * Place in Laravel root, run once, then DELETE immediately.
 */

// Load Laravel manually
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CASHFREE CREDENTIAL DEBUG ===\n\n";

// 1. Check .env values
$appId     = config('services.cashfree.app_id');
$secretKey = config('services.cashfree.secret_key');
$sandbox   = config('services.cashfree.sandbox');

echo "APP_ID     : " . ($appId     ? substr($appId, 0, 8).'...(len:'.strlen($appId).')' : '❌ EMPTY') . "\n";
echo "SECRET_KEY : " . ($secretKey ? substr($secretKey, 0, 8).'...(len:'.strlen($secretKey).')' : '❌ EMPTY') . "\n";
echo "SANDBOX    : " . ($sandbox ? 'true (sandbox.cashfree.com)' : 'false (api.cashfree.com)') . "\n\n";

// 2. Check for accidental whitespace or quotes in values
if ($appId) {
    $trimmed = trim($appId);
    if ($trimmed !== $appId) {
        echo "⚠️  APP_ID has leading/trailing whitespace!\n";
    }
    if (str_starts_with($appId, '"') || str_starts_with($appId, "'")) {
        echo "⚠️  APP_ID has quote characters — remove quotes from .env value\n";
    }
}

// 3. Test API call directly
$baseUrl = $sandbox ? 'https://sandbox.cashfree.com/pg' : 'https://api.cashfree.com/pg';
echo "BASE URL   : $baseUrl\n\n";

echo "Testing API call...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'order_id'       => 'debug_test_'.time(),
        'order_amount'   => 1.00,
        'order_currency' => 'INR',
        'customer_details' => [
            'customer_id'    => 'debug_1',
            'customer_name'  => 'Debug User',
            'customer_email' => 'debug@test.com',
            'customer_phone' => '9999999999',
        ],
    ]),
    CURLOPT_HTTPHEADER => [
        'x-client-id: '     . $appId,
        'x-client-secret: ' . $secretKey,
        'x-api-version: 2023-08-01',
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response   : $response\n\n";

if ($httpCode === 200) {
    echo "✅ Credentials are VALID. API call successful.\n";
} elseif ($httpCode === 401) {
    echo "❌ 401 Authentication Failed.\n";
    echo "Possible causes:\n";
    echo "  1. Using LIVE keys with CASHFREE_SANDBOX=true (or vice versa)\n";
    echo "  2. Copied keys have extra spaces or newlines in .env\n";
    echo "  3. Keys are from wrong Cashfree account\n";
    echo "  4. Laravel config cache has stale values — run: php artisan config:clear\n";
} else {
    echo "Unexpected status: $httpCode\n";
}