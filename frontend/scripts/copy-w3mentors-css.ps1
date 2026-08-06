# Copy W3Mentors legacy CSS into React public folder (run after clone or when SCSS is rebuilt)

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$srcCss = Join-Path $root "application\views\css"
$dstCss = Join-Path $root "frontend\public\w3mentors\css"
$srcImages = Join-Path $root "public\images"
$dstImages = Join-Path $root "frontend\public\images"

New-Item -ItemType Directory -Force -Path "$dstCss\themes\onlinetutoring" | Out-Null
New-Item -ItemType Directory -Force -Path $dstImages | Out-Null

Copy-Item "$srcCss\common.css" "$dstCss\" -Force
Copy-Item "$srcCss\themes\onlinetutoring\frontend.css" "$dstCss\themes\onlinetutoring\" -Force
$dashCss = Join-Path $root "dashboard\views\css"
if (Test-Path $dashCss) {
  Copy-Item "$dashCss\dashboard.css" "$dstCss\" -Force
  Copy-Item "$dashCss\common.css" "$dstCss\dashboard-common.css" -Force
  Copy-Item "$dashCss\course-personal.css" "$dstCss\" -Force
}
Copy-Item -Recurse -Force "$srcImages\*" "$dstImages\"

Write-Host "W3Mentors CSS + images copied to frontend/public" -ForegroundColor Green
