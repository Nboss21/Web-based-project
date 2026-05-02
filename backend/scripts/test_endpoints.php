<?php
// Lightweight endpoint smoke tester — hits common API endpoints and reports HTTP status
$base = 'http://localhost:8000/api';

$endpoints = [
    ['path'=>'/admin-only.php','method'=>'GET'],

    // auth
    ['path'=>'/auth/login.php','method'=>'POST','body'=>json_encode(['email'=>'admin@campus.edu','password'=>'password'])],
    ['path'=>'/auth/register.php','method'=>'POST','body'=>json_encode(['name'=>'Test','email'=>'test@x.com','password'=>'Password123'])],
    ['path'=>'/auth/reset-request.php','method'=>'POST'],

    // inventory
    ['path'=>'/inventory/list.php','method'=>'GET'],
    ['path'=>'/inventory/create.php','method'=>'POST'],
    ['path'=>'/inventory/update.php','method'=>'POST'],

    // material requests
    ['path'=>'/material-requests/pending.php','method'=>'GET'],
    ['path'=>'/material-requests/process.php','method'=>'POST'],

    // notifications
    ['path'=>'/notifications/list.php','method'=>'GET'],
    ['path'=>'/notifications/read.php','method'=>'GET'],

    // reports
    ['path'=>'/reports/audit-logs.php','method'=>'GET'],
    ['path'=>'/reports/by-category.php','method'=>'GET'],

    // requests
    ['path'=>'/requests/list.php','method'=>'GET'],
    ['path'=>'/requests/create.php','method'=>'POST'],

    // tasks
    ['path'=>'/tasks/my-tasks.php','method'=>'GET'],
    ['path'=>'/tasks/update-status.php','method'=>'POST'],

    // uploads
    ['path'=>'/uploads/view.php','method'=>'GET'],

    // users
    ['path'=>'/users/list.php','method'=>'GET'],
    ['path'=>'/users/technicians.php','method'=>'GET'],
    ['path'=>'/users/me/preferences.php','method'=>'GET'],
];

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

echo "Endpoint smoke test starting against $base\n";
foreach ($endpoints as $e) {
    $url = rtrim($base, '/') . $e['path'];
    $method = $e['method'] ?? 'GET';
    $body = $e['body'] ?? null;
    echo "\n-> {$method} {$e['path']} ... ";
    [$code, $res, $err] = request($method, $url, $body);
    if ($err) {
        echo "ERROR: $err\n";
        continue;
    }
    $snippet = substr(trim($res ?? ''), 0, 300);
    $ok = ($code >=200 && $code < 400) ? 'OK' : 'FAIL';
    echo "{$ok} (HTTP {$code})\n";
    if ($snippet !== '') echo "  Response: " . preg_replace('/\s+/', ' ', $snippet) . "\n";
}

echo "\nSmoke test complete.\n";
