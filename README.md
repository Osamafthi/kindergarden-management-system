# Kindergarten Management System

A comprehensive web-based management system for kindergarten schools, built with PHP and MySQL. This system helps manage students, teachers, classrooms, attendance, homework assignments, inventory, and more.

## Features

### Student Management
- Add, edit, and manage student records with photos
- Student status tracking (active/inactive)
- Student reports and academic progress
- Quranic recitation tracking

### Teacher Management
- Teacher profiles with credentials
- Salary management and payment tracking
- Teacher assignment to classrooms
- Performance monitoring

### Classroom Management
- Create and organize classrooms
- Assign teachers to classrooms
- Manage classroom capacity
- Track academic years and semesters

### Attendance System
- Daily attendance tracking
- Attendance reports by date range
- Student attendance history
- Reopen attendance for corrections

### Homework Management
- Multiple homework types and modules
- Chapter-based homework tracking
- Grade assignments and feedback
- Student homework reports

### Sessions Management
- Teacher session tracking
- Session duration and scheduling
- Classroom-based session views
- Student session history

### Inventory Management
- Product catalog management
- Stock quantity control
- Inventory updates and tracking

### Reports & Analytics
- Student comprehensive reports
- Attendance statistics
- Salary payment history
- School days configuration

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (XAMPP recommended)
- **Architecture**: Object-Oriented PHP with MVC pattern

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache Web Server
- PDO PHP Extension
- GD Library (for image handling)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/kindergarden.git
cd kindergarden
```

### 2. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE kindergarden CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema (if you have a SQL dump file):
```bash
mysql -u root -p kindergarden < database.sql
```

3. Or run the migration scripts in the `migrations/` directory

### 3. Configuration

1. Copy the example configuration file:
```bash
cp config.example.php config.php
```

2. Edit `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kindergarden');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

3. Update the socket path if using XAMPP on macOS:
```php
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
```

### 4. File Permissions

Make sure the uploads directory is writable:
```bash
chmod -R 755 assets/uploads/
```

### 5. Create Admin User

Run the admin user creation script:
```bash
php includes/create_admin_user.php
```

Or access it via browser:
```
http://localhost/kindergarden/includes/create_admin_user.php
```

### 6. Access the Application

Open your browser and navigate to:
```
http://localhost/kindergarden/views/auth/login.php
```

Default admin credentials (if using create_admin_user.php):
- Username: admin
- Password: (set during creation)

## Project Structure

```
kindergarden/
├── api/                    # API endpoints for AJAX requests
│   ├── add-*.php          # Create operations
│   ├── get-*.php          # Read operations
│   └── update-*.php       # Update operations
├── assets/                # Static resources
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # Static images
│   └── uploads/          # User-uploaded content
├── classes/              # PHP classes (OOP)
│   ├── Database.php      # Database connection
│   ├── Student.php       # Student operations
│   ├── Teacher.php       # Teacher operations
│   ├── Attendance.php    # Attendance tracking
│   ├── Homework.php      # Homework management
│   └── ...               # Other domain classes
├── includes/             # Shared PHP includes
│   ├── init.php          # Initialization
│   ├── autoload.php      # Class autoloader
│   └── SessionManager.php # Session handling
├── migrations/           # Database migrations
├── views/                # View templates
│   ├── admin/           # Admin dashboard views
│   ├── teacher/         # Teacher dashboard views
│   └── auth/            # Authentication views
├── config.example.php    # Configuration template
└── .gitignore           # Git ignore rules
```

## Usage

### Admin Dashboard

After logging in as admin, you can:
- Manage students and teachers
- Create and organize classrooms
- Track attendance and homework
- Generate reports
- Manage inventory
- Configure school settings

### Teacher Dashboard

Teachers can:
- View assigned classrooms
- Take student attendance
- Create and grade homework
- Track student sessions
- View student reports

## Security Notes

- Never commit `config.php` to version control
- The `.gitignore` file excludes sensitive data and uploads
- Change default passwords immediately after setup
- Use strong passwords for database and admin accounts
- Keep PHP and MySQL updated to latest stable versions

## Database Schema

The system uses the following main tables:
- `users` - System users (admin, teachers)
- `students` - Student records
- `teachers` - Teacher information
- `classrooms` - Classroom definitions
- `attendance` - Daily attendance records
- `homework_types` - Homework categories
- `homework` - Homework assignments
- `sessions` - Teacher session tracking
- `products` - Inventory items
- `academic_years` - School year definitions
- `semesters` - Semester periods

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open source and available for educational purposes.

## Support

For issues, questions, or contributions, please open an issue on GitHub.

## Credits

Developed for kindergarten school management and administration.

## Changelog

### Version 1.0.0
- Initial release
- Student and teacher management
- Attendance tracking
- Homework system
- Inventory management
- Reports and analytics

