#!/bin/bash

# Laravel 11 Bootstrap Script for Warehouse Management System
# Designed for Linux/Mac environments, ready for Hostinger deployment
set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
print_error() {
    echo -e "${RED}ERROR: $1${NC}" >&2
}

print_success() {
    echo -e "${GREEN}SUCCESS: $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}WARNING: $1${NC}"
}

print_info() {
    echo -e "${GREEN}INFO: $1${NC}"
}

# Step 1: Prerequisites Check
print_info "Checking prerequisites..."

# Check PHP version
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [[ $PHP_MAJOR -lt 8 ]] || [[ $PHP_MAJOR -eq 8 && $PHP_MINOR -lt 2 ]]; then
    print_error "PHP 8.2 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

print_success "PHP $PHP_VERSION found"

# Check Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed or not in PATH"
    print_error "Please install Composer from https://getcomposer.org"
    exit 1
fi

COMPOSER_VERSION=$(composer --version | head -n1)
print_success "Composer found: $COMPOSER_VERSION"

# Check PDO MySQL extension
if ! php -m | grep -q "pdo_mysql"; then
    print_error "PDO MySQL extension is not installed"
    print_error "This extension is required for database connectivity on Hostinger"
    exit 1
fi

print_success "PDO MySQL extension found"

# Step 2: Create Laravel Project
PROJECT_DIR="warehouse-app"

if [[ -d "$PROJECT_DIR" ]]; then
    print_error "Directory '$PROJECT_DIR' already exists. Please remove it or choose a different name."
    exit 1
fi

print_info "Creating Laravel 11 project in '$PROJECT_DIR'..."
if ! composer create-project laravel/laravel "$PROJECT_DIR" "^11.0" --no-interaction; then
    print_error "Failed to create Laravel project"
    exit 1
fi

cd "$PROJECT_DIR"
print_success "Laravel project created successfully"

# Step 3: Environment Configuration
print_info "Configuring environment..."

# Copy .env.example to .env
if ! cp .env.example .env; then
    print_error "Failed to copy .env.example to .env"
    exit 1
fi

# Configure .env values
sed -i.bak 's/^APP_NAME=.*/APP_NAME="WarehouseApp"/' .env
sed -i.bak 's/^APP_URL=.*/APP_URL=http:\/\/localhost:8000/' .env

# Add locale and timezone settings
if ! grep -q "APP_LOCALE=" .env; then
    echo "APP_LOCALE=ar" >> .env
else
    sed -i.bak 's/^APP_LOCALE=.*/APP_LOCALE=ar/' .env
fi

if ! grep -q "APP_TIMEZONE=" .env; then
    echo "APP_TIMEZONE=Africa/Cairo" >> .env
else
    sed -i.bak 's/^APP_TIMEZONE=.*/APP_TIMEZONE=Africa\/Cairo/' .env
fi

# Database configuration for Hostinger
sed -i.bak 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i.bak 's/^DB_HOST=.*/DB_HOST=localhost/' .env
sed -i.bak 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i.bak 's/^DB_DATABASE=.*/DB_DATABASE=CHANGE_ME/' .env
sed -i.bak 's/^DB_USERNAME=.*/DB_USERNAME=CHANGE_ME/' .env
sed -i.bak 's/^DB_PASSWORD=.*/DB_PASSWORD=CHANGE_ME/' .env

# Add admin PIN hash configuration
if ! grep -q "ADMIN_PIN_HASH=" .env; then
    echo "" >> .env
    echo "# bcrypt of admin PIN (generate later)" >> .env
    echo "ADMIN_PIN_HASH=" >> .env
fi

print_success "Environment file configured"

# Generate application key
print_info "Generating application key..."
if ! php artisan key:generate --no-interaction; then
    print_error "Failed to generate application key"
    exit 1
fi

# Update config/app.php to read from environment
print_info "Updating app configuration..."

# Update locale setting
sed -i.bak "s/'locale' => '[^']*'/'locale' => env('APP_LOCALE', 'ar')/" config/app.php

# Update timezone setting
sed -i.bak "s/'timezone' => '[^']*'/'timezone' => env('APP_TIMEZONE', 'Africa\/Cairo')/" config/app.php

print_success "App configuration updated"

# Step 4: Git initialization
print_info "Initializing Git repository..."

if [[ ! -d ".git" ]]; then
    git init
fi

# Laravel already includes .gitignore, but let's make sure it's there
if [[ ! -f ".gitignore" ]]; then
    print_warning ".gitignore not found, creating basic one..."
    cat > .gitignore << 'EOF'
/vendor
/node_modules
/public/hot
/public/storage
/storage/*.key
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
*.bak
.env.bak
config/*.bak
EOF
else
    # Add backup files to existing .gitignore if not already present
    if ! grep -q "*.bak" .gitignore; then
        echo "*.bak" >> .gitignore
    fi
    if ! grep -q ".env.bak" .gitignore; then
        echo ".env.bak" >> .gitignore
    fi
    if ! grep -q "config/*.bak" .gitignore; then
        echo "config/*.bak" >> .gitignore
    fi
fi

# Initial commit
git add .
git commit -m "chore: bootstrap Laravel 11 (no Docker) + env (ar/Cairo/Hostinger placeholders)"

print_success "Git repository initialized and initial commit created"

# Clean up backup files created by sed
print_info "Cleaning up backup files..."
find . -name "*.bak" -type f -delete 2>/dev/null || true

# Step 5: Final instructions
echo ""
print_success "🎉 Laravel 11 Warehouse Management System has been successfully bootstrapped!"
echo ""
print_info "📋 Next steps for local development:"
echo ""
echo "1. Navigate to the project directory:"
echo "   cd warehouse-app"
echo ""
echo "2. Start the local development server:"
echo "   php artisan serve"
echo "   (Alternatively: php -S localhost:8000 -t public)"
echo ""
echo "3. Generate admin PIN hash (replace 123456 with your desired PIN):"
echo "   php -r 'echo password_hash(\"123456\", PASSWORD_BCRYPT).PHP_EOL;'"
echo "   Then update ADMIN_PIN_HASH in .env with the generated hash"
echo ""
print_info "🚀 For Hostinger deployment:"
echo "1. Update database credentials in .env"
echo "2. Set Document Root to 'public/' directory"
echo "3. Upload files via FTP/Git"
echo ""
print_warning "⚠️  Remember to:"
echo "- Never commit .env to version control"
echo "- Update database credentials before deployment"
echo "- Generate a strong admin PIN hash"
echo ""
print_success "Happy coding! 🚀"
