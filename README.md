# Event Booking System — Laravel Backend

## Tech Stack
- **Framework**: Laravel 11
- **Auth**: Laravel Sanctum (token-based)
- **Database**: MySQL 8+ / MariaDB
- **Queue**: Database (or Redis)
- **Cache**: File (or Redis)

---

## 🚀 Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/iis1155/event-booking.git
cd event-booking
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and adjust to your local database username and password:
```env
APP_NAME="Event Booking System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_booking
DB_USERNAME=root          # ← change to your MySQL username
DB_PASSWORD=yourpassword  # ← change to your MySQL password

CACHE_DRIVER=file
QUEUE_CONNECTION=database
MAIL_MAILER=log
SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 4. Create the Database
Open MySQL Workbench, HeidiSQL, or any MySQL client and run:
```sql
CREATE DATABASE event_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or via MySQL CLI:
```bash
mysql -u root -p -e "CREATE DATABASE event_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run Migrations & Seed
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

### 6. Start Queue Worker
Open a **second terminal** and run (required for notifications):
```bash
php artisan queue:work
```

### 7. Start the Server
```bash
php artisan serve
```

API available at: `http://localhost:8000/api/v1`

---

## 🔐 Seeded Credentials (all passwords: `password`)

| Role      | Email                        |
|-----------|------------------------------|
| Admin     | admin@eventbooking.test      |
| Admin     | admin2@eventbooking.test     |
| Organizer | organizer1@eventbooking.test |
| Organizer | organizer2@eventbooking.test |
| Organizer | organizer3@eventbooking.test |
| Customer  | customer1@eventbooking.test  |

---

## 📬 Postman Collection

1. Open Postman
2. Click **Import** → select `postman_collection.json` from project root
3. The `{{token}}` variable is auto-saved after login — no manual copy needed
4. Use **Login (Admin)**, **Login (Organizer)**, or **Login (Customer)** to switch roles

---

## 🌐 API Endpoints

### Auth
| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| POST | /api/v1/auth/register | No | - |
| POST | /api/v1/auth/login | No | - |
| POST | /api/v1/auth/logout | Yes | Any |
| GET | /api/v1/me | Yes | Any |

### Events
| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| GET | /api/v1/events | No | - |
| GET | /api/v1/events/{id} | No | - |
| POST | /api/v1/events | Yes | Organizer, Admin |
| PUT | /api/v1/events/{id} | Yes | Organizer, Admin |
| DELETE | /api/v1/events/{id} | Yes | Organizer, Admin |

### Tickets
| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| POST | /api/v1/events/{id}/tickets | Yes | Organizer, Admin |
| PUT | /api/v1/tickets/{id} | Yes | Organizer, Admin |
| DELETE | /api/v1/tickets/{id} | Yes | Organizer, Admin |

### Bookings
| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| POST | /api/v1/tickets/{id}/bookings | Yes | Customer |
| GET | /api/v1/bookings | Yes | Customer |
| PUT | /api/v1/bookings/{id}/cancel | Yes | Customer |
| GET | /api/v1/admin/bookings | Yes | Admin |

### Payments
| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| POST | /api/v1/bookings/{id}/payment | Yes | Customer |
| GET | /api/v1/payments/{id} | Yes | Any |

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
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── BookingController.php
│   │   ├── EventController.php
│   │   ├── PaymentController.php
│   │   └── TicketController.php
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   └── PreventDoubleBooking.php
│   └── Requests/
│       ├── Auth/
│       ├── Booking/
│       ├── Event/
│       └── Ticket/
├── Models/
│   ├── Booking.php
│   ├── Event.php
│   ├── Payment.php
│   ├── Ticket.php
│   └── User.php
├── Notifications/
│   └── BookingConfirmed.php
├── Services/
│   └── PaymentService.php
└── Traits/
    ├── ApiResponse.php
    └── CommonQueryScopes.php

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
│   ├── AuthTest.php
│   ├── BookingPaymentTest.php
│   └── EventTest.php
└── Unit/
    └── PaymentServiceTest.php
```

---

## 🧪 Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage --min=85
```

Expected output:
```
Tests: 44 passed (98 assertions)
```

---

## ✅ Seeded Data Summary

| Entity   | Count | Details |
|----------|-------|---------|
| Users    | 15    | 2 admins, 3 organizers, 10 customers |
| Events   | 5     | All published, upcoming dates |
| Tickets  | 15    | 3 tiers per event (VIP, Standard, Economy) |
| Bookings | 20    | 10 confirmed, 7 pending, 3 cancelled |
| Payments | 10    | One per confirmed booking |
