# Setup Instructions for UniConnect

## Important: Database Setup Required

The UniConnect application is fully built and ready to use. However, you need to set up the MySQL database before you can use all features.

### Prerequisites

Since you mentioned using **XAMPP with MySQL on port 3307**, please follow these steps:

## Step 1: Start XAMPP MySQL

1. Open XAMPP Control Panel
2. Start the MySQL service
3. Ensure MySQL is running on **port 3307** (as you specified)

## Step 2: Create the Database

Once MySQL is running, execute the database setup script:

```bash
php setup_database.php
```

This will:
- Create the `uniconnect` database
- Create all necessary tables (users, books, requests, messages, admin_logs)
- Insert a default admin account

## Step 3: Default Admin Login

After database setup is complete, you can login with:

- **Email**: admin@uniconnect.edu.et
- **Password**: admin123

⚠️ **Important**: Change the admin password after first login!

## Alternative: Manual Database Setup

If the automated setup doesn't work, you can manually run the SQL file:

1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `uniconnect`
3. Import the file: `database/schema.sql`

## Database Configuration

The database settings are in `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');  // Your XAMPP MySQL port
define('DB_NAME', 'uniconnect');
define('DB_USER', 'root');
define('DB_PASS', '');
```

If your MySQL settings are different, update this file accordingly.

## Testing the Application

1. Ensure MySQL is running on port 3307
2. Run the database setup: `php setup_database.php`
3. Access the application at the provided URL
4. Register a new student account (use email ending in .edu.et)
5. Login and start uploading books!

## Troubleshooting

### Error: "No such file or directory"
- MySQL is not running or not accessible on port 3307
- Start XAMPP MySQL service

### Error: "Access denied"
- Check your MySQL username/password in `config/database.php`
- Default XAMPP uses username: `root`, password: (empty)

### Error: "Database already exists"
- This is normal on subsequent runs
- The setup script will skip database creation

## Features Available After Setup

✅ User registration with university email validation  
✅ Secure login with "Remember Me" functionality  
✅ Book upload with image support  
✅ Search and filter books  
✅ Send/receive exchange requests  
✅ Messaging system  
✅ Admin panel for approvals  
✅ User and book management  
✅ Statistical reports  

## Need Help?

If you encounter any issues:

1. Check that XAMPP MySQL is running
2. Verify the port is 3307
3. Check the error logs in the `logs/` directory
4. Review the database configuration in `config/database.php`

Enjoy using UniConnect! 🎓📚
