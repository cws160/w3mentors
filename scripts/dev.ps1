# Start Laravel API + Vite frontend (run from repo root)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot

Write-Host "Starting Laravel API on http://127.0.0.1:8000 ..."
$api = Start-Process -FilePath "php" -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8000" -WorkingDirectory (Join-Path $root "backend") -PassThru -WindowStyle Normal

Start-Sleep -Seconds 2

Write-Host "Starting Vite on http://localhost:5173 ..."
Push-Location (Join-Path $root "frontend")
try {
  npm run dev
} finally {
  Pop-Location
  if (-not $api.HasExited) {
    Stop-Process -Id $api.Id -Force -ErrorAction SilentlyContinue
  }
}
