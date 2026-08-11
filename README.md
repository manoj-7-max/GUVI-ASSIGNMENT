# Secure User Registration, Login & Profile System

An internship assessment project implementing a robust, responsive web application for managing user authentication and profiles securely.

## 1. Project Overview
This project provides a clean registration, login, and profile update flow. To fulfill strict performance and architectural constraints, it coordinates three database engines:
- **MySQL**: Relational data store for persistent user profile details.
- **Redis**: Session storage mapping secure random tokens to active user scopes.
- **MongoDB**: Audit database for login activity, registration events, and profile modification history.

## 2. Features
- Glassmorphic, responsive, and mobile-friendly dark theme.
- Interactive Bootstrap 5 forms with live validity classes.
- Password visibility toggles.
- Real-time client-side validation paired with security-focused backend validation.
- Secure session tokens stored exclusively in browser LocalStorage.
- Fully asynchronous communication via jQuery AJAX (no standard HTML form submissions or fetch/Axios).

## 3. Tech Stack
- **Frontend**: HTML5, CSS3, JavaScript, jQuery, Bootstrap 5.
- **Backend**: Native PHP (JSON APIs).
- **Databases**: MySQL, Redis, MongoDB.
- **Exclusions (Strictly Avoided)**: React, Vue, Angular, Node.js, Express, Laravel, PHP Sessions (`$_SESSION`), standard form submissions, `fetch()`, and `Axios`.

## 4. Folder Structure
```text
project-root/
├── assets/
│   ├── images/
│   └── icons/
├── css/
│   └── style.css
├── js/
│   ├── common.js
│   ├── register.js
│   ├── login.js
│   └── profile.js
├── php/
│   ├── config/
│   │   ├── env.php
│   │   ├── mysql.php
│   │   ├── redis.php
│   │   └── mongo.php
│   ├── register.php
│   ├── login.php
│   ├── profile.php
│   ├── logout.php
│   └── auth.php
├── index.html
├── register.html
├── login.html
├── profile.html
├── database/
│   └── database.sql
├── .env.example
├── .env
├── .gitignore
└── README.md
```

## 5. Requirements
- PHP 8.x
- Apache (or Nginx)
- MySQL / MariaDB
- Redis Server
- MongoDB Server
- PHP Extensions enabled: `pdo_mysql`, `redis`, `mongodb`

## 6. MySQL Setup
1. Open your MySQL client (e.g., phpMyAdmin, command line, or Workbench).
2. Create the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS `internship_auth`;
   ```
3. Import the schema using `database/database.sql`:
   ```bash
   mysql -u root -p internship_auth < database/database.sql
   ```

## 7. MongoDB Setup
1. Ensure your MongoDB daemon (`mongod`) is running.
2. The application automatically accesses or creates a database named `internship_app` with collections:
   - `registration_logs`
   - `login_logs`
   - `profile_update_logs`

## 8. Redis Setup
1. Start your local Redis Server:
   ```bash
   redis-server
   ```
2. By default, the application accesses Redis on `127.0.0.1:6379`.

## 9. PHP Configuration
Ensure the following lines are present and uncommented in your active `php.ini` file:
```ini
extension=pdo_mysql
extension=redis
extension=mongodb
```
Restart your Apache/web server after updating `php.ini`.

## 10. How to Run Using XAMPP/WAMP/LAMP
1. Copy the entire `project-root` folder into your web server's public directory (e.g., `C:/xampp/htdocs/hcl/`).
2. Start Apache and MySQL from your Control Panel.
3. Open your browser and navigate to:
   `http://localhost/hcl/index.html`

## 11. How to Configure Environment Variables
1. Rename `.env.example` to `.env`.
2. Open `.env` and fill in your local system credentials:
   ```ini
   MYSQL_HOST=127.0.0.1
   MYSQL_DATABASE=internship_auth
   MYSQL_USERNAME=root
   MYSQL_PASSWORD=yourpassword

   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_PASSWORD=

   MONGO_URI=mongodb://127.0.0.1:27017
   MONGO_DATABASE=internship_app
   ```

## 12. API Endpoints
All backend responses return standard JSON.

### Register Account
- **Endpoint**: `php/register.php`
- **Method**: `POST`
- **Payload**: `full_name`, `email`, `mobile`, `password`, `confirm_password`

### Login
- **Endpoint**: `php/login.php`
- **Method**: `POST`
- **Payload**: `email`, `password`

### Profile
- **Endpoint**: `php/profile.php`
- **Method**: `POST`
- **Payload (Read)**: `action=get_profile`, `token`
- **Payload (Update)**: `action=update_profile`, `token`, `full_name`, `mobile`, `age`, `date_of_birth`, `address`

### Logout
- **Endpoint**: `php/logout.php`
- **Method**: `POST`
- **Payload**: `token`

## 13. Authentication Flow
```
Browser LocalStorage (auth_token)
          ↓
  Ajax Request (token)
          ↓
  PHP reads token
          ↓
  Check key in Redis (auth_session:{token})
          ↓
  Retrieve associated user_id
          ↓
  Load/Update profile in MySQL using user_id
```

## 14. Security Implementation
- **SQL Injection Prevention**: Every MySQL query uses PDO prepared statements with parameter binding.
- **XSS Mitigation**: Values loaded from databases are safely inserted as text via jQuery (`.val()`, `.text()`).
- **No PHP Sessions**: The backend checks token validity in Redis on each request, resolving browser-side user ID manipulation.
- **Password Protection**: Passwords are saved as bcrypt hashes using PHP's native `password_hash()`. The plaintext password is never logged, stored in LocalStorage, or cached in Redis.

## 15. Testing Instructions
1. **Registration Check**: Submit the form with mismatching passwords, invalid emails, or short passwords to verify validation. Register a user and attempt to re-register the same email.
2. **Login Check**: Log in with wrong credentials to verify the generic warning. Log in successfully and check if `auth_token` appears in DevTools > Application > Local Storage.
3. **Authorization Check**: Attempt to open `profile.html` directly in a private tab. Check if you are redirected to `login.html`.
4. **Update Check**: Edit the fields, save, and reload the page to see the saved fields in the database.
5. **MongoDB Verification**: View your local MongoDB logs to verify logging of registrations, logins, and field updates.

## 16. GitHub & Vercel Deployment Guide

To deploy this project to production:

### GitHub Setup
1. Create a new repository on your GitHub account.
2. Initialize git locally, commit files, and push to GitHub:
   ```bash
   git init
   git add .
   git commit -m "Initial commit of Auth System"
   git branch -M main
   git remote add origin https://github.com/your-username/your-repo-name.git
   git push -u origin main
   ```

### Vercel Deployment
1. Go to [Vercel](https://vercel.com) and sign in using your GitHub account.
2. Click **Add New** > **Project** and import your GitHub repository.
3. Vercel will automatically detect `vercel.json` and prepare the build.
4. **Important**: Configure Environment Variables in the project settings on Vercel:
   - Add `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USERNAME`, and `MYSQL_PASSWORD` matching your production database details (e.g. AWS RDS or Aiven).
   - Add `REDIS_HOST`, `REDIS_PORT`, and `REDIS_PASSWORD` pointing to a managed Redis instance (e.g. Upstash Redis).
   - Add `MONGO_URI` and `MONGO_DATABASE` pointing to a managed MongoDB instance (e.g. MongoDB Atlas).
5. Click **Deploy**. Vercel will build the project and serve your HTML static assets alongside your PHP backend endpoints.

## 17. Screenshots Section Placeholder
*(Attach visual screenshots showing Registration, Login, Profile View, and Profile Edit states here)*
