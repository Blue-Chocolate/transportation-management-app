# 🚚 **Transportation Management App - Filament Challenge**

## **Table of Contents**

1. [Laravel Project Setup](#laravel-project-setup)  
2. [Manual Installation](#manual-installation)  
3. [Project Summary](#project-summary)  
4. [Database Structure & Relationships](#database-structure--relationships)  
5. [Key Features](#key-features)  
6. [Overlapping Trip Prevention](#overlapping-trip-prevention)  
7. [Dashboard & Widgets](#dashboard--widgets)  

---

## **Laravel Project Setup**

### **Prerequisites**
- **Docker**  
- **Docker Compose**  

### **Getting Started**

```bash
# Clone the repository
git clone https://github.com/Blue-Chocolate/transportation-management-app.git

# Navigate to project
cd transportation-management-app

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### **Build and Start Docker Containers**

```bash
docker-compose up -d --build
```

### **Install Dependencies & Run Migrations**

```bash
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## **Manual Installation**

### **Composer**

```bash
composer install
cp .env.example .env
```

### **Redis 2.0**  
> ⚠️ Open **Bash as Administrator**  
```bash


wsl --install

Install Redis inside WSL (Ubuntu):

sudo apt update
sudo apt install redis-server -y

sudo service redis-server start

# Follow Linux guide to install Redis 2.0
```

### **Tailwind CSS v3.4**

```bash

npm install -g tailwindcss@3.4
tailwindcss -v      # Expected: 3.4.x
tailwindcss init
```

### **Pest v2.0**

```bash
composer require pestphp/pest:^2.0 --dev
php artisan pest:install
vendor\bin\pest --version  # Expected: 2.x
```

---

## **Project Summary**

The **transportation_app** database, implemented using **MariaDB** and managed via **phpMyAdmin**, supports a **transportation management system** for companies (admin users) managing **drivers, vehicles, clients, and trips**.

---

## **Database Structure & Relationships**

### **Entities**

| Entity | Description | Key Attributes |
|--------|-------------|----------------|
| **Users (Admins/Companies)** | Manage drivers, vehicles, clients, trips | `id, name, email, password, role ('admin')` |
| **Clients** | Linked to user via `user_id` | `id, name, email, phone, password`   |
| **Drivers** | Attributes: `id, name, user_id, phone, license, employment_status`  | Linked to user via `user_id` |
| **Vehicles** | Attributes: `id, name, registration_number, vehicle_type, user_id` | Linked to user via `user_id` |
| **Trips** | Track trips | `id, client_id, driver_id, vehicle_id, start_time, end_time, status` |
| **Driver_Vehicle** | Junction table linking drivers and vehicles | `id, driver_id, vehicle_id, user_id` |

### **Relationships**

- **One-to-Many:** Users → Drivers/Vehicles/Clients/Trips  
- **Many-to-Many:** Drivers ↔ Vehicles (driver_vehicle)  
- **One-to-Many (Trips):** Drivers, Vehicles, Clients → Trips  

---

## **Key Features**

### **Prevention of Overlapping Trips**

Implemented across **three layers**:

1. **Database-Level (Fail-Safe)**  
   - SQL triggers (`trips_before_insert`, `trips_before_update`)  
   - Rejects conflicting trips for drivers/vehicles  

2. **Form-Level (Real-Time Feedback)**  
   - Filament forms (`CreateTrip.php`, `TripResource.php`)  
   - Validates driver/vehicle availability, start/end times, duration (≤ 24h)  

3. **Service-Layer (Application-Level Pre-Save)**  
   - `TripValidationService` final validation  
   - Checks ownership, driver/vehicle assignment, active status, and overlaps  

**Supporting Features**  
- **Redis caching** for drivers, vehicles, and clients  
- **Business rules:** Prevent past trips, max 24h trip duration, only active drivers selectable  
- **Notifications & Logging** for conflicts  

---

## **Dashboard & Widgets**

| Widget                 |         Purpose           |                Notes      |
|--------                |---------------------------|-----------------------------------------|
| **ActiveTripsWidget**  | Shows current active trips | Cached 1 min, truck icon, warning color |
| **AvailabilityWidget** | Shows available drivers & vehicles | Cached 1 min, user-group/truck icons |
| **DriverStatsOverviewWidget** | Personalized driver stats | Vehicles assigned, completed trips, active trips |
| **MonthlyTripsWidget** | Completed trips this month | Cached 5 min, success color |
