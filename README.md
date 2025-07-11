# Library Management System

A complete PHP-based Library Management System with MySQL database, featuring a modern responsive interface and comprehensive functionality for managing books, members, and borrowings.

## Features

### 📚 Book Management
- Add, edit, and delete books
- Search and filter books by title, author, ISBN, or category
- Track book availability and quantities
- Manage book categories and publication details

### 👥 Member Management
- Add, edit, and delete library members
- Search and filter members by name, email, or phone
- Track member status (active/inactive)
- Manage member contact information

### 🔄 Borrowing System
- Borrow books to active members
- Return books with automatic fine calculation
- Track due dates and overdue books
- View borrowing history and statistics

### 📊 Reports & Analytics
- Dashboard with key statistics
- Popular books and categories
- Top borrowers
- Monthly borrowing trends
- Overdue books tracking
- Recent activity monitoring

### 🔐 User Authentication
- Secure login system
- Session management
- Role-based access control

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0
- **Database Driver**: PDO

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PHP extensions: PDO, PDO_MySQL

### Step 1: Database Setup
1. Create a MySQL database named `library_management`
2. Import the database schema:
   ```bash
   mysql -u root -p library_management < database.sql
   ```
   Or use phpMyAdmin to import the `database.sql` file.

### Step 2: Configuration
1. Open `config/database.php`
2. Update the database connection details:
   ```php
   $host = 'localhost';
   $dbname = 'library_management';
   $username = 'your_username';
   $password = 'your_password';
   ```

### Step 3: Web Server Configuration
1. Place all files in your web server directory
2. Ensure the web server has read/write permissions
3. Configure your web server to serve the application

### Step 4: Access the Application
1. Open your web browser
2. Navigate to the application URL
3. Login with default credentials:
   - **Username**: admin
   - **Password**: admin123

## Project Structure

```
library-management/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── header.php            # Common header with navigation
│   └── footer.php            # Common footer with scripts
├── api/
│   ├── get_book.php          # API endpoint for book details
│   └── get_member.php        # API endpoint for member details
├── index.php                 # Dashboard page
├── login.php                 # Login page
├── logout.php                # Logout functionality
├── books.php                 # Books management
├── members.php               # Members management
├── borrowings.php            # Borrowings management
├── reports.php               # Reports and analytics
├── database.sql              # Database schema and sample data
└── README.md                 # This file
```

## Database Schema

### Books Table
- `book_id` (Primary Key)
- `title`, `author`, `isbn`
- `category`, `publication_year`
- `quantity`, `available_quantity`
- `created_at`

### Members Table
- `member_id` (Primary Key)
- `name`, `email`, `phone`, `address`
- `membership_date`, `status`
- `created_at`

### Borrowings Table
- `borrowing_id` (Primary Key)
- `book_id`, `member_id` (Foreign Keys)
- `borrow_date`, `due_date`, `return_date`
- `status`, `fine_amount`
- `created_at`

### Users Table
- `user_id` (Primary Key)
- `username`, `password`, `email`
- `role`, `created_at`

## Usage Guide

### Adding Books
1. Navigate to Books → Add New Book
2. Fill in book details (title, author, ISBN, etc.)
3. Set quantity and category
4. Click "Add Book"

### Managing Members
1. Go to Members → Add New Member
2. Enter member information
3. Set member status (active/inactive)
4. Save member details

### Borrowing Books
1. Navigate to Borrowings → Borrow Book
2. Select available book and active member
3. Set due date (default: 14 days)
4. Confirm borrowing

### Returning Books
1. Go to Borrowings → Return Book
2. Select the borrowing record
3. System automatically calculates fines if overdue
4. Confirm return

### Viewing Reports
1. Access Reports page for analytics
2. View popular books, categories, and borrowers
3. Check overdue books and recent activity
4. Monitor monthly borrowing trends

## Security Features

- **SQL Injection Prevention**: Uses PDO prepared statements
- **XSS Protection**: HTML escaping for user input
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation for all forms
- **Password Hashing**: Bcrypt password hashing

## Customization

### Adding New Features
1. Create new PHP files for additional functionality
2. Add navigation links in `includes/header.php`
3. Update database schema if needed
4. Test thoroughly before deployment

### Styling
- Modify CSS in `includes/header.php`
- Update Bootstrap classes for layout changes
- Customize color scheme and branding

### Database Modifications
- Backup existing data before schema changes
- Update related PHP code for new fields
- Test all functionality after changes

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Errors**
   - Check file permissions for web server
   - Ensure PHP has write access to session directory

3. **Page Not Found**
   - Verify web server configuration
   - Check file paths and URLs
   - Ensure .htaccess is properly configured

4. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies if needed

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section
- Review the documentation

## Changelog

### Version 1.0.0
- Initial release
- Complete CRUD operations for books and members
- Borrowing and returning functionality
- Reports and analytics
- Responsive design with Bootstrap 5
- Secure authentication system

---

**Note**: This is a demonstration project. For production use, implement additional security measures, error logging, and backup systems. #   L i b r a r y - M a n a g e m e n t - S y s t e m  
 