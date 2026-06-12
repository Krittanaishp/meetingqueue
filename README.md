# Meeting Queue Demo

ระบบจองห้องประชุมออนไลน์ (Demo) — PHP + MySQL + XAMPP  
Hospital meeting room booking system demo, built for Apache/PHP without Node.js or a build step.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
![License](https://img.shields.io/badge/License-MIT-green)

## Features

- Login with username + password (demo accounts included)
- Calendar view (month / week / day / list)
- Book meeting rooms with approval workflow
- External meeting records
- Room status dashboard
- Reports, statistics, reviews
- Admin: user management, backup/restore, trash

## Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.0+)
- Web browser

## Quick Start

1. Clone into XAMPP htdocs:

```bash
git clone https://github.com/kritsakornporsche/meetingqueue-demo.git
cd meetingqueue-demo
```

Or copy the folder to `C:\xampp\htdocs\meetingqueue-demo`

2. Start **Apache** and **MySQL** in XAMPP Control Panel.

3. Open setup in your browser:

```
http://localhost/meetingqueue-demo/setup_demo.php
```

This creates the database, tables, demo users, and sample bookings.

4. Open the login page:

```
http://localhost/meetingqueue-demo/
```

## Demo Accounts

| Role  | Username   | Password        |
|-------|------------|-----------------|
| Admin | `admin`    | `admin`         |
| User  | `Somchai`  | `1234567890123` |
| User  | `Somsak`   | `1111111111111` |

## Configuration

Default settings use local XAMPP MySQL:

| Setting  | Default            |
|----------|--------------------|
| DB_HOST  | `localhost`        |
| DB_USER  | `root`             |
| DB_PASS  | *(empty)*          |
| DB_NAME  | `meetingqueue_db`  |

Edit `api/config.php` or set environment variables. See `api/config.example.php`.

ZK BioTime HR sync is **disabled** in demo mode. Use `seed_users.php` to reset demo users.

## Project Structure

```
meetingqueue-demo/
├── index.php          # Login
├── dashboard.php      # Main app
├── setup_demo.php     # One-click demo setup
├── schema.sql         # Database schema
├── api/               # REST-style endpoints
├── views/             # Page templates
├── includes/          # Header, sidebar
├── css/  js/          # Static assets
└── uploads/           # Runtime uploads (writable)
```

## Manual Setup (optional)

```bash
# 1. Import schema via browser
http://localhost/meetingqueue-demo/setup_db.php

# 2. Seed users
http://localhost/meetingqueue-demo/seed_users.php

# 3. Generate sample bookings
http://localhost/meetingqueue-demo/mock_bookings.php
```

## Production Notes

This repository is a **sanitized demo**. It does not include:

- Hospital network IPs or credentials
- Real employee data
- ZK BioTime integration (enable via `ZK_ENABLED=true` in config)

For the full hospital deployment, use the private production repository.

## License

MIT — see [LICENSE](LICENSE).

## Credits

Developed for hospital meeting room management. Demo version for GitHub showcase.
