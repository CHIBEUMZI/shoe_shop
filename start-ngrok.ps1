param(
    [string]$Domain = "https://mushiness-compacter-sprinkler.ngrok-free.dev"
)

Copy-Item -Force .env.ngrok .env

if (Test-Path public/hot) {
    Remove-Item public/hot -Force
}

$baseUrl = $Domain.TrimEnd('/')
$envFile = Get-Content .env -Raw
$envFile = $envFile -replace '^APP_URL=.*', "APP_URL=$baseUrl"
$envFile = $envFile -replace '^ASSET_URL=.*', "ASSET_URL=$baseUrl"
$envFile = $envFile -replace '^VITE_API_URL=.*', "VITE_API_URL=$baseUrl"
$envFile = $envFile -replace '^SHOP_WEB_BASE_URL=.*', "SHOP_WEB_BASE_URL=$baseUrl"
$envFile = $envFile -replace '^CORS_ALLOWED_ORIGINS=.*', "CORS_ALLOWED_ORIGINS=$baseUrl,http://localhost:5173"
$envFile = $envFile -replace '^VNPAY_RETURN_URL=.*', "VNPAY_RETURN_URL=$baseUrl/api/v1/payments/vnpay/return"
$envFile = $envFile -replace '^VNPAY_IPN_URL=.*', "VNPAY_IPN_URL=$baseUrl/api/v1/payments/vnpay/ipn"
$envFile = $envFile -replace '^VNPAY_FRONTEND_SUCCESS_URL=.*', "VNPAY_FRONTEND_SUCCESS_URL=$baseUrl/shop/orders/success"
$envFile = $envFile -replace '^VNPAY_FRONTEND_FAIL_URL=.*', "VNPAY_FRONTEND_FAIL_URL=$baseUrl/shop/checkout"
$envFile = $envFile -replace '^MOMO_REDIRECT_URL=.*', "MOMO_REDIRECT_URL=$baseUrl/api/v1/payments/momo/return"
$envFile = $envFile -replace '^MOMO_IPN_URL=.*', "MOMO_IPN_URL=$baseUrl/api/v1/payments/momo/ipn"
$envFile = $envFile -replace '^MOMO_FRONTEND_SUCCESS_URL=.*', "MOMO_FRONTEND_SUCCESS_URL=$baseUrl/shop/orders/success"
$envFile = $envFile -replace '^MOMO_FRONTEND_FAIL_URL=.*', "MOMO_FRONTEND_FAIL_URL=$baseUrl/shop/products"
Set-Content .env $envFile

$env:APP_URL = $baseUrl
$env:ASSET_URL = $baseUrl
$env:VITE_API_URL = $baseUrl
$env:SHOP_WEB_BASE_URL = $baseUrl
$env:CORS_ALLOWED_ORIGINS = "$baseUrl,http://localhost:5173"

docker compose up -d

docker compose exec app php artisan optimize:clear

docker compose exec app php artisan config:clear

Write-Host "Ngrok environment is ready."
Write-Host "Run: ngrok http 8080"
Write-Host "Run Vite in another terminal: npm run build"
