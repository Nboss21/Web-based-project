# enable_pdo_pgsql.ps1
# Enables pdo_pgsql and pgsql extensions in XAMPP php.ini (Windows).
# Run this script as Administrator.

$phpIniPath = 'C:\xampp\php\php.ini'
if (-not (Test-Path $phpIniPath)) {
    Write-Error "php.ini not found at $phpIniPath. Adjust the path and re-run the script."
    exit 2
}

$backup = "$phpIniPath.bak.$((Get-Date).ToString('yyyyMMddHHmmss'))"
Copy-Item -Path $phpIniPath -Destination $backup -Force
Write-Output "Backed up php.ini to $backup"

$content = Get-Content -Raw -Path $phpIniPath

# Uncomment extension lines for pdo_pgsql and pgsql
$content = $content -replace '^[\s;]*extension\s*=\s*pdo_pgsql', 'extension=pdo_pgsql' -replace '^[\s;]*extension\s*=\s*pgsql', 'extension=pgsql'

# If the php.ini uses Windows DLL-style extensions, ensure those are present/uncommented
if ($content -notmatch 'pdo_pgsql' -and $content -notmatch 'php_pdo_pgsql') {
    $content += "`r`nextension=php_pdo_pgsql.dll"
}
if ($content -notmatch 'pgsql' -and $content -notmatch 'php_pgsql') {
    $content += "`r`nextension=php_pgsql.dll"
}

Set-Content -Path $phpIniPath -Value $content -Encoding UTF8
Write-Output 'Updated php.ini — uncommented pdo_pgsql and pgsql lines (if present).'

$next = @'
Next steps:
1) Restart Apache/XAMPP using the XAMPP Control Panel (stop and start Apache).
2) Verify on CLI:
    C:\xampp\php\php.exe -m | findstr pgsql
    C:\xampp\php\php.exe -r "echo extension_loaded('pdo_pgsql') ? 'ok' : 'missing';"
3) If the previous command prints 'ok', run the DB test script:
    C:\xampp\php\php.exe D:\web_project_full\backend\scripts\test_neon_connect.php
'@

Write-Output $next
