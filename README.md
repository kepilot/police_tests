# PHP DDD Project - Secure User Portal

A PHP project built using Domain-Driven Design (DDD) principles with secure user authentication and registration.

## 🚀 Features

- **User Registration & Login**: Secure user authentication with password hashing
- **JWT Authentication**: Token-based authentication for API access
- **Password Security**: Strong password validation and bcrypt hashing
- **Environment Configuration**: Secure configuration management with .env files
- **Database Security**: Proper data storage with encrypted passwords
- **DDD Architecture**: Clean separation of concerns across all layers
- **Learning Portal**: Complete educational platform with topics, exams, and user management
- **Role-Based Access Control**: Admin and Super Admin roles for content management
- **Automatic Route Protection**: All routes protected by authentication by default
- **PDF OCR Processing**: Advanced OCR system using Google Gemini Vision API
- **RabbitMQ Queue System**: Asynchronous processing for scanned PDFs with robust queue management

## 🌐 Web Interface

- **Login Portal**: http://localhost:8080/login.html
- **User Dashboard**: http://localhost:8080/dashboard.html (requires authentication)
- **Admin Panel**: http://localhost:8080/admin.html (admin access required)
- **RabbitMQ Management**: http://localhost:15672 (admin/admin123)

## 📡 API Endpoints

### Authentication
- **POST /auth/register** - Register a new user
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!"
  }
  ```

- **POST /auth/login** - Login user
  ```json
  {
    "email": "john@example.com",
    "password": "SecurePass123!"
  }
  ```

### User Management
- **POST /users** - Create user (legacy endpoint)
- **GET /users** - List users (requires authentication)
- **GET /dashboard.html** - User dashboard (requires authentication)

### Learning Portal (All endpoints require authentication)
- **GET /topics** - List all learning topics
- **POST /topics** - Create new topic (admin only)
- **GET /exams** - List all exams
- **POST /exams** - Create new exam (admin only)
- **GET /learning/stats** - Get learning statistics

### PDF Processing
- **POST /pdf/upload** - Upload PDF for OCR processing
- **POST /pdf/import** - Import extracted questions to database

## 🔄 Queue System (RabbitMQ)

The project includes a robust queue system for processing scanned PDFs asynchronously:

### Queue Architecture
- **PDF OCR Queue**: Converts PDFs to images
- **Gemini API Queue**: Processes images with Google Gemini Vision API
- **Results Queue**: Saves results and sends notifications
- **Dead Letter Queue**: Handles failed jobs

### Workers
- **PDF OCR Worker**: `scripts/workers/pdf-ocr-worker.php`
- **Gemini API Worker**: `scripts/workers/gemini-api-worker.php`
- **Results Worker**: `scripts/workers/results-worker.php`

### Queue Management
```bash
# Start workers
docker-compose exec app php scripts/start-workers.php

# Monitor queues
docker-compose exec app php scripts/monitor-queues.php

# Test queue system
docker-compose exec app php scripts/test-queue-system.php
```

For detailed queue documentation, see [QUEUE_SERVICE_DOCUMENTATION.md](QUEUE_SERVICE_DOCUMENTATION.md).

## 🔐 Security Features

### Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

### JWT Tokens
- Secure token generation with expiration
- Token validation and user identification
- Configurable expiration time (default: 1 hour)

### Database Security
- Passwords stored as bcrypt hashes
- Configurable hash cost (default: 12)
- User activity tracking (last login, account status)

### Route Protection
- Authentication middleware protects all routes except public ones
- Automatic redirect to login for unauthenticated users
- JWT token validation on every request
- Session-based user information storage

## 🗄️ Database Structure

```sql
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user' NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
);

CREATE TABLE topics (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
);

CREATE TABLE exams (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL,
    passing_score_percentage INT NOT NULL,
    topic_id VARCHAR(36) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (topic_id) REFERENCES topics(id)
);

