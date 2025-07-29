@echo off
REM Development script for PHP DDD Learning Portal
REM Usage: dev.bat [command]

set COMMAND=%1
if "%COMMAND%"=="" set COMMAND=help

if "%COMMAND%"=="help" goto help
if "%COMMAND%"=="up" goto up
if "%COMMAND%"=="down" goto down
if "%COMMAND%"=="restart" goto restart
if "%COMMAND%"=="logs" goto logs
if "%COMMAND%"=="shell" goto shell
if "%COMMAND%"=="migrate" goto migrate
if "%COMMAND%"=="setup-db" goto setup-db
if "%COMMAND%"=="seed" goto seed
if "%COMMAND%"=="db-shell" goto db-shell
if "%COMMAND%"=="db-status" goto db-status
if "%COMMAND%"=="admin" goto admin
if "%COMMAND%"=="admin-custom" goto admin-custom
if "%COMMAND%"=="show-creds" goto show-creds
if "%COMMAND%"=="create-user" goto create-user
if "%COMMAND%"=="test" goto test
if "%COMMAND%"=="test-unit" goto test-unit
if "%COMMAND%"=="test-integration" goto test-integration
if "%COMMAND%"=="test-feature" goto test-feature
if "%COMMAND%"=="test-coverage" goto test-coverage
if "%COMMAND%"=="install" goto install
if "%COMMAND%"=="update" goto update
if "%COMMAND%"=="cs-check" goto cs-check
if "%COMMAND%"=="cs-fix" goto cs-fix
if "%COMMAND%"=="stan" goto stan
if "%COMMAND%"=="test-portal" goto test-portal
if "%COMMAND%"=="test-routing" goto test-routing
if "%COMMAND%"=="test-auth" goto test-auth
if "%COMMAND%"=="clean" goto clean
if "%COMMAND%"=="status" goto status
if "%COMMAND%"=="backup" goto backup
if "%COMMAND%"=="setup" goto setup
goto unknown

:help
echo PHP DDD Learning Portal - Available Commands:
echo.
echo 🐳 Docker Management:
echo   dev.bat up          - Start all containers
echo   dev.bat down        - Stop all containers
echo   dev.bat restart     - Restart all containers
echo   dev.bat logs        - Show container logs
echo   dev.bat shell       - Open shell in app container
echo.
echo 🗄️  Database Operations:
echo   dev.bat migrate     - Run all database migrations
echo   dev.bat setup-db    - Setup database (create tables)
echo   dev.bat seed        - Seed database with sample data
echo   dev.bat db-shell    - Open MySQL shell
echo   dev.bat db-status   - Check database connection
echo.
echo 👤 User Management:
echo   dev.bat admin       - Create default admin user
echo   dev.bat admin-custom - Create custom admin user
echo   dev.bat show-creds  - Show admin credentials
echo   dev.bat create-user - Create a new user
echo.
echo 🧪 Testing:
echo   dev.bat test        - Run all tests
echo   dev.bat test-unit   - Run unit tests only
echo   dev.bat test-integration - Run integration tests only
echo   dev.bat test-feature - Run feature tests only
echo   dev.bat test-coverage - Run tests with coverage
echo.
echo 🔧 Development:
echo   dev.bat install     - Install PHP dependencies
echo   dev.bat update      - Update PHP dependencies
echo   dev.bat cs-check    - Check code style
echo   dev.bat cs-fix      - Fix code style
echo   dev.bat stan        - Run static analysis
echo.
echo 📊 Learning Portal:
echo   dev.bat test-portal - Test learning portal functionality
echo   dev.bat test-routing - Test routing system
echo   dev.bat test-auth   - Test authentication system
echo.
echo 🛠️  Utilities:
echo   dev.bat clean       - Clean up temporary files
echo   dev.bat status      - Show project status
echo   dev.bat backup      - Create database backup
echo   dev.bat setup       - Complete project setup
goto end

:up
echo 🚀 Starting Docker containers...
docker-compose up -d
echo ✅ Containers started successfully!
goto end

:down
echo 🛑 Stopping Docker containers...
docker-compose down
echo ✅ Containers stopped successfully!
goto end

:restart
echo 🔄 Restarting Docker containers...
docker-compose restart
echo ✅ Containers restarted successfully!
goto end

:logs
echo 📋 Showing container logs...
docker-compose logs -f
goto end

:shell
echo 🐚 Opening shell in app container...
docker exec -it ddd-app bash
goto end

