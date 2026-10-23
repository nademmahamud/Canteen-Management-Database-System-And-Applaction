================================================================================
  CAMPUS CANTEEN MANAGEMENT SYSTEM (CCMS)
  A Smart Solution for Modern Canteen Operations
================================================================================

PROJECT DETAILS
---------------
Course: Database Management System (DBMS) Project
Presented by:
  - Abrar Abdullah (202353080124)
  - Sagor Anup Chandra (202353080061)
  - Mahamud Nadem (202353080023)
  - Bukhari Syed Ghufran (202353080181)
  - Kasmi Manal (202353080165)
Year: 2026

TECHNOLOGY STACK
----------------
- Frontend: HTML5, CSS3, JavaScript (with animations)
- Backend: PHP (Core)
- Database: MySQL 8.0
- Server: Apache Web Server (XAMPP/WAMP)
- Tools: phpMyAdmin, Chart.js for analytics
- CSS Framework: Custom CSS with responsive design

DATABASE STRUCTURE (10 Tables)
------------------------------
1. users        - User accounts (admin, staff, cashier, student)
2. categories   - Food categories
3. foods        - Menu items / food products
4. inventory    - Stock tracking with alerts
5. suppliers    - Supplier information
6. cart         - Shopping cart
7. cart_items   - Cart item details
8. orders       - Order records
9. order_items  - Order line items
10. payments    - Payment transactions
11. addresses   - User addresses
12. activity_logs - System activity auditing

LOGIN CREDENTIALS
-----------------
Admin Login:
  Username: admin
  Password: admin

User Login:
  Username: user
  Password: user

Other Demo Users:
  - abrar@gmail.com / password
  - sagor@gmail.com / password
  - nadem@gmail.com / password
  - ghufran@gmail.com / password
  - manal@gmail.com / password

INSTALLATION INSTRUCTIONS
---------------------------
1. Install XAMPP (if not installed)
   Download from: https://www.apachefriends.org/

2. Copy the CanteenManagement folder
   Copy this entire CanteenManagement folder to:
   C:\xampp\htdocs\
   So the path becomes: C:\xampp\htdocs\CanteenManagement\

3. Start XAMPP Services
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

4. Import the Database
   - Open browser and go to: http://localhost/phpmyadmin
   - Click on "Import" tab
   - Choose File: C:\xampp\htdocs\CanteenManagement\sql\database.sql
   - Click "Go" to import

   OR

   - Create database manually named "canteen_db"
   - The app will auto-create tables on first run (optional)

5. Access the Application
   Open browser and go to:
   http://localhost/CanteenManagement/

   Default login page will appear.

FEATURES
--------
- Multiple User Roles (Admin, Staff, Cashier, Student)
- Digital Food Ordering System
- Menu Management (CRUD operations)
- Order Management with status tracking
- Automated Billing & Payment Processing
- Real-time Inventory Monitoring with Low Stock Alerts
- Supplier Management
- Sales Analytics & Reporting with Charts
- Activity Logging & Auditing
- Responsive Design (Works on mobile, tablet, desktop)
- Dynamic Animated UI
- Role-Based Access Control (RBAC)
- Print Invoices
- Database Normalization (BCNF)

DASHBOARD MODULES
-----------------
1. Dashboard      - Overview with stats, charts, recent orders
2. Users          - Full CRUD for user management (Admin only)
3. Menu Items     - Food/menu management (Admin & Staff)
4. Orders         - Order placement and tracking (All users)
5. Billing        - Payment processing (Admin & Cashier)
6. Inventory      - Stock management with alerts (Admin & Staff)
7. Suppliers      - Supplier management (Admin only)
8. Reports        - Sales analytics & reports (Admin only)
9. Settings       - Profile & password management (All users)

SECURITY FEATURES
-----------------
- Password Hashing (bcrypt)
- Session Management with timeout
- Role-Based Access Control
- SQL Injection Protection (Prepared Statements)
- XSS Protection (Output Encoding)
- CSRF Token Protection
- Activity Logging

TROUBLESHOOTING
---------------
1. If database connection fails:
   - Check includes/db.php for correct DB credentials
   - Default: root / (no password)
   - Make sure MySQL is running in XAMPP

2. If pages show 404:
   - Make sure the folder is named exactly "CanteenManagement"
   - Check that mod_rewrite is enabled in Apache

3. If styles don't load:
   - Clear browser cache (Ctrl+F5)
   - Check that the assets folder is accessible

SUPPORT
-------
For any issues, please contact the project team.

Thank you for using Campus Canteen Management System!
================================================================================
