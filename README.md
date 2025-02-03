Event Management System

## Description
This is a simple web-based Event Management System developed using pure PHP and MySQL. It allows users to create, manage, and view events, as well as register as attendees and generate event reports. The system is designed with a focus on security, usability, and maintainability.

## Features

### Core Features
- **User Authentication**
  - Secure registration with **Argon2id** password hashing.
  - Smooth login & logout experience with **session management**.
  - CSRF protection for authentication actions.
- **Event Management**
  - Users can reate, edit, delete, and view events.
  - Users can define **title, description, date, location, and capacity**.
  - **Permissions-based access** to prevent unauthorized modifications.
- **Attendee Registration**
  - Hassle-free event registration with **capacity limits**.
  - Option to **cancel registrations** easily.
- **Event Dashboard**
  - **Paginated, sortable, and filterable** event listings.
- **Event Reports**
  - **CSV export functionality** for admins to manage attendee data easily.

### Additional Features
- **Search functionality**: Find events by **title, description, and location**.
- **AJAX-Powered Interactions**: Smooth user experience **registration, event updates, and cancellations**.
- **JSON API**: Retrieve event details dynamically via API endpoints.

## Tech Stack
- **Backend**: PHP (pure, procedural & PDO)
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5

## Codebase Structure (Tree)
```
event-management-system/
├── AJAX/                          # Handles AJAX-based interactions
│   ├── event_operations.php       # Handles event updates and deletion via AJAX
│   └── event_registration.php     # Manages event registrations and cancellations dynamically
├── api/                           # API endpoints for external data access
│   └── events.php                 # JSON API for retrieving event details securely
├── assets/                        # Static frontend assets (CSS, JS)
│   ├── css/
│   │   └── style.css              # Custom styles and Bootstrap overrides
│   └── js/
│       └── main.js                # JavaScript for AJAX, search, and form validation
├── config/                        # Configuration files
│   └── database.php               # Secure database connection using PDO
├── includes/                      # Reusable components and functions
│   ├── auth.php                   # Handles user authentication and session management
│   ├── footer.php                 # Common footer section with JavaScript imports
│   ├── functions.php               # Utility functions for security, validation, and sanitization
│   └── header.php                 # Common header with navigation bar and authentication checks
├── admin_dashboard.php            # Admin dashboard displaying event stats and management tools
├── create_event.php               # Allows authenticated users to create new events
├── event_details.php              # Displays event details and enables registration (via AJAX)
├── events_reports.php             # Admin-only page for generating event reports and exporting data
├── events.php                     # Paginated, filterable list of events with AJAX-powered search
├── index.php                      # Homepage displaying upcoming and featured events
├── login.php                      # User login page with authentication checks
├── logout.php                     # Logs out the user and destroys the session
├── register.php                   # User registration page with input validation and hashing
└── search.php                     # Allows users to search for events and attendees dynamically

```

## Installation Guide
### Local Setup (XAMPP)
1. **Download & Install XAMPP** ([Click here](https://www.apachefriends.org/download.html))
2. **Clone or download the project**
   - Place files inside `htdocs` (`C:\xampp\htdocs\event-management-system`)
3. **Create a Database**
   - Open `phpMyAdmin` (`http://localhost/phpmyadmin/`)
   - Create a new database: `event_management`
   - Import `event_management.sql` if available.
4. **Configure Database**
   - Edit `config/database.php` and set your credentials:
   ```php
   $db_host = 'localhost';
   $db_user = 'root';
   $db_pass = '';
   $db_name = 'event_management';
   ```
5. **Run the Application**
   - Open `http://localhost/event-management-system/` in your browser.


## Live Demo
[🔗 eventmanagement.wuaze.com]

## Test Login Credentials
**Regular User:**
- **Email:** `test@example.com`
- **Password:** `Test123@`

**Admin User:**
- **Email:** `admin@example.com`
- **Password:** `Admin123@`





