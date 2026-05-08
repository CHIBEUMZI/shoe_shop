Copy-Item -Force .env.local .env

if (Test-Path public/hot) {
    Remove-Item public/hot -Force
}

$envFile = Get-Content .env -Raw
$envFile = $envFile -replace '^APP_URL=.*', 'APP_URL=http://localhost:8080'
$envFile = $envFile -replace '^ASSET_URL=.*', 'ASSET_URL=http://localhost:8080'
$envFile = $envFile -replace '^SHOP_WEB_BASE_URL=.*', 'SHOP_WEB_BASE_URL=http://localhost:8080'
$envFile = $envFile -replace '^CORS_ALLOWED_ORIGINS=.*', 'CORS_ALLOWED_ORIGINS=http://localhost:5173'
Set-Content .env $envFile

docker compose up -d

docker compose exec app php artisan optimize:clear

docker compose exec app php artisan config:clear

Write-Host "Local environment is ready."
Write-Host "Run Vite in another terminal: npm run dev"
