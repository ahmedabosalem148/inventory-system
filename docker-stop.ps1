# Docker Stop Script
# يوقف كل الـ containers

Write-Host "Stopping all containers..." -ForegroundColor Yellow

docker-compose down

Write-Host ""
Write-Host "All containers stopped!" -ForegroundColor Green
Write-Host ""
Write-Host "To remove volumes (delete all data):" -ForegroundColor Cyan
Write-Host "   docker-compose down -v" -ForegroundColor White
Write-Host ""
