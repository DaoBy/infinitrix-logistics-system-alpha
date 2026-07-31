# Infinitrix Logistics Management System

> **Capstone Project | Technical Lead | BS Computer Science - STI College Fairview**
>
> A comprehensive, multi-role logistics platform designed to manage the lifecycle of cargo delivery from customer booking to final release. Built to simulate the operational workflow of a real-world courier company (Infinitrix Express Cargo).

**[View Live Demo (Local Environment)](Not Deployed)** | **[Download User Manual](docs/User%20Manual.pdf)**

---

## 🚀 Project Overview

Infinitrix is not a simple CRUD app; it is a workflow-driven logistics engine. As the **Technical Lead** for a team of four, I directed the system architecture, database schema design, and implemented the core backend logic along with a substantial portion of the Vue.js frontend.

The system manages the entire lifecycle of a delivery:
1.  **Customer Booking** (Parcel details, dimension calculations, photo uploads).
2.  **Operational Management** (Staff approval, waybill generation, sticker printing).
3.  **Logistics Assignment** (Driver-Truck set creation, cargo assignment, manifest generation).
4.  **Last-Mile Delivery** (Driver live status updates, location tracking).
5.  **Finance & Admin** (Prepaid/Postpaid payment verification, refunds, and reporting).

## ✨ Key Features

- **Multi-Portal Architecture:** Separate dedicated interfaces for **Customers**, **Staff**, **Drivers**, **Collectors**, and **Admins**.
- **Advanced Booking Engine:** Dynamic pricing based on real-time dimension/weight input, mandatory minimum 6-photo verification for parcels, and postpaid eligibility checks (3-delivery minimum).
- **Operational Tools:** Includes automated Waybill generation, sticker printing with batch actions, and manifest finalization.
- **Cargo & Fleet Management:** A dedicated "Driver-Truck Sets" module that tracks driver cooldowns, volume/weight capacity, and backhaul eligibility.
- **Financial Workflows:** Supports both Prepaid and Postpaid (CND, Net 7, Net 15, Net 30) terms with distinct cash and online payment verification queues.
- **System Utilities:** Full database backup/restore capabilities, data archiving for old manifests/waybills, and customer profile audit logs.

## 🛠️ Technology Stack

This project utilizes a modern industry-standard stack:

| Layer | Technology |
| :--- | :--- |
| **Frontend** | Vue.js 3, Pinia (State Management), Tailwind CSS |
| **Backend** | PHP 8.x, Laravel 10.x (REST API) |
| **Database** | MySQL / MariaDB |
| **Architecture** | SPA (Single Page Application) with JWT Authentication |
| **Dev Tools** | Git, Composer, NPM, Docker (Optional) |

## 📸 System Screenshots

*Click to view full-size.*

| Customer Booking Flow | Staff Dashboard | Delivery Completion |
| :---: | :---: | :---: |
| ![Booking Form](docs/screenshots/01-customer-booking.png) | ![Staff Pending](docs/screenshots/02-staff-pending.png) | ![Completion](docs/screenshots/03-staff-completion.png) |

| Driver-Truck Assignment | Admin Reports | Package Monitoring |
| :---: | :---: | :---: |
| ![Assignments](docs/screenshots/04-driver-truck-assign.png) | ![Reports](docs/screenshots/05-admin-reports.png) | ![Monitoring](docs/screenshots/06-staff-monitoring.png) |

## 👤 User Roles & Modules

The system is segmented into distinct portals to separate concerns:

1.  **Customer Portal:**
    - Schedule deliveries, track packages, view delivery history.
    - Manage profile and saved receiver information.
2.  **Staff Portal (Operations):**
    - Approve/Reject requests; Manage Waybills & Stickers; Create Driver-Truck assignments; Generate Manifests; Release packages.
3.  **Driver Portal (Mobile-Friendly):**
    - View assigned deliveries; Update package status (Delivered/Damaged/Lost); Update GPS location via map or dropdown.
4.  **Collector Portal (Finance):**
    - Handle Pending Collections; Submit verification requests; Track personal collection performance.
5.  **Admin Portal (System & Finance):**
    - Payment verification; Refund/Adjustments; Employee & Customer management; Warehouse/Region assignment; System utilities (Backups, Font preferences, Archiving).

## 🏗️ System Architecture

The application follows a modern **Client-Server** architecture:

- **Frontend (Vue SPA):** Communicates with the Laravel Backend via strict RESTful APIs.
- **Backend (Laravel):** Handles business logic, database interactions, and file storage (parcel images, payment receipts).
- **Database (MySQL):** Relational schema designed to handle complex joins for pricing calculations, route assignments, and financial ledger tracking.

**Technical Decisions:**
- **Pricing Logic:** Implemented via a configurable `Price Matrix` (Base Fee + Volume + Weight + Package fees) rather than hard-coded values, allowing admins to adjust rates dynamically.
- **File Handling:** Uses Laravel's native filesystem to segregate package photos, receipts, and generated PDFs (Waybills/Stickers).

## 🗄️ Database Overview

The database schema is normalized to support the complex relationships of logistics.

- **Users & Roles:** A unified `users` table with a `role` column (Admin/Staff/Driver/Collector/Customer) and related profile tables to handle polymorphic data.
- **Deliveries:** A central `deliveries` table linking `sender_id` and `receiver_id` (which can be users or guest addresses).
- **Driver-Truck Sets:** A pivot table managing the `drivers` and `trucks` tables, tracking assignment history, capacity usage, and cooldown periods.
- **Financials:** Separate tables for `payments` (prepaid) and `collections` (postpaid) to maintain clear audit trails and handle verification workflows.

## 🚀 Installation Guide (Local Development)

*Prerequisites: PHP 8.1+, Composer, Node.js, MySQL.*

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/DaoBy/infinitrix-logistics-system-alpha.git
    cd infinitrix-logistics-system-alpha