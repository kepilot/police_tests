#!/bin/bash
# Development script for PHP DDD Learning Portal
# Usage: ./scripts/dev.sh [command]

COMMAND=${1:-help}

show_help() {
    echo "PHP DDD Learning Portal - Available Commands:"
    echo ""
    echo "🐳 Docker Management:"
    echo "  ./scripts/dev.sh up          - Start all containers"
    echo "  ./scripts/dev.sh down        - Stop all containers"
    echo "  ./scripts/dev.sh restart     - Restart all containers"
    echo "  ./scripts/dev.sh logs        - Show container logs"
    echo "  ./scripts/dev.sh shell       - Open shell in app container"
    echo ""
    echo "🗄️  Database Operations:"
    echo "  ./scripts/dev.sh migrate     - Run all database migrations"
    echo "  ./scripts/dev.sh setup-db    - Setup database (create tables)"
    echo "  ./scripts/dev.sh seed        - Seed database with sample data"
    echo "  ./scripts/dev.sh db-shell    - Open MySQL shell"
    echo "  ./scripts/dev.sh db-status   - Check database connection"
    echo ""
    echo "👤 User Management:"
    echo "  ./scripts/dev.sh admin       - Create default admin user"
    echo "  ./scripts/dev.sh admin-custom - Create custom admin user"
    echo "  ./scripts/dev.sh show-creds  - Show admin credentials"
    echo "  ./scripts/dev.sh create-user - Create a new user"
    echo ""
    echo "🧪 Testing:"
    echo "  ./scripts/dev.sh test        - Run all tests"
    echo "  ./scripts/dev.sh test-unit   - Run unit tests only"
    echo "  ./scripts/dev.sh test-integration - Run integration tests only"
    echo "  ./scripts/dev.sh test-feature - Run feature tests only"
    echo "  ./scripts/dev.sh test-coverage - Run tests with coverage"
    echo ""
    echo "🔧 Development:"
    echo "  ./scripts/dev.sh install     - Install PHP dependencies"
    echo "  ./scripts/dev.sh update      - Update PHP dependencies"
    echo "  ./scripts/dev.sh cs-check    - Check code style"
    echo "  ./scripts/dev.sh cs-fix      - Fix code style"
    echo "  ./scripts/dev.sh stan        - Run static analysis"
    echo ""
    echo "📊 Learning Portal:"
    echo "  ./scripts/dev.sh test-portal - Test learning portal functionality"
    echo "  ./scripts/dev.sh test-routing - Test routing system"
    echo "  ./scripts/dev.sh test-auth   - Test authentication system"
    echo ""
    echo "🛠️  Utilities:"
    echo "  ./scripts/dev.sh clean       - Clean up temporary files"
    echo "  ./scripts/dev.sh status      - Show project status"
    echo "  ./scripts/dev.sh backup      - Create database backup"
    echo "  ./scripts/dev.sh setup       - Complete project setup"
}

