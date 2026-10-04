$ErrorActionPreference='Stop'
$projectRoot=$PSScriptRoot
$php=(Get-Command php).Source
$node=(Get-Command node).Source
if(!(Test-Path "$projectRoot/backend/vendor/autoload.php")){throw 'Run setup-local.ps1 first.'}
$occupied=@(Get-NetTCPConnection -State Listen -LocalPort 18080,5174 -ErrorAction SilentlyContinue)
if($occupied.Count){throw 'Port 18080 or 5174 is already in use. Open the running app or stop its existing service before starting again.'}
$processes=@()
try {
    $processes+=Start-Process $php -ArgumentList @('-S','127.0.0.1:18080','-t','.','../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php') -WorkingDirectory "$projectRoot/backend/public" -WindowStyle Hidden -PassThru
    $processes+=Start-Process $php -ArgumentList @('artisan','queue:work','--tries=3','--timeout=90','--sleep=2') -WorkingDirectory "$projectRoot/backend" -WindowStyle Hidden -PassThru
    $processes+=Start-Process $php -ArgumentList @('artisan','schedule:work') -WorkingDirectory "$projectRoot/backend" -WindowStyle Hidden -PassThru
    $processes+=Start-Process $node -ArgumentList @('node_modules/vite/bin/vite.js','--host','127.0.0.1','--port','5174') -WorkingDirectory "$projectRoot/frontend" -WindowStyle Hidden -PassThru
    Write-Host 'Invoice SaaS running at http://localhost:5174. Ctrl+C stops these services.'
    while($true){Start-Sleep -Seconds 2;foreach($process in $processes){if($process.HasExited){throw "Service $($process.Id) exited. Check backend/storage/logs/laravel.log."}}}
} finally {foreach($process in $processes){if(!$process.HasExited){Stop-Process -Id $process.Id -ErrorAction SilentlyContinue}}}