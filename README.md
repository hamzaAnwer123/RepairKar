# 🔧 RepairKar

**RepairKar** is a local services marketplace for Pakistan that connects people with **trusted, verified mechanics** for appliance repair, car & bike service, electrical work, and plumbing. Users can find nearby mechanics on a live map, chat with them in real time, place voice calls, and book services — while mechanics manage Fiverr-style service listings ("gigs"), job requests, earnings, and reviews.

> 🌐 Live site: [https://repairkar.site.je/](https://repairkar.site.je/)

---

## ✨ Features

### 👤 Customers
- 🔎 **Search & discovery** — browse mechanics by category and city
- 🗺️ **Live map** — see nearby mechanics in real time (Leaflet + OpenStreetMap)
- 📅 **Bookings** — request a service and track status: `pending → accepted → en_route → completed`
- 💬 **Real-time chat** — message mechanics directly
- 📞 **Voice calling** — WebRTC-based in-app calls
- ⭐ **Reviews & ratings** — rate completed jobs

### 🔧 Mechanics
- 🏪 **Shop profile** — bio, address, CNIC document, shop photo, verification
- 🧾 **Gigs** — create Fiverr-style service listings with price ranges (min/max)
- 📥 **Job requests** — accept, track, and complete bookings
- 📍 **Live location sharing** — update position so customers can track you
- 💰 **Earnings dashboard** — income and activity overview

### 🛡️ Admin
- 👥 **User & mechanic management** — verify or reject mechanic applications (with rejection reasons)
- 📊 **Dashboard** — bookings, gigs, reviews, messages, and platform activity
- 🔑 **Bootstrap admin account** — optionally synced from `.env` on every login

### 🔐 Platform-wide
- Phone **or** email signup (exactly one required, enforced in DB and API)
- Password hashing (`password_hash`), login throttling & temporary account lockout
- PDO **prepared statements everywhere**, session-based auth with `includes/auth-check.php`
- Never exposes DB errors to the browser

---

## 🛠️ Tech Stack

| Layer      | Technology |
|------------|------------|
| Backend    | PHP 8+ (plain PHP, no framework, no Composer) |
| Database   | MySQL 8.0+ / MariaDB 10.2+ (InnoDB, utf8mb4) via **PDO** |
| Frontend   | Static HTML + Tailwind CSS v3, Inter font, Font Awesome 6 |
| Maps       | Leaflet 1.9.4 + OpenStreetMap |
| Calls      | WebRTC (signaling via `api/call-signal.php`) |
| Config     | `.env` file parsed by a small built-in parser (`config/config.php`) |
| Build      | npm + Tailwind CLI (`tailwindcss`) |

---

## 📁 Project Structure

```
RepairKar/
├── index.html              # Landing page (SEO, Open Graph, JSON-LD)
├── login.html              # Auth pages (signup, forgot-password, etc.)
├── about.html              # Public info pages (contact, careers, terms…)
├── user/                   # Customer app pages (dashboard, booking, chat, live-map…)
├── mechanic/               # Mechanic app pages (dashboard, gigs, jobs, earnings…)
├── admin/                  # Admin panel (PHP: users, mechanics, bookings, reviews…)
├── api/                    # PHP REST-style endpoints (see below)
├── includes/               # db.php (PDO), functions.php, auth-check.php, admin layouts
├── config/config.php       # .env loader → constants
├── database/schema.sql     # Full DB schema (9 tables)
├── assets/
│   ├── css/                # input.css → tailwind.min.css (built), loader, navbar
│   ├── js/                 # loader.js, navbar.js
│   ├── images/             # Logos, icons, og-image
│   └── uploads/            # User-uploaded profile/shop photos
├── .env.example            # Environment template
└── tailwind.config.js      # Brand colors + content paths
```

**Database tables:** `users`, `mechanics`, `gigs`, `bookings`, `messages`, `call_signals`, `calls`, `contact_messages`, `reviews`

---

## 🚀 Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (or WAMP/Laragon) with **PHP 8+** and **MySQL**
- [Node.js](https://nodejs.org/) (only needed to rebuild Tailwind CSS)
- Git

### 1. Clone the project
```bash
git clone https://github.com/hamzaAnwer123/RepairKar.git
cd RepairKar
```
Place it inside your web root, e.g. `C:\xampp\htdocs\RepairKar`.

### 2. Configure the environment
```bash
cp .env.example .env    # Windows: copy .env.example .env
```
Then fill in your values:
```ini
DB_HOST=localhost
DB_NAME=repairkar
DB_USER=root
DB_PASS=                      # blank is fine for default XAMPP MySQL
BASE_URL=http://localhost/RepairKar

; ---- Optional: admin bootstrap account ----
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@repairkar.pk
ADMIN_PASSWORD=StrongPassword123
```

### 3. Create the database
Start **Apache** and **MySQL** from the XAMPP control panel, then import the schema:
- Via phpMyAdmin: create a database named `repairkar` → **Import** → select `database/schema.sql`
- Or via CLI:
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS repairkar CHARACTER SET utf8mb4;"
mysql -u root repairkar < database/schema.sql
```

### 4. Run it
Open your browser at:
```
http://localhost/RepairKar/
```
- Public site → `index.html`
- Admin panel → `http://localhost/RepairKar/admin/index.php`
- The app is also fully usable through Apache; PHP handles `/api/*` endpoints and the admin panel.

### 5. (Optional) Rebuild Tailwind CSS
Only needed when you edit styles or HTML class names:
```bash
npm install
npm run watch:css   # development (auto-rebuild)
npm run build:css   # production (minified)
```

---

## 🔌 API Overview

All endpoints live in `/api` and speak JSON. Highlights:

| Area | Endpoints |
|------|-----------|
| Auth | `register.php`, `login.php`, `logout.php`, `current-user.php`, `change-password.php`, `delete-account.php` |
| Users | `get-user-profile.php`, `update-profile.php`, `get-user-bookings.php`, `get-user-stats.php`, `upload-profile-photo.php`, `remove-profile-photo.php` |
| Mechanics | `get-mechanics.php`, `get-nearby-mechanics.php`, `get-mechanic-profile.php`, `update-mechanic-profile.php`, `get-mechanic-dashboard.php`, `get-mechanic-earnings.php`, `get-mechanic-jobs.php`, `get-mechanic-activity.php`, `ping-online.php`, `update-live-location.php` |
| Gigs | `create-gig.php`, `get-my-gigs.php`, `update-gig-status.php` |
| Bookings | `create-booking.php`, `get-booking-requests.php`, `get-booking-detail.php`, `get-booking-status.php`, `update-booking-status.php`, `get-mechanic-accepted.php` |
| Chat & Calls | `get-conversations.php`, `get-messages.php`, `send-message.php`, `mark-messages-read.php`, `call-signal.php`, `log-call.php` |
| Reviews | `submit-review.php`, `get-my-reviews.php`, `delete-review.php` |
| Misc | `submit-contact.php` |

---

## 🔒 Security Notes

- All SQL goes through **PDO prepared statements** (`includes/db.php` disables emulate-prepares).
- Passwords are hashed with PHP's native bcrypt (`password_hash`).
- Login attempts are rate-limited with `failed_attempts` / `locked_until` columns.
- Uploaded files live under `assets/uploads/` and are validated server-side.
- `.env` and real credentials are git-ignored — never commit them.

---

## 🤝 Contributing

1. Fork the repo and create a feature branch: `git checkout -b feature/my-feature`
2. Commit your changes: `git commit -m "Add my feature"`
3. Push and open a Pull Request.

---

## 📄 License

This project is private and proprietary. © RepairKar — All rights reserved.

---

<p align="center">Made Hamza Anwar</p>
