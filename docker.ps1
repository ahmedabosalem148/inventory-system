# Docker Helper Script for Windows
# استخدام: .\docker.ps1 <command>

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

function Show-Help {
    Write-Host ""
    Write-Host "🐳 Docker Helper Commands:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  init              - Initialize project (first time)" -ForegroundColor Green
    Write-Host "  up                - Start all containers" -ForegroundColor Green
    Write-Host "  down              - Stop all containers" -ForegroundColor Yellow
    Write-Host "  restart           - Restart all containers" -ForegroundColor Yellow
    Write-Host "  build             - Build all containers" -ForegroundColor Blue
    Write-Host "  logs              - Show all logs" -ForegroundColor White
    Write-Host "  logs-backend      - Show backend logs" -ForegroundColor White
    Write-Host "  logs-frontend     - Show frontend logs" -ForegroundColor White
    Write-Host "  shell-backend     - Access backend shell" -ForegroundColor Magenta
    Write-Host "  shell-frontend    - Access frontend shell" -ForegroundColor Magenta
    Write-Host "  db-migrate        - Run migrations" -ForegroundColor Cyan
    Write-Host "  db-seed           - Run seeders" -ForegroundColor Cyan
    Write-Host "  db-fresh          - Fresh migration + seed" -ForegroundColor Cyan
    Write-Host "  cache-clear       - Clear all caches" -ForegroundColor Yellow
    Write-Host "  clean             - Remove everything" -ForegroundColor Red
    Write-Host ""
}

function Initialize-Project {
    Write-Host "🚀 Initializing project..." -ForegroundColor Cyan
    
    # Copy environment file
    Copy-Item .env.docker .env -Force
    Write-Host "✅ Environment file copied" -ForegroundColor Green
    
    # Build containers
    Write-Host "🔨 Building containers..." -ForegroundColor Yellow
    docker-compose build
    
    # Start containers
    Write-Host "🚀 Starting containers..." -ForegroundColor Yellow
    docker-compose up -d
    
    # Wait for MySQL
    Write-Host "⏳ Waiting for MySQL to be ready (10 seconds)..." -ForegroundColor Yellow
    Start-Sleep -Seconds 10
    
    # Install dependencies
    Write-Host "📦 Installing backend dependencies..." -ForegroundColor Yellow
    docker-compose exec backend composer install
    
    Write-Host "📦 Installing frontend dependencies..." -ForegroundColor Yellow
    docker-compose exec frontend npm install
    
    # Generate key
    Write-Host "🔑 Generating application key..." -ForegroundColor Yellow
    docker-compose exec backend php artisan key:generate
    
    # Run migrations
    Write-Host "🗄️  Running migrations and seeders..." -ForegroundColor Yellow
    docker-compose exec backend php artisan migrate:fresh --seed
    
    # Link storage
    Write-Host "🔗 Linking storage..." -ForegroundColor Yellow
    docker-compose exec backend php artisan storage:link
    
    Write-Host ""
    Write-Host "✅ Project initialized successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🌐 Access points:" -ForegroundColor Cyan
    Write-Host "   Frontend:    http://localhost:5173" -ForegroundColor White
    Write-Host "   Backend:     http://localhost:8000" -ForegroundColor White
    Write-Host "   phpMyAdmin:  http://localhost:8080" -ForegroundColor White
    Write-Host ""
}

switch ($Command.ToLower()) {
    "help" {
        Show-Help
    }
    "init" {
        Initialize-Project
    }
    "up" {
        Write-Host "🚀 Starting containers..." -ForegroundColor Green
        docker-compose up -d
    }
    "down" {
        Write-Host "🛑 Stopping containers..." -ForegroundColor Yellow
        docker-compose down
    }
    "restart" {
        Write-Host "🔄 Restarting containers..." -ForegroundColor Yellow
        docker-compose restart
    }
    "build" {
        Write-Host "🔨 Building containers..." -ForegroundColor Blue
        docker-compose build
    }
    "logs" {
        docker-compose logs -f
    }
    "logs-backend" {
        docker-compose logs -f backend
    }
    "logs-frontend" {
        docker-compose logs -f frontend
    }
    "shell-backend" {
        docker-compose exec backend sh
    }
    "shell-frontend" {
        docker-compose exec frontend sh
    }
    "db-migrate" {
        Write-Host "🗄️  Running migrations..." -ForegroundColor Cyan
        docker-compose exec backend php artisan migrate
    }
    "db-seed" {
        Write-Host "🌱 Running seeders..." -ForegroundColor Cyan
        docker-compose exec backend php artisan db:seed
    }
    "db-fresh" {
        Write-Host "🗄️  Fresh migration with seed..." -ForegroundColor Cyan
        docker-compose exec backend php artisan migrate:fresh --seed
    }
    "cache-clear" {
        Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
        docker-compose exec backend php artisan cache:clear
        docker-compose exec backend php artisan config:clear
        docker-compose exec backend php artisan route:clear
        docker-compose exec backend php artisan view:clear
    }
    "clean" {
        Write-Host "🗑️  Removing all containers and volumes..." -ForegroundColor Red
        docker-compose down -v
        docker system prune -f
    }
    default {
        Write-Host "❌ Unknown command: $Command" -ForegroundColor Red
        Show-Help
    }
}
