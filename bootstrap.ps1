# Laravel 11 Bootstrap Script for Warehouse Management System
# Designed for Windows PowerShell, ready for Hostinger deployment

# Set strict mode for error handling
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Colors for output
function Write-Error-Custom {
    param([string]$Message)
    Write-Host "ERROR: $Message" -ForegroundColor Red
}

function Write-Success {
    param([string]$Message)
    Write-Host "SUCCESS: $Message" -ForegroundColor Green
}

function Write-Warning-Custom {
    param([string]$Message)
    Write-Host "WARNING: $Message" -ForegroundColor Yellow
}

function Write-Info {
    param([string]$Message)
    Write-Host "INFO: $Message" -ForegroundColor Green
}

# Step 1: Prerequisites Check
Write-Info "Checking prerequisites..."

# Check PHP version
try {
    $phpVersion = php -r "echo PHP_VERSION;" 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }
    
    $versionParts = $phpVersion.Split('.')
    $major = [int]$versionParts[0]
    $minor = [int]$versionParts[1]
    
    if ($major -lt 8 -or ($major -eq 8 -and $minor -lt 2)) {
        Write-Error-Custom "PHP 8.2 or higher is required. Current version: $phpVersion"
        exit 1
    }
    
    Write-Success "PHP $phpVersion found"
} catch {
    Write-Error-Custom "PHP is not installed or not in PATH"
    exit 1
}

# Check Composer
$composerCommand = ""
$composerFound = $false

# Try global composer first
try {
    $null = composer --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $composerCommand = "composer"
        $composerVersion = composer --version | Select-Object -First 1
        $composerFound = $true
    }
} catch {
    # Global composer not found, will try local
}

# If global composer not found, try local composer.phar
if (-not $composerFound -and (Test-Path "composer.phar")) {
    try {
        $composerVersion = php composer.phar --version | Select-Object -First 1
        $composerCommand = "php composer.phar"
        $composerFound = $true
    } catch {
        # Local composer also failed
    }
}

if ($composerFound) {
    Write-Success "Composer found: $composerVersion"
} else {
    Write-Error-Custom "Composer is not installed or not in PATH"
    Write-Error-Custom "Please install Composer from https://getcomposer.org"
    exit 1
}

# Check PDO MySQL extension
try {
    $extensions = php -m 2>$null
    if ($extensions -notcontains "pdo_mysql") {
        Write-Error-Custom "PDO MySQL extension is not installed"
        Write-Error-Custom "This extension is required for database connectivity on Hostinger"
        exit 1
    }
    Write-Success "PDO MySQL extension found"
} catch {
    Write-Error-Custom "Failed to check PHP extensions"
    exit 1
}

# Step 2: Create Laravel Project
$projectDir = "warehouse-app"

if (Test-Path $projectDir) {
    Write-Error-Custom "Directory '$projectDir' already exists. Please remove it or choose a different name."
    exit 1
}

Write-Info "Creating Laravel 11 project in '$projectDir'..."
try {
    # Use the composer command determined earlier with prefer-dist to avoid Git requirement
    if ($composerCommand -eq "composer") {
        composer create-project laravel/laravel $projectDir "^11.0" --prefer-dist --no-interaction
    } else {
        php composer.phar create-project laravel/laravel $projectDir "^11.0" --prefer-dist --no-interaction
    }
    if ($LASTEXITCODE -ne 0) {
        throw "Composer failed"
    }
} catch {
    Write-Error-Custom "Failed to create Laravel project"
    exit 1
}

Set-Location $projectDir
Write-Success "Laravel project created successfully"

# Step 3: Environment Configuration
Write-Info "Configuring environment..."

# Copy .env.example to .env
try {
    Copy-Item ".env.example" ".env"
    Write-Success "Environment file copied"
} catch {
    Write-Error-Custom "Failed to copy .env.example to .env"
    exit 1
}

# Configure .env values
$envContent = Get-Content ".env"
$envContent = $envContent -replace '^APP_NAME=.*', 'APP_NAME="WarehouseApp"'
$envContent = $envContent -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000'

# Add locale and timezone settings
if ($envContent -notmatch "APP_LOCALE=") {
    $envContent += "APP_LOCALE=ar"
} else {
    $envContent = $envContent -replace '^APP_LOCALE=.*', 'APP_LOCALE=ar'
}

