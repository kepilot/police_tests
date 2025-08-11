# PHP DDD Project - Secure Learning Portal

A PHP application built using Domain-Driven Design principles with secure authentication, learning management, and advanced PDF OCR processing.

## 🚀 Features

- **User Authentication**: Secure login with JWT tokens and role-based access
- **Learning Portal**: Topics, exams, and question management
- **PDF OCR Processing**: Extract questions from scanned PDFs using Google Gemini Vision API
- **Queue System**: Asynchronous processing with RabbitMQ for better performance
- **Admin Panel**: Complete content management interface

## 🌐 Quick Access

- **Login**: http://localhost:8080/login.html
- **Dashboard**: http://localhost:8080/dashboard.html
- **Admin Panel**: http://localhost:8080/admin.html
- **RabbitMQ Management**: http://localhost:15672 (admin/admin123)

## 🏗️ Architecture

- **Domain Layer**: Business logic and entities
- **Application Layer**: Command handlers and services
- **Infrastructure Layer**: Database, external services, messaging
- **Presentation Layer**: Controllers and views

## 🚀 Getting Started

1. **Start containers**: `docker-compose up -d`
2. **Setup database**: `docker exec ddd-app php scripts/setup-database.php`
3. **Access application**: http://localhost:8080

## 📚 Documentation

- **Queue System**: [QUEUE_SERVICE_DOCUMENTATION.md](QUEUE_SERVICE_DOCUMENTATION.md)
- **API Endpoints**: See individual controller files
- **Database Schema**: Check migration files in `database/migrations/`

## 🧪 Testing

- **Unit Tests**: `composer test`
- **Manual Tests**: `php run-tests.php`
- **Code Quality**: `composer cs-check` and `composer stan`

## 🔧 Development

Use the provided scripts for common tasks:
- **Windows**: `.\scripts\dev.bat`
- **Linux/Mac**: `./scripts/dev.sh` or `make`

## 🔐 Security

- Password hashing with bcrypt
- JWT token authentication
- Role-based access control
- Protected routes by default

## 📦 Dependencies

- PHP 8.1+
- MySQL/MariaDB
- RabbitMQ
- Google Gemini Vision API
- Docker & Docker Compose 