CREATE TABLE questions (
    id VARCHAR(36) PRIMARY KEY,
    text TEXT NOT NULL,
    type ENUM('multiple_choice', 'true_false', 'single_choice') NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    options JSON NOT NULL,
    correct_option INT NOT NULL,
    points INT DEFAULT 1 NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

CREATE TABLE exam_attempts (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    score INT,
    passed BOOLEAN,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);
```

## ⚙️ Configuration

Create a `.env` file based on `env.example`:

```env
# Database Configuration
DB_HOST=db
DB_PORT=3306
DB_NAME=ddd_db
DB_USER=ddd_user
DB_PASSWORD=secret

# Application Configuration
APP_ENV=development
APP_DEBUG=true
APP_SECRET=your-super-secret-key-here-change-in-production

# Security Configuration
JWT_SECRET=your-jwt-secret-key-here-change-in-production
PASSWORD_HASH_COST=12

# Server Configuration
SERVER_HOST=0.0.0.0
SERVER_PORT=8080
```

## 🏗️ Project Structure

```
src/
├── Domain/           # Core business logic and domain models
│   ├── Entities/     # Domain entities (User)
│   ├── ValueObjects/ # Value objects (Email, Password)
│   ├── Events/       # Domain events
│   └── Services/     # Domain services
├── Application/      # Application layer
│   ├── Commands/     # Command handlers (Register, Login)
│   ├── Queries/      # Query handlers
│   └── Services/     # Application services
├── Infrastructure/   # Infrastructure layer
│   ├── Persistence/  # Database repositories
│   ├── Services/     # External services (JWT)
│   └── Container/    # Dependency injection
└── Presentation/     # Presentation layer
    ├── Controllers/  # HTTP controllers
    └── Views/        # View templates
```

## 🚀 Getting Started

1. **Start the containers**:
   ```bash
   docker-compose up -d
   ```

2. **Setup the database**:
   ```bash
   docker exec ddd-app php scripts/setup-database.php
   ```

3. **Access the application**:
   - Login Portal: http://localhost:8080/login.html
   - User Dashboard: http://localhost:8080/dashboard.html
   - API Base: http://localhost:8080
   - PHPMyAdmin: http://localhost:8081

## 🧪 Testing

The project includes comprehensive test suites for all layers:

### Test Structure
- **Unit Tests**: Test individual components in isolation
- **Integration Tests**: Test component interactions  
- **Feature Tests**: Test complete user workflows

### Running Tests
```bash
# Run all tests with PHPUnit
composer test

# Run specific test suites
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Feature

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/html

# Run manual test suite (when PHPUnit has issues)
php run-tests.php
```

### Test Coverage
- ✅ **Domain Layer**: Entities, Value Objects
- ✅ **Application Layer**: Commands, Handlers
- ✅ **Infrastructure Layer**: Services, Repositories, Middleware
- ✅ **Authentication**: JWT tokens, password validation
- ✅ **Route Protection**: Middleware, public/private routes
- ✅ **API Endpoints**: Registration, login, protected routes
- ✅ **Learning Portal**: Topics, exams, questions, and user management
- ✅ **Role-Based Access Control**: Admin and user role validation

## 🔧 Development

### Quick Commands (Cross-Platform)

**Windows:**
```bash
.\scripts\dev.bat help          # Show all available commands
.\scripts\dev.bat setup         # Complete project setup
.\scripts\dev.bat migrate       # Run database migrations
.\scripts\dev.bat show-creds    # Show admin credentials
.\scripts\dev.bat test          # Run all tests
```

**Linux/Mac:**
```bash
./scripts/dev.sh help           # Show all available commands
./scripts/dev.sh setup          # Complete project setup
./scripts/dev.sh migrate        # Run database migrations
./scripts/dev.sh show-creds     # Show admin credentials
./scripts/dev.sh test           # Run all tests
```

**Makefile (Linux/Mac):**
```bash
make help                       # Show all available commands
make setup                      # Complete project setup
make migrate                    # Run database migrations
make show-creds                 # Show admin credentials
make test                       # Run all tests
```

### Available Commands

#### 🐳 Docker Management
- `up` - Start all containers
- `down` - Stop all containers
- `restart` - Restart all containers
- `logs` - Show container logs
- `shell` - Open shell in app container

#### 🗄️ Database Operations
- `migrate` - Run all database migrations
- `setup-db` - Setup database (create tables)
- `seed` - Seed database with sample data
- `db-shell` - Open MySQL shell
- `db-status` - Check database connection

#### 👤 User Management
- `admin` - Create default admin user
- `admin-custom` - Create custom admin user
- `show-creds` - Show admin credentials
- `create-user` - Create a new user

#### 🧪 Testing
- `test` - Run all tests
- `test-unit` - Run unit tests only
- `test-integration` - Run integration tests only
- `test-feature` - Run feature tests only
- `test-coverage` - Run tests with coverage

#### 🔧 Development
- `install` - Install PHP dependencies
- `update` - Update PHP dependencies
- `cs-check` - Check code style
- `cs-fix` - Fix code style
- `stan` - Run static analysis

#### 📊 Learning Portal
- `test-portal` - Test learning portal functionality
- `test-routing` - Test routing system
- `test-auth` - Test authentication system

#### 🛠️ Utilities
- `clean` - Clean up temporary files
- `status` - Show project status
- `backup` - Create database backup
- `setup` - Complete project setup

### Traditional Commands (Inside Container)
- Run tests: `composer test`
- Check code style: `composer cs-check`
- Fix code style: `composer cs-fix`
- Run static analysis: `composer stan`

## 🎓 Learning Portal

The application includes a comprehensive learning portal with the following features:

### Topic Management
- Create and organize learning topics by difficulty level
- Support for beginner, intermediate, advanced, and expert levels
- Topic descriptions and metadata management

### Exam System
- Create comprehensive exams with multiple question types
- Support for multiple choice, true/false, and single choice questions
- Configurable duration and passing score requirements
- Point-based scoring system

### User Role System
- **User Role**: Can view topics and take exams
- **Admin Role**: Can create and manage topics and exams
- **Super Admin Role**: Full system access including user management

### Exam Attempt Tracking
- Monitor user progress and performance
- Track exam completion and scores
- Comprehensive analytics and reporting

## 🛡️ Security Best Practices

1. **Change default secrets** in production
2. **Use HTTPS** in production
3. **Implement rate limiting** for auth endpoints
4. **Add password reset functionality**
5. **Implement account lockout** after failed attempts
6. **Add audit logging** for security events

## 📝 DDD Principles

This project follows Domain-Driven Design principles:

1. **Ubiquitous Language**: Domain model and code use the same language
2. **Bounded Contexts**: Clear boundaries between different parts of the system
3. **Aggregates**: Transactional consistency boundaries
4. **Value Objects**: Immutable objects that describe characteristics
5. **Entities**: Objects with identity and lifecycle
6. **Domain Events**: Events that represent something that happened in the domain
7. **Repositories**: Abstraction of persistence
8. **Services**: Stateless operations that don't fit within an entity or value object

## 🔒 Production Deployment

1. Update all secrets in `.env`
2. Set `APP_ENV=production`
3. Set `APP_DEBUG=false`
4. Configure proper database credentials
5. Set up SSL/TLS certificates
6. Configure proper backup strategies
7. Set up monitoring and logging 