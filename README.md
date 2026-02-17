# Event Booking System — Laravel Backend

## Tech Stack
- **Framework**: Laravel 11
- **Auth**: Laravel Sanctum (token-based)
- **Database**: MySQL 8+
- **Queue**: Redis (or database for local dev)
- **Cache**: Redis (or file for local dev)

---

## 🚀 Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` adjust to your local username and password:
```env
APP_NAME="Event Booking System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_booking
DB_USERNAME=root
DB_PASSWORD=your_password

CACHE_DRIVER=redis          # or: file
QUEUE_CONNECTION=redis      # or: database
SESSION_DRIVER=database

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 3. Install Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 4. Run Migrations & Seed
```bash
php artisan migrate:fresh --seed
```

Expected output:
```
✅ Users seeded: 2 admins, 3 organizers, 10 customers
✅ Events seeded: 5 events
✅ Tickets seeded: 15 tickets (3 tiers × 5 events)
✅ Bookings seeded: 20 bookings (10 confirmed w/ payments, 7 pending, 3 cancelled)
```

### 5. Start Queue Worker (for notifications)
```bash
php artisan queue:work
```

### 6. Start Server
```bash
php artisan serve
```
API available at: `http://localhost:8000/api/v1`

---

## 🔐 Seeded Credentials (all passwords: `password`)

| Role       | Email                          |
|------------|-------------------------------|
| Admin      | admin@eventbooking.test       |
| Admin      | admin2@eventbooking.test      |
| Organizer  | organizer1@eventbooking.test  |
| Organizer  | organizer2@eventbooking.test  |
| Organizer  | organizer3@eventbooking.test  |
| Customer   | customer1@eventbooking.test   |

---

## 📁 Project Structure

```
app/
├── Enums/
│   ├── BookingStatus.php
│   ├── EventStatus.php
│   ├── PaymentStatus.php
│   ├── TicketType.php
│   └── UserRole.php
├── Http/
│   ├── Controllers/         # Section 3
│   ├── Middleware/          # Section 4
│   └── Requests/            # Section 2 & 3
├── Models/
│   ├── User.php
│   ├── Event.php
│   ├── Ticket.php
│   ├── Booking.php
│   └── Payment.php
├── Services/
│   └── PaymentService.php   # Section 4
├── Traits/
│   └── CommonQueryScopes.php
└── Notifications/
    └── BookingConfirmed.php  # Section 5

database/
├── factories/
├── migrations/
└── seeders/
```

---

## 🧪 Running Tests

```bash
php artisan test
php artisan test --coverage --min=85
```

---

## 📬 Postman Collection
Import `postman_collection.json` from the project root.
Set environment variable `base_url = http://localhost:8000/api/v1`.
