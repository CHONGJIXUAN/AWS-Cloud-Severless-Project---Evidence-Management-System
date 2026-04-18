# Global Scam Report Center

A comprehensive PHP-based web application for reporting and browsing scam reports to help protect communities from fraud.

## Features

### Public/Guest Users
- View approved scam reports
- Search and filter reports by type, date, or keywords
- View detailed report information
- Access static informational pages
- Register for an account

### Registered Users
- All public user features
- Submit new scam reports
- View their own submission history and status
- Track report approval status
- Edit pending reports

### System Administrator
- Manage all user accounts (CRUD operations)
- Manage all scam reports
- Moderate report submissions (approve/reject)
- Access comprehensive admin dashboard
- View system statistics

## 🧰 Technology Stack

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript  
- **Framework**: Bootstrap 5.1.3  
- **Icons**: Font Awesome 6.0.0  

### ☁️ Cloud & AWS Services
- **Storage**: S3 Bucket
- **Database**: :Dyanamo DB
- **Notifications**: :AWS SNS
- **Monitoring**: AWS CloudWatch
- **Deployment**: AWS BeanStalk

## Installation Instructions

### Prerequisites

1. **XAMPP/WAMP/LAMP** or similar local server environment
2. **PHP 7.4 or higher**
3. **MySQL 5.7 or higher**
4. **Web browser** (Chrome, Firefox, Safari, Edge)

### Setup Steps

1. **Download and Extract**
   - Extract the project files to your web server directory
   - For XAMPP: `C:\xampp\htdocs\ddac-website\`
   - For WAMP: `C:\wamp\www\ddac-website\`

2. **Database Setup**
   - Start your MySQL server
   - Access phpMyAdmin or MySQL command line
   - Create a new database named `scam_report_center`
   - Import the SQL schema from `database/schema.sql`
   
   **OR** run the following SQL commands:
   ```sql
   CREATE DATABASE scam_report_center;
   USE scam_report_center;
   
   -- Copy and paste the contents of database/schema.sql
   ```

3. **Database Configuration**
   - Open `config/database.php`
   - Update the database connection settings if needed:
   ```php
   $host = 'localhost';
   $dbname = 'scam_report_center';
   $username = 'root';        // Your MySQL username
   $password = '';            // Your MySQL password
   ```

4. **File Permissions**
   - Ensure web server has read/write access to all project files
   - Set appropriate permissions for your operating system

5. **Access the Application**
   - Start your web server (Apache/Nginx)
   - Open your browser and navigate to:
     - Local: `http://localhost/ddac-website/`
     - Or: `http://127.0.0.1/ddac-website/`

## Default Login Credentials

### Administrator Account
- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@scamreport.com`

### Regular Users
- Register new accounts through the registration page
- All new accounts are created with 'user' role by default

## Project Structure

```
ddac-website/
├── admin/
│   ├── dashboard.php      # Admin dashboard
│   ├── users.php         # User management
│   └── reports.php       # Report management
├── assets/
│   └── css/
│       └── style.css     # Custom styles
├── config/
│   └── database.php      # Database configuration
├── database/
│   └── schema.sql        # Database schema
├── includes/
│   └── functions.php     # Common functions
├── user/
│   ├── dashboard.php     # User dashboard
│   └── submit_report.php # Report submission form
├── index.php             # Homepage
├── login.php             # Login page
├── register.php          # Registration page
├── reports.php           # Browse reports
├── report_details.php    # Detailed report view
├── about.php             # About page
├── logout.php            # Logout functionality
└── README.md             # This file
```

## Key Features Implemented

### Security Features
- CSRF token protection
- Password hashing (PHP password_hash)
- SQL injection prevention (PDO prepared statements)
- Input sanitization and validation
- Session management
- Role-based access control

### User Management
- User registration with validation
- Secure login system
- Profile management
- Account status management (active/suspended)
- Role-based permissions

### Report Management
- Report submission with rich details
- Report status tracking (pending/approved/rejected)
- Content moderation system
- Search and filtering capabilities
- Pagination for large datasets

### Admin Features
- Comprehensive dashboard with statistics
- User account management (CRUD operations)
- Report moderation and approval
- System overview and monitoring

## Usage Instructions

### For End Users

1. **Browse Reports** (No account needed)
   - Visit the homepage
   - Click "Browse Reports" or use the search functionality
   - Filter by scam type or search keywords

2. **Register and Submit Reports**
   - Click "Register" to create an account
   - Fill out the registration form
   - Login with your credentials
   - Click "Submit Report" to add a new scam report
   - Track your submissions in "My Dashboard"

### For Administrators

1. **Login as Admin**
   - Use the default admin credentials
   - Access the admin dashboard

2. **Manage Users**
   - View all registered users
   - Suspend or activate user accounts
   - Delete user accounts if necessary

3. **Moderate Reports**
   - Review pending report submissions
   - Approve legitimate reports for public viewing
   - Reject inappropriate or false reports

### Development Notes

- The application uses PDO for database connections
- Bootstrap 5 is loaded via CDN
- Font Awesome icons are loaded via CDN
- All user inputs are sanitized and validated
- Sessions are used for authentication state management

## Future Enhancements

Potential features for future development:
- Email notifications for report status changes
- Advanced reporting and analytics
- Multi-language support
- File upload for evidence
- API endpoints for mobile app
- Enhanced search with full-text indexing
- User reputation system
- Report commenting and discussion

## Support

For technical support or questions about the application:
- Review this README file
- Check the source code comments
- Verify database schema is properly imported
- Ensure all file permissions are correct

---

**Note**: This is a demonstration application for educational purposes. In a production environment, additional security measures, error handling, and performance optimizations should be implemented.
