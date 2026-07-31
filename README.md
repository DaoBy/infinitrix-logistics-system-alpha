# Infinitrix Logistics Management System

BS Computer Science Capstone Project | STI College Fairview  
Technical Lead: Blaise Gabriel Mariano

A multi-role logistics platform built for Infinitrix Express Cargo, covering the full delivery lifecycle—from customer booking and operational approval to driver dispatch, tracking, and financial settlement.

**[User Manual](docs/User_Manual.pdf)** | **[Flowchart & ERD](docs/Flowchart _ERD.pdf)**

---

## Overview

This system was developed as a capstone project to simulate the operational workflow of a real-world courier company. It is not a basic CRUD application—it is a workflow engine with distinct portals for customers, operations staff, drivers, collectors, and administrators.

The system manages the full delivery process:
- Customer booking with photo verification and dynamic pricing
- Staff approval and waybill generation
- Cargo assignment and truck manifest creation
- Driver status and location updates
- Payment collection and verification
- Refund handling and reporting

---

## Key Features

- **Separate portals** for Customers, Staff, Drivers, Collectors, and Admin, each with role-specific views and permissions.
- **Booking engine** with real-time pricing, dimension/weight input, and mandatory photo uploads (minimum 6 images per package).
- **Postpaid support** with configurable payment terms (CND, Net 7, Net 15, Net 30), available only to customers with at least 3 completed deliveries.
- **Waybill and sticker generation** with batch printing and PDF output.
- **Driver-truck assignment** with tracking for volume/weight capacity, driver cooldowns, and backhaul eligibility.
- **Manifest creation and finalization** before dispatch.
- **Payment workflows** for both prepaid and postpaid transactions, including cash, GCash, and bank transfer.
- **Admin utilities** including database backup and restore, data archiving, and customer profile audit logs.

---

## Tech Stack

**Frontend:** Vue.js 3, Pinia, Tailwind CSS  
**Backend:** PHP 8, Laravel 10 (REST API)  
**Database:** MySQL  
**Architecture:** SPA with JWT authentication  
**Tools:** Git, Composer, NPM

---

## Screenshots

| Customer Booking | Staff Pending Requests | Delivery Completion |
| :---: | :---: | :---: |
| [![Booking](docs/screenshots/image20.png)](docs/screenshots/image20.png) | [![Pending](docs/screenshots/image28.png)](docs/screenshots/image28.png) | [![Completion](docs/screenshots/image49.png)](docs/screenshots/image49.png) |

| Driver-Truck Assignment | Admin Reports | Package Monitoring |
| :---: | :---: | :---: |
| [![Assignment](docs/screenshots/image27.png)](docs/screenshots/image27.png) | [![Reports](docs/screenshots/image61.png)](docs/screenshots/image61.png) | [![Monitoring](docs/screenshots/image11.png)](docs/screenshots/image11.png) |

---

## User Roles

**Customer**  
Request deliveries, track packages, view order history, and manage saved receiver details.

**Staff (Operations)**  
Approve or reject delivery requests, generate waybills and stickers, create driver-truck assignments, generate manifests, and release packages.

**Driver**  
View assigned deliveries, update package status (Delivered / Damaged / Lost), and update current location.

**Collector (Finance)**  
Manage pending and postpaid collections, submit verification requests, and track collection performance.

**Admin**  
Verify payments, manage refunds and adjustments, handle employee and customer records, configure warehouse regions, and access system utilities.

---

## Architecture

The frontend is a Vue.js SPA that communicates with a Laravel backend through RESTful APIs. The backend handles business logic, file storage, and database operations. The database is normalized to support complex joins for pricing, route assignments, and financial tracking.

**Technical decisions worth noting:**
- Pricing is calculated using a configurable matrix (base fee + volume + weight + package fee), not hardcoded logic.
- The booking process is split into multiple steps, with state managed via Pinia to prevent data loss across components.
- Role-based access is enforced through Laravel middleware, and the Vue frontend dynamically renders the correct dashboard per role.
- All parcel photos and payment receipts are stored using Laravel's filesystem with basic validation for file type and size.

---

## Database Overview

- **Users and Roles:** A single users table with a role column, extended by profile tables for each role type.
- **Deliveries:** Central deliveries table linking sender and receiver information.
- **Driver-Truck Sets:** Pivot table tracking assignment history, capacity usage, and cooldown periods.
- **Financials:** Separate tables for prepaid payments and postpaid collections to maintain a clear audit trail.

Refer to the ERD file in the `docs/` folder for the full schema.

---

## Installation

Requirements: PHP 8.1+, Composer, Node.js, MySQL

```bash
git clone https://github.com/DaoBy/infinitrix-logistics-system-alpha.git
cd infinitrix-logistics-system-alpha

composer install
npm install
npm run dev

cp .env.example .env
php artisan key:generate

# Configure your database credentials in .env

php artisan migrate --seed
php artisan serve