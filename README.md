# UniConnect - University Book & Material Exchange Platform

A professional PHP-based web application for Ethiopian university students to exchange, borrow, donate, and sell academic books and materials.

## Project Overview

UniConnect connects students across Ethiopian universities, providing an organized platform for sharing educational resources. The system replaces informal methods (like Telegram groups) with a secure, feature-rich web application.

## Core Technologies

- **Backend**: PHP 8.2 with Object-Oriented Programming
- **Database**: MySQL (configured for port 3307)
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Authentication**: PHP Sessions and Cookies

## PHP Concepts Demonstrated

1. **Object-Oriented Programming (OOP)**
   - Classes: `Database`, `User`, `Book`, `Request`, `Message`, `Admin`
   - Encapsulation, inheritance, and polymorphism
   - Singleton pattern for database connection

2. **Database Management**
   - PDO for secure database access
   - Prepared statements to prevent SQL injection
   - Database relationships and foreign keys

3. **Session & Cookie Management**
   - Secure user authentication
   - "Remember Me" functionality with cookies
   - Session security best practices

4. **File Handling**
   - Image upload and storage
   - File validation and security
   - Directory management

## Features

### User Features
- ✅ Registration with university email validation (.edu.et)
- ✅ Secure login with session/cookie support
- ✅ Upload and manage book listings
- ✅ Search and filter books by multiple criteria
- ✅ Send and receive exchange requests
- ✅ Messaging system for coordination
- ✅ Profile management
- ✅ Dashboard with statistics

### Admin Features
- ✅ Approve or block book listings
- ✅ User management (activate/deactivate accounts)
- ✅ System statistics and reports
- ✅ Activity logs
- ✅ Top departments and exchange type analytics

## Database Schema

The application uses 5 main tables:
- `users` - User accounts and authentication
- `books` - Book listings and details
- `requests` - Exchange requests between users
- `messages` - Communication between users
- `admin_logs` - Admin activity tracking

## Installation & Setup

### Prerequisites
- XAMPP with MySQL running on port 3307
- PHP 8.2 or higher

### Setup Instructions

1. **Start MySQL** (via XAMPP on port 3307)

2. **Setup Database**
   ```bash
   php setup_database.php
   ```

3. **Access the Application**
   - Open your browser
   - Navigate to the application URL
   - Default admin login:
     - Email: admin@uniconnect.edu.et
     - Password: admin123

## Project Structure

```
uniconnect/
├── classes/           # PHP OOP classes
│   ├── Database.php   # Database connection (Singleton)
│   ├── User.php       # User authentication & management
│   ├── Book.php       # Book CRUD operations
│   ├── Request.php    # Request handling
│   ├── Message.php    # Messaging system
│   └── Admin.php      # Admin functions
├── config/            # Configuration files
│   └── database.php   # Database config
├── includes/          # Shared includes
│   ├── init.php       # Initialization & autoloader
│   ├── navbar.php     # Navigation bar
│   └── footer.php     # Footer
├── views/             # Application pages
│   ├── auth/          # Authentication pages
│   ├── dashboard/     # User dashboard
│   ├── admin/         # Admin panel
│   └── public/        # Public pages
├── assets/            # Static assets
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── uploads/           # User uploads
│   ├── books/         # Book images
│   └── profiles/      # Profile images
├── database/          # Database files
│   └── schema.sql     # Database schema
└── index.php          # Main entry point
```

## Security Features

- Password hashing with bcrypt
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF protection
- File upload validation
- Session security settings
- Email validation for university accounts

## Usage Guide

### For Students

1. **Register**: Create an account using your university email (.edu.et)
2. **Browse**: Search for books by title, author, course, or department
3. **Upload**: List your books for exchange, borrowing, or sale
4. **Request**: Send requests to book owners
5. **Coordinate**: Use the messaging system to arrange exchanges

### For Administrators

1. **Login**: Use admin credentials
2. **Approve**: Review and approve pending book listings
3. **Manage**: Monitor user activity and manage accounts
4. **Reports**: View analytics and system statistics

## Default Admin Account

- **Email**: admin@uniconnect.edu.et
- **Password**: admin123
- ⚠️ **Important**: Change the admin password after first login

## Key PHP OOP Concepts Applied

### 1. Singleton Pattern (Database.php)
```php
private static $instance = null;
public static function getInstance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### 2. Encapsulation
- Private properties with getter/setter methods
- Protected database connection
- Public interfaces for class interactions

### 3. Session Management
- Secure session initialization
- Cookie-based "Remember Me" feature
- Session timeout handling

### 4. File Handling
- Secure file uploads
- File type validation
- Storage management

## Contributing

This project is designed for educational purposes to demonstrate PHP OOP concepts, database management, session handling, and file operations.

## License

Educational project for Ethiopian universities.

## Support

For issues or questions, contact: admin@uniconnect.edu.et
