#!/usr/bin/env pwsh
# PowerShell script for PHP DDD Learning Portal
# Provides easy commands to run operations inside Docker containers

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

function Show-Help {
    Write-Host "PHP DDD Learning Portal - Available Commands:" -ForegroundColor Green
    Write-Host ""
    Write-Host "🐳 Docker Management:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 up          - Start all containers"
    Write-Host "  .\scripts\dev.ps1 down        - Stop all containers"
    Write-Host "  .\scripts\dev.ps1 restart     - Restart all containers"
    Write-Host "  .\scripts\dev.ps1 logs        - Show container logs"
    Write-Host "  .\scripts\dev.ps1 shell       - Open shell in app container"
    Write-Host ""
    Write-Host "🗄️  Database Operations:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 migrate     - Run all database migrations"
    Write-Host "  .\scripts\dev.ps1 setup-db    - Setup database (create tables)"
    Write-Host "  .\scripts\dev.ps1 seed        - Seed database with sample data"
    Write-Host "  .\scripts\dev.ps1 db-shell    - Open MySQL shell"
    Write-Host "  .\scripts\dev.ps1 db-status   - Check database connection"
    Write-Host ""
    Write-Host "👤 User Management:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 admin       - Create default admin user"
    Write-Host "  .\scripts\dev.ps1 admin-custom - Create custom admin user"
    Write-Host "  .\scripts\dev.ps1 show-creds  - Show admin credentials"
    Write-Host "  .\scripts\dev.ps1 create-user - Create a new user"
    Write-Host ""
    Write-Host "🧪 Testing:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 test        - Run all tests"
    Write-Host "  .\scripts\dev.ps1 test-unit   - Run unit tests only"
    Write-Host "  .\scripts\dev.ps1 test-integration - Run integration tests only"
    Write-Host "  .\scripts\dev.ps1 test-feature - Run feature tests only"
    Write-Host "  .\scripts\dev.ps1 test-coverage - Run tests with coverage"
    Write-Host ""
    Write-Host "🔧 Development:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 install     - Install PHP dependencies"
    Write-Host "  .\scripts\dev.ps1 update      - Update PHP dependencies"
    Write-Host "  .\scripts\dev.ps1 cs-check    - Check code style"
    Write-Host "  .\scripts\dev.ps1 cs-fix      - Fix code style"
    Write-Host "  .\scripts\dev.ps1 stan        - Run static analysis"
    Write-Host ""
    Write-Host "📊 Learning Portal:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 test-portal - Test learning portal functionality"
    Write-Host "  .\scripts\dev.ps1 test-routing - Test routing system"
    Write-Host "  .\scripts\dev.ps1 test-auth   - Test authentication system"
    Write-Host ""
    Write-Host "🛠️  Utilities:" -ForegroundColor Yellow
    Write-Host "  .\scripts\dev.ps1 clean       - Clean up temporary files"
    Write-Host "  .\scripts\dev.ps1 status      - Show project status"
    Write-Host "  .\scripts\dev.ps1 backup      - Create database backup"
    Write-Host "  .\scripts\dev.ps1 setup       - Complete project setup"
}

