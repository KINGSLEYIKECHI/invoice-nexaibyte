$ErrorActionPreference='Stop'
Push-Location $PSScriptRoot
try {
    Push-Location backend
    try {
        composer install --no-interaction
        if($LASTEXITCODE -ne 0){throw 'Composer install failed.'}
        if(!(Test-Path .env)) {
            $text=Get-Content .env.example -Raw
            $text=$text.Replace('APP_URL=http://localhost:8000','APP_URL=http://localhost:18080').Replace('FRONTEND_URL=http://localhost:5173','FRONTEND_URL=http://localhost:5174').Replace('DB_CONNECTION=mysql','DB_CONNECTION=sqlite').Replace('DB_DATABASE=invoices','DB_DATABASE=database/database.sqlite').Replace('QUEUE_CONNECTION=redis','QUEUE_CONNECTION=database').Replace('CACHE_STORE=redis','CACHE_STORE=file').Replace('MAIL_MAILER=smtp','MAIL_MAILER=log')
            [IO.File]::WriteAllText((Join-Path (Get-Location) '.env'),$text)
        }
        if(!(Test-Path database/database.sqlite)){New-Item -ItemType File database/database.sqlite | Out-Null}
        if((Get-Content .env -Raw) -match '(?m)^APP_KEY=\s*$'){php artisan key:generate}
        php artisan migrate --seed
        if($LASTEXITCODE -ne 0){throw 'Migration failed.'}
        if(!(Test-Path public/storage)){php artisan storage:link}
    } finally {Pop-Location}
    if(!(Test-Path frontend/.env)){[IO.File]::WriteAllText((Join-Path (Get-Location) 'frontend/.env'),'VITE_API_URL=http://localhost:18080/api')}
    Push-Location frontend
    try {npm.cmd ci;if($LASTEXITCODE -ne 0){throw 'npm install failed.'}} finally {Pop-Location}
    Write-Host 'Setup complete. Run .\start-local.ps1, then open http://localhost:5174.'
} finally {Pop-Location}