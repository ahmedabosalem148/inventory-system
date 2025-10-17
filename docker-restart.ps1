# Docker Restart Script
# يعيد تشغيل كل الـ services

param(
    [string]$Service = ""
)

if ($Service) {
    Write-Host "Restarting $Service..." -ForegroundColor Yellow
    docker-compose restart $Service
    Write-Host "$Service restarted!" -ForegroundColor Green
} else {
    Write-Host "Restarting all services..." -ForegroundColor Yellow
    docker-compose restart
    Write-Host "All services restarted!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Container Status:" -ForegroundColor Cyan
docker-compose ps
Write-Host ""
