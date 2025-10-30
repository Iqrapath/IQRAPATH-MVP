<?php
/**
 * Apache PHP SSL Configuration Check
 * Access via: http://localhost:8000/verify-apache-ssl.php
 */

header('Content-Type: text/plain');

echo "========================================\n";
echo "   Apache PHP SSL Configuration\n";
echo "========================================\n\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server API: " . php_sapi_name() . "\n\n";

echo "SSL Certificate Configuration:\n";
echo "------------------------------\n";

$curlCA = ini_get('curl.cainfo');
$opensslCA = ini_get('openssl.cafile');

if ($curlCA && file_exists($curlCA)) {
    echo "✅ cURL CA: " . $curlCA . "\n";
    echo "   Certificate exists: YES\n\n";
} else {
    echo "❌ cURL CA: " . ($curlCA ?: 'Not Set') . "\n";
    echo "   APACHE NOT RESTARTED YET!\n\n";
}

if ($opensslCA && file_exists($opensslCA)) {
    echo "✅ OpenSSL CA: " . $opensslCA . "\n";
    echo "   Certificate exists: YES\n\n";
} else {
    echo "❌ OpenSSL CA: " . ($opensslCA ?: 'Not Set') . "\n";
    echo "   APACHE NOT RESTARTED YET!\n\n";
}

// Test actual SSL connection
echo "Testing Paystack API Connection:\n";
echo "---------------------------------\n";

$ch = curl_init('https://api.paystack.co');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$result = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "❌ SSL Test: FAILED\n";
    echo "   Error: " . $error . "\n\n";
    echo "========================================\n";
    echo "   ACTION REQUIRED\n";
    echo "========================================\n";
    echo "1. Open XAMPP Control Panel\n";
    echo "2. Click STOP for Apache\n";
    echo "3. Wait 3 seconds\n";
    echo "4. Click START for Apache\n";
    echo "5. Refresh this page\n";
} else {
    echo "✅ SSL Test: SUCCESS\n";
    echo "   HTTP Code: " . $httpCode . "\n";
    echo "   SSL certificate is working!\n\n";
    echo "========================================\n";
    echo "   Configuration is READY!\n";
    echo "========================================\n";
    echo "You can now test Paystack payments.\n";
}

echo "\n";
echo "To test Paystack webhook, run:\n";
echo ".\\test-paystack-webhook.ps1\n";
