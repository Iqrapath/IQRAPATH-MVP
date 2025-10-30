<?php
header('Content-Type: text/plain');

echo "Apache PHP SSL Configuration:\n";
echo "================================\n\n";

echo "cURL CA Info: " . (ini_get('curl.cainfo') ?: 'NOT SET') . "\n";
echo "OpenSSL CA File: " . (ini_get('openssl.cafile') ?: 'NOT SET') . "\n\n";

echo "Certificate File Exists:\n";
$curlCa = ini_get('curl.cainfo');
if ($curlCa) {
    echo "  cURL CA: " . (file_exists($curlCa) ? 'YES ✓' : 'NO ✗') . "\n";
}

$opensslCa = ini_get('openssl.cafile');
if ($opensslCa) {
    echo "  OpenSSL CA: " . (file_exists($opensslCa) ? 'YES ✓' : 'NO ✗') . "\n";
}

echo "\nPHP Version: " . PHP_VERSION . "\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
