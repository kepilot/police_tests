# Makefile for PHP DDD Learning Portal
# Provides easy commands to run operations inside Docker containers

# Default target
.PHONY: help
help:
	@echo "PHP DDD Learning Portal - Available Commands:"
	@echo ""
	@echo "🐳 Docker Management:"
	@echo "  make up          - Start all containers"
	@echo "  make down        - Stop all containers"
	@echo "  make restart     - Restart all containers"
	@echo "  make logs        - Show container logs"
	@echo "  make shell       - Open shell in app container"
	@echo ""
	@echo "🗄️  Database Operations:"
	@echo "  make migrate     - Run all database migrations"
	@echo "  make setup-db    - Setup database (create tables)"
	@echo "  make seed        - Seed database with sample data"
	@echo "  make db-shell    - Open MySQL shell"
	@echo "  make db-status   - Check database connection"
	@echo ""
	@echo "👤 User Management:"
	@echo "  make admin       - Create default admin user"
	@echo "  make admin-custom - Create custom admin user"
	@echo "  make show-creds  - Show admin credentials"
	@echo "  make create-user - Create a new user"
	@echo ""
	@echo "🧪 Testing:"
	@echo "  make test        - Run all tests"
	@echo "  make test-unit   - Run unit tests only"
	@echo "  make test-integration - Run integration tests only"
	@echo "  make test-feature - Run feature tests only"
	@echo "  make test-coverage - Run tests with coverage"
	@echo ""
	@echo "🔧 Development:"
	@echo "  make install     - Install PHP dependencies"
	@echo "  make update      - Update PHP dependencies"
	@echo "  make cs-check    - Check code style"
	@echo "  make cs-fix      - Fix code style"
	@echo "  make stan        - Run static analysis"
	@echo ""
	@echo "📊 Learning Portal:"
	@echo "  make test-portal - Test learning portal functionality"
	@echo "  make test-routing - Test routing system"
	@echo "  make test-auth   - Test authentication system"
	@echo ""
	@echo "🛠️  Utilities:"
	@echo "  make clean       - Clean up temporary files"
	@echo "  make status      - Show project status"
	@echo "  make backup      - Create database backup"
	@echo "  make restore     - Restore database from backup"

# Docker Management
.PHONY: up down restart logs shell
up:
	@echo "🚀 Starting Docker containers..."
	docker-compose up -d
	@echo "✅ Containers started successfully!"

down:
	@echo "🛑 Stopping Docker containers..."
	docker-compose down
	@echo "✅ Containers stopped successfully!"

restart:
	@echo "🔄 Restarting Docker containers..."
	docker-compose restart
	@echo "✅ Containers restarted successfully!"

logs:
	@echo "📋 Showing container logs..."
	docker-compose logs -f

shell:
	@echo "🐚 Opening shell in app container..."
	docker exec -it ddd-app bash

# Database Operations
.PHONY: migrate setup-db seed db-shell db-status
migrate:
	@echo "🗄️  Running database migrations..."
	docker exec ddd-app php scripts/run-migrations.php
	@echo "✅ Migrations completed!"

setup-db:
	@echo "🗄️  Setting up database..."
	docker exec ddd-app php scripts/setup-database.php
	@echo "✅ Database setup completed!"

seed:
	@echo "🌱 Seeding database with sample data..."
	docker exec ddd-app php scripts/seed-learning-data.php
	@echo "✅ Database seeded successfully!"

db-shell:
	@echo "🐚 Opening MySQL shell..."
	docker exec -it ddd-db mysql -u ddd_user -psecret ddd_db

db-status:
	@echo "📊 Checking database connection..."
	docker exec ddd-app php scripts/test-database-connection.php

# User Management
.PHONY: admin admin-custom show-creds create-user
admin:
	@echo "👤 Creating default admin user..."
	docker exec ddd-app php scripts/create-default-admin.php

admin-custom:
	@echo "👤 Creating custom admin user..."
	docker exec ddd-app php scripts/create-admin-user.php

show-creds:
	@echo "🔑 Showing admin credentials..."
	docker exec ddd-app php scripts/show-admin-credentials.php

