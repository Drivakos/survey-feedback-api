# Survey Feedback API

A comprehensive Laravel-based REST API for collecting and managing customer survey feedback with JWT authentication, Redis caching, and rate limiting.

## 🚀 Features

### ✅ Core Functionality
- **JWT Authentication** - Secure user registration and login
- **Survey Management** - Create, view, and submit surveys
- **Question Types** - Support for text, scale (1-5), and multiple choice questions
- **Answer Validation** - Type-safe answer validation with proper error handling
- **Duplicate Prevention** - Prevent users from submitting multiple answers to the same survey

### ✅ Performance & Security
- **Redis Caching** - High-performance caching for public survey data
- **Rate Limiting** - IP-based rate limiting (60 requests per minute)
- **JSON Logging** - Comprehensive logging of survey submissions to daily-rotated files
- **Input Validation** - Comprehensive validation for all endpoints
- **Error Handling** - Consistent API response format with proper HTTP status codes
- **Database Optimization** - Efficient queries with proper indexing

### ✅ Developer Experience
- **RESTful API** - Clean, RESTful endpoint design
- **Comprehensive Documentation** - Detailed API documentation with examples
- **Comprehensive Testing** - All endpoints tested and verified working
- **Production Ready** - Optimized for scalability and performance

## 🛠️ Tech Stack

- **PHP**: 8.3+
- **Framework**: Laravel 12.x
- **Database**: SQLite (development) / MySQL (production)
- **Cache**: Redis (with database fallback)
- **Authentication**: JWT (tymon/jwt-auth)
- **Rate Limiting**: Custom IP-based middleware
- **Logging**: JSON file logging with daily rotation
- **Testing**: PHPUnit (TODO)

## 📋 Prerequisites

- PHP 8.3 or higher
- Composer
- SQLite or MySQL database
- Redis (optional, for enhanced caching)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/Drivakos/survey-feedback-api.git
cd survey-feedback-api
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
# Create SQLite database (or configure MySQL in .env)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed dummy data
php artisan db:seed
```

### 5. Redis Setup (Optional - Defaults to Database Cache)

> **Note:** The `.env.example` file has `CACHE_STORE=redis` by default. If you don't want to set up Redis, simply change it to `CACHE_STORE=database` for a working database-based cache, same with SESSION_DRIVER and QUEUE_CONNECTION.

```bash
# Install Redis server (Memurai for Windows)
winget install Memurai.MemuraiDeveloper

# Install PHP Redis extension
# Download from: https://windows.php.net/downloads/pecl/releases/redis/
# Add to php.ini: extension=redis

# Update .env for full Redis integration
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Without Redis:** The API will work perfectly with database caching as a fallback.

### 6. Start the Server
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`

## 📚 API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/register
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login User
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Public Survey Endpoints

#### Get All Active Surveys
```http
GET /api/surveys
```

#### Get Survey Details
```http
GET /api/surveys/{id}
```

### Protected Endpoints (Require JWT Token)

#### Submit Survey Answers
```http
POST /api/surveys/{id}/submit
Authorization: Bearer {jwt_token}
```

#### Get Current User
```http
GET /api/me
Authorization: Bearer {jwt_token}
```

## 🔒 Security Features

### JWT Authentication
- Token-based authentication for protected endpoints
- 1-hour token expiration
- Secure password hashing with bcrypt

### Rate Limiting
- 60 requests per minute per IP address
- Automatic retry headers
- Prevents abuse and ensures fair usage

## 📊 Performance Features

### Redis Caching
- Public survey data cached for 1 hour
- Fast response times for cached data
- Automatic cache invalidation on data changes

### JSON Logging
- Survey submissions logged to daily-rotated JSON files
- Includes timestamp, survey info, responder details, and answers
- Stored in `storage/logs/surveys/` directory
- Metadata includes IP address and user agent

---
