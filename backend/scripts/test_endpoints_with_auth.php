<?php
// Authenticate, capture token, then test protected endpoints with Authorization header
$base = 'http://localhost:8000/api';

function request($method, $url, $body=null, $headers=[]){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $headers[] = 'Content-Type: application/json';
    }
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $res, $err];
}

echo "Logging in to obtain token...\n";
$loginUrl = rtrim($base, '/') . '/auth/login.php';
$loginBody = json_encode(['email'=>'admin@campus.edu','password'=>'password']);
[$code, $res, $err] = request('POST', $loginUrl, $loginBody);
if ($err) {
    echo "Login request error: $err\n";
    exit(1);
}
$json = json_decode($res, true);
if (!isset($json['token'])) {
    echo "Login failed or token missing: HTTP $code - $res\n";
    exit(2);
}
$token = $json['token'];
echo "Token obtained (truncated): " . substr($token,0,32) . "...\n\n";

$endpoints = [
    ['/admin-only.php','GET'],
    ['/inventory/list.php','GET'],
    ['/inventory/create.php','POST'],
    ['/material-requests/pending.php','GET'],
    ['/notifications/list.php','GET'],
    ['/reports/audit-logs.php','GET'],
    ['/requests/list.php','GET'],
    ['/tasks/my-tasks.php','GET'],
    ['/users/list.php','GET'],
    ['/users/me/preferences.php','GET'],
];

echo "Testing protected endpoints with Authorization header...\n";
foreach ($endpoints as $e) {
    [$path, $method] = $e;
    $url = rtrim($base, '/') . $path;
    echo "-> {$method} {$path} ... ";
    [$code, $res, $err] = request($method, $url, null, ["Authorization: Bearer {$token}"]);
    if ($err) {
        echo "ERROR: $err\n";
        continue;
    }
    $ok = ($code >=200 && $code < 400) ? 'OK' : 'FAIL';
    echo "{$ok} (HTTP {$code})\n";
    $snippet = trim(substr($res ?? '', 0, 300));
    if ($snippet !== '') echo "  Response: " . preg_replace('/\s+/', ' ', $snippet) . "\n";
}

echo "\nAuthenticated smoke test complete.\n";