if ($envContent -notmatch "APP_TIMEZONE=") {
    $envContent += "APP_TIMEZONE=Africa/Cairo"
} else {
    $envContent = $envContent -replace '^APP_TIMEZONE=.*', 'APP_TIMEZONE=Africa/Cairo'
}

# Database configuration for Hostinger
$envContent = $envContent -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=mysql'
$envContent = $envContent -replace '^DB_HOST=.*', 'DB_HOST=localhost'
$envContent = $envContent -replace '^DB_PORT=.*', 'DB_PORT=3306'
$envContent = $envContent -replace '^DB_DATABASE=.*', 'DB_DATABASE=CHANGE_ME'
$envContent = $envContent -replace '^DB_USERNAME=.*', 'DB_USERNAME=CHANGE_ME'
$envContent = $envContent -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=CHANGE_ME'

# Add admin PIN hash configuration
if ($envContent -notmatch "ADMIN_PIN_HASH=") {
    $envContent += ""
    $envContent += "# bcrypt of admin PIN (generate later)"
    $envContent += "ADMIN_PIN_HASH="
}

Set-Content ".env" $envContent
Write-Success "Environment file configured"

# Generate application key
Write-Info "Generating application key..."
try {
    php artisan key:generate --no-interaction
    if ($LASTEXITCODE -ne 0) {
        throw "Key generation failed"
    }
} catch {
    Write-Error-Custom "Failed to generate application key"
    exit 1
}

# Update config/app.php to read from environment
Write-Info "Updating app configuration..."

$configContent = Get-Content "config/app.php"
$configContent = $configContent -replace "'locale' => '[^']*'", "'locale' => env('APP_LOCALE', 'ar')"
$configContent = $configContent -replace "'timezone' => '[^']*'", "'timezone' => env('APP_TIMEZONE', 'Africa/Cairo')"
Set-Content "config/app.php" $configContent

Write-Success "App configuration updated"

# Step 4: Git initialization
Write-Info "Initializing Git repository..."

# Check if Git is available
$gitAvailable = $false
try {
    $null = git --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $gitAvailable = $true
    }
} catch {
    # Git not available
}

if ($gitAvailable) {
    if (-not (Test-Path ".git")) {
        git init
    }

    # Update .gitignore to include backup files
    $gitignoreContent = Get-Content ".gitignore" -ErrorAction SilentlyContinue
    if ($gitignoreContent -notcontains "*.bak") {
        Add-Content ".gitignore" "*.bak"
    }
    if ($gitignoreContent -notcontains ".env.bak") {
        Add-Content ".gitignore" ".env.bak"
    }
    if ($gitignoreContent -notcontains "config/*.bak") {
        Add-Content ".gitignore" "config/*.bak"
    }

    # Initial commit
    git add .
    git commit -m "chore: bootstrap Laravel 11 (no Docker) + env (ar/Cairo/Hostinger placeholders)"

    Write-Success "Git repository initialized and initial commit created"
} else {
    Write-Warning-Custom "Git is not installed. Skipping Git repository initialization."
    Write-Info "You can manually initialize Git later by running: git init"
}

# Step 5: Final instructions
Write-Host ""
Write-Success "Laravel 11 Warehouse Management System has been successfully bootstrapped!"
Write-Host ""
Write-Info "Next steps for local development:"
Write-Host ""
Write-Host "1. Navigate to the project directory:"
Write-Host "   cd warehouse-app"
Write-Host ""
Write-Host "2. Start the local development server:"
Write-Host "   php artisan serve"
Write-Host "   (Alternatively: php -S localhost:8000 -t public)"
Write-Host ""
Write-Host "3. Generate admin PIN hash (replace 123456 with your desired PIN):"
Write-Host "   php -r 'echo password_hash(`"123456`", PASSWORD_BCRYPT).PHP_EOL;'"
Write-Host "   Then update ADMIN_PIN_HASH in .env with the generated hash"
Write-Host ""
Write-Info "For Hostinger deployment:"
Write-Host "1. Update database credentials in .env"
Write-Host "2. Set Document Root to 'public/' directory"
Write-Host "3. Upload files via FTP/Git"
Write-Host ""
Write-Warning-Custom "Remember to:"
Write-Host "- Never commit .env to version control"
Write-Host "- Update database credentials before deployment"
Write-Host "- Generate a strong admin PIN hash"
Write-Host ""
Write-Success "Happy coding!"
