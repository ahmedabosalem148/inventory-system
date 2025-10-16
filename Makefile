.PHONY: help build up down restart logs shell-backend shell-frontend db-migrate db-seed db-fresh clean

help: ## Show this help
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build all containers
	docker-compose build

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

logs: ## Show logs
	docker-compose logs -f

logs-backend: ## Show backend logs
	docker-compose logs -f backend

logs-frontend: ## Show frontend logs
	docker-compose logs -f frontend

shell-backend: ## Access backend shell
	docker-compose exec backend sh

shell-frontend: ## Access frontend shell
	docker-compose exec frontend sh

shell-mysql: ## Access MySQL shell
	docker-compose exec mysql mysql -u inventory_user -psecret123 inventory_db

db-migrate: ## Run migrations
	docker-compose exec backend php artisan migrate

db-seed: ## Run seeders
	docker-compose exec backend php artisan db:seed

db-fresh: ## Fresh migration with seed
	docker-compose exec backend php artisan migrate:fresh --seed

cache-clear: ## Clear all caches
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan route:clear
	docker-compose exec backend php artisan view:clear

npm-install: ## Install frontend dependencies
	docker-compose exec frontend npm install

composer-install: ## Install backend dependencies
	docker-compose exec backend composer install

clean: ## Remove all containers and volumes
	docker-compose down -v
	docker system prune -f

init: ## Initialize project (first time setup)
	@echo "🚀 Initializing project..."
	cp .env.docker .env
	docker-compose build
	docker-compose up -d
	@echo "⏳ Waiting for MySQL to be ready..."
	sleep 10
	docker-compose exec backend composer install
	docker-compose exec backend php artisan key:generate
	docker-compose exec backend php artisan migrate:fresh --seed
	docker-compose exec backend php artisan storage:link
	@echo "✅ Project initialized successfully!"
	@echo "🌐 Frontend: http://localhost:5173"
	@echo "🔧 Backend: http://localhost:8000"
	@echo "🗄️  phpMyAdmin: http://localhost:8080"
