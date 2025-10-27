# Kindergarten Management System - Login System

## Overview
This login system provides secure authentication for teachers and administrators in the Kindergarten Management System.

## Features
- **Secure Authentication**: Password hashing using PHP's `password_hash()` function
- **Session Management**: Automatic session handling with timeout
- **Remember Me**: Optional persistent login with secure tokens
- **Role-Based Access**: Support for admin and teacher roles
- **API Endpoints**: RESTful API for login/logout operations
- **Session Security**: Session regeneration and secure cookie handling

## Components

### 1. Login Page (`views/auth/login.php`)
- Modern, responsive login form
- Email and password authentication
- Remember me functionality
- Real-time validation and error handling
- Automatic redirection based on user role

### 2. User Class (`classes/User.php`)
Enhanced with static methods for session management:
- `User::isLoggedIn()` - Check if user is logged in
- `User::isAdmin()` - Check if user is admin
- `User::isTeacher()` - Check if user is teacher
- `User::getCurrentUserId()` - Get current user ID
- `User::getCurrentUserRole()` - Get current user role
- `User::logout()` - Logout user and clear session
- `User::requireLogin()` - Require login (redirect if not logged in)
- `User::requireAdmin()` - Require admin access
- `User::requireTeacher()` - Require teacher access

### 3. SessionManager Class (`includes/SessionManager.php`)
Comprehensive session management:
- Automatic session initialization
- Remember me token handling
- Session timeout management
- Session regeneration for security
- Activity tracking

### 4. API Endpoints

#### Login API (`api/login.php`)
- **Method**: POST
- **Input**: JSON with email, password, remember (optional)
- **Output**: JSON with success status and user information

#### Logout API (`api/logout.php`)
- **Method**: POST
- **Output**: JSON with logout confirmation

#### Session Check API (`api/session-check.php`)
- **Method**: GET/POST
- **Output**: JSON with current session status

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    teacher_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(64) NULL,
    remember_token_expires DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Setup Instructions

1. **Run Database Migration**:
   ```bash
   php includes/database_migration.php
   ```

2. **Create Admin User**:
   ```bash
   php includes/create_admin_user.php
   ```

3. **Test the System**:
   - Visit `/kindergarden/test-login.php` for testing
   - Use credentials: `admin@kindergarten.com` / `admin123`

## Usage Examples

### Check if user is logged in
```php
if (User::isLoggedIn()) {
    echo "User is logged in";
}
```

### Require admin access
```php
User::requireAdmin(); // Will redirect if not admin
```

### Get current user info
```php
$userId = User::getCurrentUserId();
$userRole = User::getCurrentUserRole();
$userEmail = User::getCurrentUserEmail();
```

### Initialize SessionManager
```php
$database = new Database();
$sessionManager = new SessionManager($database);
```

## Security Features

1. **Password Security**: All passwords are hashed using `password_hash()`
2. **Session Security**: Session IDs are regenerated on login
3. **Remember Me**: Secure tokens with expiration dates
4. **Session Timeout**: Automatic logout after 2 hours of inactivity
5. **SQL Injection Protection**: All queries use prepared statements
6. **XSS Protection**: Input validation and output escaping

## File Structure
```
kindergarden/
├── views/auth/
│   ├── login.php          # Login page
│   └── logout.php         # Logout page
├── api/
│   ├── login.php          # Login API
│   ├── logout.php         # Logout API
│   └── session-check.php  # Session check API
├── includes/
│   ├── SessionManager.php # Session management class
│   ├── database_migration.php
│   └── create_admin_user.php
├── classes/
│   ├── User.php           # Enhanced User class
│   └── Database.php       # Database connection
└── test-login.php         # Test page
```

## Testing

1. **Login Test**: Visit `/kindergarden/test-login.php`
2. **API Test**: Use the session check API to verify authentication
3. **Role Test**: Test admin and teacher access controls

## Default Credentials
- **Email**: admin@kindergarten.com
- **Password**: admin123

**⚠️ Important**: Change the default password in production!