# Main command dispatcher
switch ($Command.ToLower()) {
    "help" { 
        Show-Help 
    }
    "up" { 
        Write-Host "🚀 Starting Docker containers..." -ForegroundColor Green
        docker-compose up -d
        Write-Host "✅ Containers started successfully!" -ForegroundColor Green
    }
    "down" { 
        Write-Host "🛑 Stopping Docker containers..." -ForegroundColor Green
        docker-compose down
        Write-Host "✅ Containers stopped successfully!" -ForegroundColor Green
    }
    "restart" { 
        Write-Host "🔄 Restarting Docker containers..." -ForegroundColor Green
        docker-compose restart
        Write-Host "✅ Containers restarted successfully!" -ForegroundColor Green
    }
    "logs" { 
        Write-Host "📋 Showing container logs..." -ForegroundColor Green
        docker-compose logs -f
    }
    "shell" { 
        Write-Host "🐚 Opening shell in app container..." -ForegroundColor Green
        docker exec -it ddd-app bash
    }
    "migrate" { 
        Write-Host "🗄️  Running database migrations..." -ForegroundColor Green
        docker exec ddd-app php scripts/run-migrations.php
        Write-Host "✅ Migrations completed!" -ForegroundColor Green
    }
    "setup-db" { 
        Write-Host "🗄️  Setting up database..." -ForegroundColor Green
        docker exec ddd-app php scripts/setup-database.php
        Write-Host "✅ Database setup completed!" -ForegroundColor Green
    }
    "seed" { 
        Write-Host "🌱 Seeding database with sample data..." -ForegroundColor Green
        docker exec ddd-app php scripts/seed-learning-data.php
        Write-Host "✅ Database seeded successfully!" -ForegroundColor Green
    }
    "db-shell" { 
        Write-Host "🐚 Opening MySQL shell..." -ForegroundColor Green
        docker exec -it ddd-db mysql -u ddd_user -psecret ddd_db
    }
    "db-status" { 
        Write-Host "📊 Checking database connection..." -ForegroundColor Green
        docker exec ddd-app php scripts/test-database-connection.php
    }
    "admin" { 
        Write-Host "👤 Creating default admin user..." -ForegroundColor Green
        docker exec ddd-app php scripts/create-default-admin.php
    }
    "admin-custom" { 
        Write-Host "👤 Creating custom admin user..." -ForegroundColor Green
        docker exec ddd-app php scripts/create-admin-user.php
    }
    "show-creds" { 
        Write-Host "🔑 Showing admin credentials..." -ForegroundColor Green
        docker exec ddd-app php scripts/show-admin-credentials.php
    }
    "create-user" { 
        Write-Host "👤 Creating new user..." -ForegroundColor Green
        docker exec ddd-app php scripts/create-user.php
    }
    "test" { 
        Write-Host "🧪 Running all tests..." -ForegroundColor Green
        docker exec ddd-app composer test
    }
    "test-unit" { 
        Write-Host "🧪 Running unit tests..." -ForegroundColor Green
        docker exec ddd-app vendor/bin/phpunit --testsuite Unit
    }
    "test-integration" { 
        Write-Host "🧪 Running integration tests..." -ForegroundColor Green
        docker exec ddd-app vendor/bin/phpunit --testsuite Integration
    }
    "test-feature" { 
        Write-Host "🧪 Running feature tests..." -ForegroundColor Green
        docker exec ddd-app vendor/bin/phpunit --testsuite Feature
    }
    "test-coverage" { 
        Write-Host "🧪 Running tests with coverage..." -ForegroundColor Green
        docker exec ddd-app vendor/bin/phpunit --coverage-html coverage/html
    }
    "install" { 
        Write-Host "📦 Installing PHP dependencies..." -ForegroundColor Green
        docker exec ddd-app composer install
    }
    "update" { 
        Write-Host "📦 Updating PHP dependencies..." -ForegroundColor Green
        docker exec ddd-app composer update
    }
    "cs-check" { 
        Write-Host "🔍 Checking code style..." -ForegroundColor Green
        docker exec ddd-app composer cs-check
    }
    "cs-fix" { 
        Write-Host "🔧 Fixing code style..." -ForegroundColor Green
        docker exec ddd-app composer cs-fix
    }
    "stan" { 
        Write-Host "🔍 Running static analysis..." -ForegroundColor Green
        docker exec ddd-app composer stan
    }
    "test-portal" { 
        Write-Host "🎓 Testing learning portal functionality..." -ForegroundColor Green
        docker exec ddd-app php scripts/test-learning-portal.php
    }
    "test-routing" { 
        Write-Host "🛣️  Testing routing system..." -ForegroundColor Green
        docker exec ddd-app php scripts/test-routing.php
    }
    "test-auth" { 
        Write-Host "🔐 Testing authentication system..." -ForegroundColor Green
        docker exec ddd-app php scripts/test-authentication.php
    }
    "clean" { 
        Write-Host "🧹 Cleaning up temporary files..." -ForegroundColor Green
        docker exec ddd-app rm -rf coverage/ tmp/ cache/ logs/
        Write-Host "✅ Cleanup completed!" -ForegroundColor Green
    }
    "status" { 
        Write-Host "📊 Project Status:" -ForegroundColor Green
        Write-Host "  Docker containers:" -ForegroundColor Yellow
        docker-compose ps
        Write-Host ""
        Write-Host "  Database connection:" -ForegroundColor Yellow
        docker exec ddd-app php scripts/test-database-connection.php
        Write-Host ""
        Write-Host "  Admin users:" -ForegroundColor Yellow
        docker exec ddd-app php scripts/show-admin-credentials.php
    }
    "backup" { 
        Write-Host "💾 Creating database backup..." -ForegroundColor Green
        if (!(Test-Path "backups")) {
            New-Item -ItemType Directory -Path "backups"
        }
        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
        docker exec ddd-db mysqldump -u ddd_user -psecret ddd_db > "backups/ddd_backup_$timestamp.sql"
        Write-Host "✅ Backup created successfully!" -ForegroundColor Green
    }
    "setup" { 
        Write-Host "🚀 Setting up PHP DDD Learning Portal..." -ForegroundColor Green
        Write-Host "1. Starting containers..." -ForegroundColor Yellow
        docker-compose up -d
        Write-Host "2. Setting up database..." -ForegroundColor Yellow
        docker exec ddd-app php scripts/setup-database.php
        Write-Host "3. Running migrations..." -ForegroundColor Yellow
        docker exec ddd-app php scripts/run-migrations.php
        Write-Host "4. Creating admin user..." -ForegroundColor Yellow
        docker exec ddd-app php scripts/create-default-admin.php
        Write-Host "5. Seeding sample data..." -ForegroundColor Yellow
        docker exec ddd-app php scripts/seed-learning-data.php
        Write-Host ""
        Write-Host "✅ Setup completed successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "🌐 Access URLs:" -ForegroundColor Yellow
        Write-Host "  Login: http://localhost:8080/login.html"
        Write-Host "  Dashboard: http://localhost:8080/dashboard.html"
        Write-Host "  PHPMyAdmin: http://localhost:8081"
        Write-Host ""
        Write-Host "🔑 Admin credentials:" -ForegroundColor Yellow
        Write-Host "  Email: admin@learningportal.com"
        Write-Host "  Password: Admin123!"
        Write-Host ""
        Write-Host "📋 Available commands: .\scripts\dev.ps1 help" -ForegroundColor Yellow
    }
    default {
        Write-Host "❌ Unknown command: $Command" -ForegroundColor Red
        Write-Host "Run '.\scripts\dev.ps1 help' for available commands" -ForegroundColor Yellow
    }
} 