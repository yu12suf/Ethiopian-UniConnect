# UniConnect - University Book & Material Exchange Platform

## Overview

UniConnect is a PHP-based web application designed for Ethiopian university students to exchange, borrow, donate, and sell academic books and materials. The platform serves as a secure, organized alternative to informal sharing methods like Telegram groups, connecting students across universities to facilitate educational resource sharing within campus communities.

The application demonstrates core PHP development practices including object-oriented programming, secure database management, session-based authentication, and file handling for book images and documents.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Backend Architecture

**Core Framework**: Pure PHP 8.2 with Object-Oriented Programming
- **Design Pattern**: Singleton pattern implemented for database connections to ensure single instance management
- **Class Structure**: 
  - `Database` - Handles database connectivity and PDO instance management
  - `User` - Manages user authentication, registration, and profile operations
  - `Book` - Handles book listing creation, updates, and retrieval
  - `Request` - Manages exchange requests between users
  - `Message` - Facilitates communication system between students
  - `Admin` - Handles administrative functions and system monitoring

**Rationale**: OOP structure chosen for code reusability, maintainability, and clear separation of concerns. Pure PHP approach avoids framework overhead while maintaining professional development standards.

### Frontend Architecture

**Technology Stack**: HTML5, CSS3, Bootstrap 5, JavaScript
- Bootstrap 5 provides responsive grid system and pre-built components
- Custom CSS for brand-specific styling and enhanced user experience
- Vanilla JavaScript for client-side interactivity

**Rationale**: Bootstrap chosen for rapid UI development and mobile responsiveness. Avoiding heavy frontend frameworks keeps the application lightweight and accessible for students with varying internet speeds.

### Authentication & Authorization

**Session Management**: PHP native sessions for user state management
- Session-based authentication for logged-in users
- Cookie support for "Remember Me" functionality with secure token generation
- Session security best practices including regeneration and timeout handling

**Access Control**: 
- Two-tier user system: Regular users and Administrators
- University email validation (.edu.et domain) for registration
- Role-based access control for admin-specific features

**Rationale**: Native PHP sessions chosen over third-party solutions for simplicity and direct control. University email validation ensures only legitimate students can register, maintaining platform integrity.

### Data Layer

**Database**: MySQL
- **Port Configuration**: 3307 (customized for XAMPP compatibility)
- **Access Method**: PDO (PHP Data Objects) with prepared statements
- **Security**: All queries use prepared statements to prevent SQL injection

**Schema Design**:
- `users` - Student accounts with profile information
- `books` - Book listings with metadata (title, author, department, course, condition)
- `requests` - Exchange requests between users
- `messages` - Communication system for coordination
- `admin_logs` - Administrative action tracking

**Relationships**:
- Foreign key constraints maintain referential integrity
- One-to-many relationships between users and books/requests/messages
- Database cascading for data consistency

**Rationale**: MySQL selected for reliability, widespread hosting support, and compatibility with XAMPP development environment. PDO abstraction layer provides database portability and enhanced security over mysqli.

### File Storage

**Upload Management**: PHP native file handling
- Image uploads for book covers stored in filesystem
- File validation for type, size, and security
- Directory structure for organized storage

**Security Measures**:
- File type validation to prevent malicious uploads
- Size limits to prevent storage abuse
- Generated unique filenames to prevent conflicts

**Rationale**: Filesystem storage chosen over database BLOB storage for better performance and easier backup management. Direct file handling provides full control over validation and security.

### Search & Filtering System

**Implementation**: Server-side filtering with dynamic SQL query building
- Multi-criteria search (title, author, course, department)
- Filter by exchange type (borrow, buy, donate)
- Filter by material condition

**Rationale**: Server-side approach ensures data security and handles large datasets efficiently without client-side performance issues.

## External Dependencies

### Third-Party Libraries

**Bootstrap 5**: Frontend CSS framework
- **Purpose**: Responsive layout system, pre-built UI components
- **Integration**: CDN-based delivery for faster loading
- **Usage**: Grid system, cards, modals, forms, navigation components

### Development Environment

**XAMPP Stack**: Local development environment
- **Apache**: Web server
- **MySQL**: Database server (configured on port 3307)
- **PHP**: Server-side scripting (version 8.2)
- **Rationale**: XAMPP chosen for ease of setup and cross-platform compatibility, standard choice for PHP development in educational contexts

### Email Validation

**University Domain Requirement**: .edu.et email addresses
- **Purpose**: Restrict registration to Ethiopian university students
- **Implementation**: Server-side validation during registration
- **Rationale**: Ensures platform is used by legitimate students, maintains community integrity

### Database Management

**phpMyAdmin**: Database administration interface
- **Purpose**: Database creation, schema management, data viewing
- **Access**: Bundled with XAMPP, typically accessible via localhost
- **Usage**: Manual database setup alternative, development debugging

### No External APIs

The application is designed to be self-contained without external API dependencies, ensuring reliability and privacy for student data. All functionality is handled within the application stack.