create-user:
	@echo "👤 Creating new user..."
	docker exec ddd-app php scripts/create-user.php

# Testing
.PHONY: test test-unit test-integration test-feature test-coverage
test:
	@echo "🧪 Running all tests..."
	docker exec ddd-app composer test

test-unit:
	@echo "🧪 Running unit tests..."
	docker exec ddd-app vendor/bin/phpunit --testsuite Unit

test-integration:
	@echo "🧪 Running integration tests..."
	docker exec ddd-app vendor/bin/phpunit --testsuite Integration

test-feature:
	@echo "🧪 Running feature tests..."
	docker exec ddd-app vendor/bin/phpunit --testsuite Feature

test-coverage:
	@echo "🧪 Running tests with coverage..."
	docker exec ddd-app vendor/bin/phpunit --coverage-html coverage/html

# Development
.PHONY: install update cs-check cs-fix stan
install:
	@echo "📦 Installing PHP dependencies..."
	docker exec ddd-app composer install

update:
	@echo "📦 Updating PHP dependencies..."
	docker exec ddd-app composer update

cs-check:
	@echo "🔍 Checking code style..."
	docker exec ddd-app composer cs-check

cs-fix:
	@echo "🔧 Fixing code style..."
	docker exec ddd-app composer cs-fix

stan:
	@echo "🔍 Running static analysis..."
	docker exec ddd-app composer stan

# Learning Portal Testing
.PHONY: test-portal test-routing test-auth
test-portal:
	@echo "🎓 Testing learning portal functionality..."
	docker exec ddd-app php scripts/test-learning-portal.php

test-routing:
	@echo "🛣️  Testing routing system..."
	docker exec ddd-app php scripts/test-routing.php

test-auth:
	@echo "🔐 Testing authentication system..."
	docker exec ddd-app php scripts/test-authentication.php

# Utilities
.PHONY: clean status backup restore
clean:
	@echo "🧹 Cleaning up temporary files..."
	docker exec ddd-app rm -rf coverage/ tmp/ cache/ logs/
	@echo "✅ Cleanup completed!"

status:
	@echo "📊 Project Status:"
	@echo "  Docker containers:"
	@docker-compose ps
	@echo ""
	@echo "  Database connection:"
	@docker exec ddd-app php scripts/test-database-connection.php 2>/dev/null || echo "  ❌ Database connection failed"
	@echo ""
	@echo "  Admin users:"
	@docker exec ddd-app php scripts/show-admin-credentials.php 2>/dev/null || echo "  ❌ No admin users found"

backup:
	@echo "💾 Creating database backup..."
	@mkdir -p backups
	docker exec ddd-db mysqldump -u ddd_user -psecret ddd_db > backups/ddd_backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup created successfully!"

restore:
	@echo "📥 Restoring database from backup..."
	@if [ -z "$(BACKUP_FILE)" ]; then \
		echo "❌ Please specify backup file: make restore BACKUP_FILE=backups/filename.sql"; \
		exit 1; \
	fi
	docker exec -i ddd-db mysql -u ddd_user -psecret ddd_db < $(BACKUP_FILE)
	@echo "✅ Database restored successfully!"

# Quick setup for new developers
.PHONY: setup
setup:
	@echo "🚀 Setting up PHP DDD Learning Portal..."
	@echo "1. Starting containers..."
	$(MAKE) up
	@echo "2. Setting up database..."
	$(MAKE) setup-db
	@echo "3. Running migrations..."
	$(MAKE) migrate
	@echo "4. Creating admin user..."
	$(MAKE) admin
	@echo "5. Seeding sample data..."
	$(MAKE) seed
	@echo ""
	@echo "✅ Setup completed successfully!"
	@echo ""
	@echo "🌐 Access URLs:"
	@echo "  Login: http://localhost:8080/login.html"
	@echo "  Dashboard: http://localhost:8080/dashboard.html"
	@echo "  PHPMyAdmin: http://localhost:8081"
	@echo ""
	@echo "🔑 Admin credentials:"
	@echo "  Email: admin@learningportal.com"
	@echo "  Password: Admin123!"
	@echo ""
	@echo "📋 Available commands: make help" 