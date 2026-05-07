# MediCore HMS v2 — Professional Edition

## How to Execute this System

### Prerequisites
- XAMPP (Apache, PHP, MySQL)
- Web browser (Chrome, Firefox, Edge, Safari)

### Installation Steps
1. Copy `hms/` folder to `C:\xampp\htdocs\`
2. Start Apache and MySQL from XAMPP Control Panel
3. Import `database.sql` in phpMyAdmin (http://localhost/phpmyadmin)
4. Visit http://localhost/hms/ in your browser

## How this System Works

### Architecture
- **Backend**: PHP 7.4+ with PDO for database operations
- **Frontend**: Bootstrap 5.3 with vanilla JavaScript
- **Database**: MySQL with relational schema for patients, doctors, appointments, consultations, and prescriptions

### User Roles
- **Administrator**: Manage doctors, patients, appointments, view consultations and prescriptions
- **Doctor**: View assigned consultations, create prescriptions, manage patient records

### Main Features
- User authentication (login/registration)
- Patient management and history tracking
- Doctor scheduling and consultation records
- Appointment booking with conflict detection
- Prescription management
- Real-time notification system

## Credentials
| Role   | Email               | Password  |
|--------|---------------------|-----------|
| Admin  | admin@hms.com       | admin123  |
| Doctor | dr.santos@hms.com   | doctor123 |
| Doctor | dr.reyes@hms.com    | doctor123 |
