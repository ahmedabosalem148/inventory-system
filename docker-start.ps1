# Docker Quick Start Script
# يشغل كل حاجة في Docker مرة واحدة

Write-Host "Starting Inventory System with Docker..." -ForegroundColor Cyan
Write-Host ""

# Check if .env exists, if not copy from .env.docker
if (-Not (Test-Path ".env")) {
    Write-Host "Creating .env file from .env.docker..." -ForegroundColor Yellow
    Copy-Item ".env.docker" ".env"
    Write-Host ".env file created!" -ForegroundColor Green
}

# Stop any running containers
Write-Host "Stopping any running containers..." -ForegroundColor Yellow
docker-compose down 2>$null

# Build and start all services
Write-Host "Building and starting all services..." -ForegroundColor Green
docker-compose up -d --build

# Wait for services to be ready
Write-Host ""
Write-Host "Waiting for services to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Show running containers
Write-Host ""
Write-Host "Running containers:" -ForegroundColor Cyan
docker-compose ps

# Show logs
Write-Host ""
Write-Host "Recent logs:" -ForegroundColor Cyan
docker-compose logs --tail=20

Write-Host ""
Write-Host "System is ready!" -ForegroundColor Green
Write-Host ""
Write-Host "Access URLs:" -ForegroundColor Cyan
Write-Host "   Frontend:   http://localhost:5173" -ForegroundColor White
Write-Host "   Backend:    http://localhost:8000" -ForegroundColor White
Write-Host "   phpMyAdmin: http://localhost:8080" -ForegroundColor White
Write-Host "   Nginx:      http://localhost:80" -ForegroundColor White
Write-Host ""
Write-Host "Database Info:" -ForegroundColor Cyan
Write-Host "   Host:     localhost:3306" -ForegroundColor White
Write-Host "   Database: inventory_db" -ForegroundColor White
Write-Host "   Username: inventory_user" -ForegroundColor White
Write-Host "   Password: secret123" -ForegroundColor White
Write-Host ""
Write-Host "Useful Commands:" -ForegroundColor Cyan
Write-Host "   View logs:      docker-compose logs -f" -ForegroundColor White
Write-Host "   Stop all:       docker-compose down" -ForegroundColor White
Write-Host "   Restart:        docker-compose restart" -ForegroundColor White
Write-Host "   Enter backend:  docker exec -it inventory-backend sh" -ForegroundColor White
Write-Host ""
