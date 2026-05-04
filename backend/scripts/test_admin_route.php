<?php
// Test login and admin-only endpoint using built-in server
require_once __DIR__ . '/../bootstrap.php';

$loginUrl = 'http://localhost:8000/api/auth/login.php';
$adminUrl = 'http://localhost:8000/api/admin-only.php';

$loginData = json_encode(['email' => 'admin@campus.edu', 'password' => 'Password123']);

$opts = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $loginData,
        'ignore_errors' => true
    ]
];

$context = stream_context_create($opts);
$res = file_get_contents($loginUrl, false, $context);
echo "LOGIN RESPONSE:\n" . $res . "\n\n";

$json = json_decode($res, true);
if (!isset($json['token'])) {
    echo "Login did not return token.\n";
    exit(1);
}

$token = $json['token'];

$opts2 = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$token}\r\n",
        'ignore_errors' => true
    ]
];

$ctx2 = stream_context_create($opts2);
$res2 = file_get_contents($adminUrl, false, $ctx2);
echo "ADMIN-ENDPOINT RESPONSE:\n" . $res2 . "\n";

exit(0);