:migrate
echo 🗄️  Running database migrations...
docker exec ddd-app php scripts/run-migrations.php
echo ✅ Migrations completed!
goto end

:setup-db
echo 🗄️  Setting up database...
docker exec ddd-app php scripts/setup-database.php
echo ✅ Database setup completed!
goto end

:seed
echo 🌱 Seeding database with sample data...
docker exec ddd-app php scripts/seed-learning-data.php
echo ✅ Database seeded successfully!
goto end

:db-shell
echo 🐚 Opening MySQL shell...
docker exec -it ddd-db mysql -u ddd_user -psecret ddd_db
goto end

:db-status
echo 📊 Checking database connection...
docker exec ddd-app php scripts/test-database-connection.php
goto end

:admin
echo 👤 Creating default admin user...
docker exec ddd-app php scripts/create-default-admin.php
goto end

:admin-custom
echo 👤 Creating custom admin user...
docker exec ddd-app php scripts/create-admin-user.php
goto end

:show-creds
echo 🔑 Showing admin credentials...
docker exec ddd-app php scripts/show-admin-credentials.php
goto end

:create-user
echo 👤 Creating new user...
docker exec ddd-app php scripts/create-user.php
goto end

:test
echo 🧪 Running all tests...
docker exec ddd-app composer test
goto end

:test-unit
echo 🧪 Running unit tests...
docker exec ddd-app vendor/bin/phpunit --testsuite Unit
goto end

:test-integration
echo 🧪 Running integration tests...
docker exec ddd-app vendor/bin/phpunit --testsuite Integration
goto end

:test-feature
echo 🧪 Running feature tests...
docker exec ddd-app vendor/bin/phpunit --testsuite Feature
goto end

:test-coverage
echo 🧪 Running tests with coverage...
docker exec ddd-app vendor/bin/phpunit --coverage-html coverage/html
goto end

:install
echo 📦 Installing PHP dependencies...
docker exec ddd-app composer install
goto end

:update
echo 📦 Updating PHP dependencies...
docker exec ddd-app composer update
goto end

:cs-check
echo 🔍 Checking code style...
docker exec ddd-app composer cs-check
goto end

:cs-fix
echo 🔧 Fixing code style...
docker exec ddd-app composer cs-fix
goto end

:stan
echo 🔍 Running static analysis...
docker exec ddd-app composer stan
goto end

:test-portal
echo 🎓 Testing learning portal functionality...
docker exec ddd-app php scripts/test-learning-portal.php
goto end

:test-routing
echo 🛣️  Testing routing system...
docker exec ddd-app php scripts/test-routing.php
goto end

:test-auth
echo 🔐 Testing authentication system...
docker exec ddd-app php scripts/test-authentication.php
goto end

:clean
echo 🧹 Cleaning up temporary files...
docker exec ddd-app rm -rf coverage/ tmp/ cache/ logs/
echo ✅ Cleanup completed!
goto end

:status
echo 📊 Project Status:
echo   Docker containers:
docker-compose ps
echo.
echo   Database connection:
docker exec ddd-app php scripts/test-database-connection.php
echo.
echo   Admin users:
docker exec ddd-app php scripts/show-admin-credentials.php
goto end

:backup
echo 💾 Creating database backup...
if not exist "backups" mkdir backups
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "datestamp=%YYYY%%MM%%DD%_%HH%%Min%%Sec%"
docker exec ddd-db mysqldump -u ddd_user -psecret ddd_db > backups\ddd_backup_%datestamp%.sql
echo ✅ Backup created successfully!
goto end

:setup
echo 🚀 Setting up PHP DDD Learning Portal...
echo 1. Starting containers...
call dev.bat up
echo 2. Setting up database...
call dev.bat setup-db
echo 3. Running migrations...
call dev.bat migrate
echo 4. Creating admin user...
call dev.bat admin
echo 5. Seeding sample data...
call dev.bat seed
echo.
echo ✅ Setup completed successfully!
echo.
echo 🌐 Access URLs:
echo   Login: http://localhost:8080/login.html
echo   Dashboard: http://localhost:8080/dashboard.html
echo   PHPMyAdmin: http://localhost:8081
echo.
echo 🔑 Admin credentials:
echo   Email: admin@learningportal.com
echo   Password: Admin123!
echo.
echo 📋 Available commands: dev.bat help
goto end

:unknown
echo ❌ Unknown command: %COMMAND%
echo Run 'dev.bat help' for available commands
goto end

:end 