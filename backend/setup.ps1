$ErrorActionPreference = "Stop"

Write-Host "Setting up W3Mentors Laravel API..." -ForegroundColor Cyan

$dirs = @(
    "bootstrap\cache",
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)

foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created $dir"
    }
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Install PHP 8.2+ and Composer, then run this script again."
    exit 1
}

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example"
}

if (-not (Test-Path "vendor")) {
    composer install
}

php artisan key:generate --force
php artisan migrate --force

Write-Host ""
Write-Host "Done. Start the API with: php artisan serve" -ForegroundColor Green