case $COMMAND in
    "help")
        show_help
        ;;
    "up")
        echo "🚀 Starting Docker containers..."
        docker-compose up -d
        echo "✅ Containers started successfully!"
        ;;
    "down")
        echo "🛑 Stopping Docker containers..."
        docker-compose down
        echo "✅ Containers stopped successfully!"
        ;;
    "restart")
        echo "🔄 Restarting Docker containers..."
        docker-compose restart
        echo "✅ Containers restarted successfully!"
        ;;
    "logs")
        echo "📋 Showing container logs..."
        docker-compose logs -f
        ;;
    "shell")
        echo "🐚 Opening shell in app container..."
        docker exec -it ddd-app bash
        ;;
    "migrate")
        echo "🗄️  Running database migrations..."
        docker exec ddd-app php scripts/run-migrations.php
        echo "✅ Migrations completed!"
        ;;
    "setup-db")
        echo "🗄️  Setting up database..."
        docker exec ddd-app php scripts/setup-database.php
        echo "✅ Database setup completed!"
        ;;
    "seed")
        echo "🌱 Seeding database with sample data..."
        docker exec ddd-app php scripts/seed-learning-data.php
        echo "✅ Database seeded successfully!"
        ;;
    "db-shell")
        echo "🐚 Opening MySQL shell..."
        docker exec -it ddd-db mysql -u ddd_user -psecret ddd_db
        ;;
    "db-status")
        echo "📊 Checking database connection..."
        docker exec ddd-app php scripts/test-database-connection.php
        ;;
    "admin")
        echo "👤 Creating default admin user..."
        docker exec ddd-app php scripts/create-default-admin.php
        ;;
    "admin-custom")
        echo "👤 Creating custom admin user..."
        docker exec ddd-app php scripts/create-admin-user.php
        ;;
    "show-creds")
        echo "🔑 Showing admin credentials..."
        docker exec ddd-app php scripts/show-admin-credentials.php
        ;;
    "create-user")
        echo "👤 Creating new user..."
        docker exec ddd-app php scripts/create-user.php
        ;;
    "test")
        echo "🧪 Running all tests..."
        docker exec ddd-app composer test
        ;;
    "test-unit")
        echo "🧪 Running unit tests..."
        docker exec ddd-app vendor/bin/phpunit --testsuite Unit
        ;;
    "test-integration")
        echo "🧪 Running integration tests..."
        docker exec ddd-app vendor/bin/phpunit --testsuite Integration
        ;;
    "test-feature")
        echo "🧪 Running feature tests..."
        docker exec ddd-app vendor/bin/phpunit --testsuite Feature
        ;;
    "test-coverage")
        echo "🧪 Running tests with coverage..."
        docker exec ddd-app vendor/bin/phpunit --coverage-html coverage/html
        ;;
    "install")
        echo "📦 Installing PHP dependencies..."
        docker exec ddd-app composer install
        ;;
    "update")
        echo "📦 Updating PHP dependencies..."
        docker exec ddd-app composer update
        ;;
    "cs-check")
        echo "🔍 Checking code style..."
        docker exec ddd-app composer cs-check
        ;;
    "cs-fix")
        echo "🔧 Fixing code style..."
        docker exec ddd-app composer cs-fix
        ;;
    "stan")
        echo "🔍 Running static analysis..."
        docker exec ddd-app composer stan
        ;;
    "test-portal")
        echo "🎓 Testing learning portal functionality..."
        docker exec ddd-app php scripts/test-learning-portal.php
        ;;
    "test-routing")
        echo "🛣️  Testing routing system..."
        docker exec ddd-app php scripts/test-routing.php
        ;;
    "test-auth")
        echo "🔐 Testing authentication system..."
        docker exec ddd-app php scripts/test-authentication.php
        ;;
    "clean")
        echo "🧹 Cleaning up temporary files..."
        docker exec ddd-app rm -rf coverage/ tmp/ cache/ logs/
        echo "✅ Cleanup completed!"
        ;;
    "status")
        echo "📊 Project Status:"
        echo "  Docker containers:"
        docker-compose ps
        echo ""
        echo "  Database connection:"
        docker exec ddd-app php scripts/test-database-connection.php
        echo ""
        echo "  Admin users:"
        docker exec ddd-app php scripts/show-admin-credentials.php
        ;;
    "backup")
        echo "💾 Creating database backup..."
        mkdir -p backups
        timestamp=$(date +%Y%m%d_%H%M%S)
        docker exec ddd-db mysqldump -u ddd_user -psecret ddd_db > "backups/ddd_backup_${timestamp}.sql"
        echo "✅ Backup created successfully!"
        ;;
    "setup")
        echo "🚀 Setting up PHP DDD Learning Portal..."
        echo "1. Starting containers..."
        ./scripts/dev.sh up
        echo "2. Setting up database..."
        ./scripts/dev.sh setup-db
        echo "3. Running migrations..."
        ./scripts/dev.sh migrate
        echo "4. Creating admin user..."
        ./scripts/dev.sh admin
        echo "5. Seeding sample data..."
        ./scripts/dev.sh seed
        echo ""
        echo "✅ Setup completed successfully!"
        echo ""
        echo "🌐 Access URLs:"
        echo "  Login: http://localhost:8080/login.html"
        echo "  Dashboard: http://localhost:8080/dashboard.html"
        echo "  PHPMyAdmin: http://localhost:8081"
        echo ""
        echo "🔑 Admin credentials:"
        echo "  Email: admin@learningportal.com"
        echo "  Password: Admin123!"
        echo ""
        echo "📋 Available commands: ./scripts/dev.sh help"
        ;;
    *)
        echo "❌ Unknown command: $COMMAND"
        echo "Run './scripts/dev.sh help' for available commands"
        exit 1
        ;;
esac 