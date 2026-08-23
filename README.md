# PharmaTech Engine — Advanced Pharmacy Management System

**Enterprise-grade backend system for pharmacy and inventory management**, built on a Multi-Tenant architecture (Tenant-per-Pharmacy) with robust data isolation, encrypted data handling, and automated background jobs.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Key Features](#key-features)
- [Architecture](#architecture)
- [Local Development Setup](#local-development-setup)
- [Roadmap](#roadmap)
- [License](#license)

---

## Overview

PharmaTech Engine is a production-grade backend platform designed to power pharmacy operations at enterprise scale. It combines strict multi-tenant data isolation, encrypted-at-rest sensitive data with non-reversible lookup hashes for efficient querying, and a suite of automated background jobs that keep inventory, finances, and notifications running without manual intervention.

The system is built for reliability and auditability first — financial transactions, debt tracking, and inventory movements are all first-class citizens of the domain model, not afterthoughts bolted onto a generic CRUD system.

---

## Tech Stack

| Layer                   | Technology                                                |
|--------------------------|------------------------------------------------------------|
| Backend Framework         | PHP 8.5, Laravel 12                                         |
| Admin Panel               | Filament PHP                                                |
| Database                  | MySQL — Encrypted Casts & non-reversible Lookup Hashes       |
| Authorization              | Spatie Permission (RBAC)                                      |
| Queue & Background Jobs    | Redis & Laravel Queues                                          |
| Push Notifications         | Firebase Cloud Messaging (FCM)                                  |
| Environment / Tooling      | Docker & Laravel Sail                                            |

---

## Key Features

### 🏥 Multi-Tenant Isolation
Strict pharmacy tenant boundaries are enforced via a custom `ResolvePharmacy` middleware combined with dedicated authorization policies, ensuring no cross-tenant data leakage at the application or query layer.

### 🔐 Encrypted, Query-Safe Sensitive Data
Sensitive fields are stored using Laravel encrypted casts, paired with non-reversible lookup hashes — enabling fast, indexed lookups (e.g., searching by phone number or email) without ever decrypting data server-side for querying.

### 📦 Smart FEFO Inventory Protocol
First-Expiry, First-Out stock rotation is enforced at the batch level, minimizing waste and ensuring near-expiry inventory is dispensed before fresher stock.

### 💰 Financial & Credit Tracking
Full cash box management, customer and supplier debt ledgers, and installment payment tracking — giving pharmacy owners complete visibility into receivables, payables, and daily cash flow.

### ⏱ Automated Task Scheduling
Cron-driven jobs run with `withoutOverlapping()` protection to safely handle:
- Daily expiration checks
- Overdue debt monitoring
- Notification dispatch
- Weather-driven stock demand forecasting

### 🤖 AI & External Integrations
- **LLM API integration** for interaction analysis
- **Weather API integration** feeding demand forecasting models

### 🔑 Role-Based Access Control
Authorization is fully managed through Spatie Permission, supporting granular roles and permissions across pharmacy staff.

---

## Architecture

PharmaTech Engine follows a **Tenant-per-Pharmacy Multi-Tenant** model — each execution domain is scoped to a specific pharmacy's data context, with tenant resolution handled transparently at the middleware layer (`ResolvePharmacy`). This keeps authorization logic centralized and auditable while allowing the application to scale efficiently without data cross-contamination.

Background processing (notifications, forecasting, scheduled checks) is decoupled from the request/response cycle via Redis-backed Laravel Queues, ensuring the application remains responsive under load.

---

## Local Development Setup

This project uses **Docker** and **Laravel Sail** for a fully containerized local development environment.

### Prerequisites
- Docker Engine & Docker Compose

### Setup Steps

**1. Clone the repository**
```bash
git clone https://github.com/3delsaker940/PharmaTech.git
cd PharmaTech
```

**2. Environment & Dependencies Setup**
```bash
cp .env.example .env

# Install dependencies using a temporary Docker container (if PHP/Composer is not installed locally)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

**3. Start containers**
```bash
./vendor/bin/sail up -d
```

**4. Generate application key and build the database**
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

Your local instance should now be up and running, with a fully seeded database ready for development.

---

## Roadmap

- [ ] **Rule-Based Engine** — Prolog-like inference engine for offline drug interaction checks
- [ ] **Native PDF Export** — Invoice & statement generation via `laravel-dompdf`
- [ ] **Offline-First Sync Resilience** — Robust database synchronization for intermittent connectivity environments

---

## License

This project is proprietary software. All rights reserved unless otherwise stated.
