# Campus Maintenance System

> A comprehensive web-based platform for managing campus facility maintenance requests, inventory, task assignments, and notifications. Built with PHP backend and vanilla JavaScript frontend, deployed on Neon PostgreSQL.

**Live Demo:** [Deploy Instructions](#deployment)  
**Documentation:** See [DEPLOYMENT.md](./DEPLOYMENT.md) for setup and deployment guide

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Quick Start](#quick-start)
- [API Documentation](#api-documentation)
- [User Roles & Permissions](#user-roles--permissions)
- [File Structure](#file-structure)
- [Deployment](#deployment)
- [Testing](#testing)
- [Known Issues & Future Improvements](#known-issues--future-improvements)

---

## 🎯 Project Overview

The **Campus Maintenance System** is an enterprise-grade web application designed to streamline facility maintenance operations at educational institutions. It provides a centralized platform for:

- **Students/Staff** to submit maintenance requests with photo evidence
- **Technicians** to manage assigned tasks and track progress
- **Administrators** to oversee inventory, approve requests, assign work, and generate reports
- **Real-time notifications** for status updates and urgency alerts

This project demonstrates full-stack web development including:

- RESTful API design with security best practices
- JWT-based authentication and role-based access control (RBAC)
- PostgreSQL database with complex relationships
- Interactive responsive UI with real-time updates
- Docker containerization for production deployment

---

## ✨ Key Features

### For All Users

- 🔐 **Secure Authentication** - JWT tokens with password hashing (bcrypt)
- 👤 **User Profiles** - Role-based dashboards and preferences
- 🔔 **Real-time Notifications** - Status updates, assignments, and announcements
- 🌓 **Dark/Light Theme Toggle** - User preference storage

### For Students/Staff

- 📝 **Submit Requests** - Create maintenance requests with title, description, and photos
- 📊 **Track Status** - Monitor request progress from submission to completion
- 📋 **View History** - Access previous requests and outcomes
- 🖼️ **File Uploads** - Attach images to document maintenance issues

### For Technicians

- 📋 **Task Management** - View assigned maintenance tasks
- ✅ **Status Updates** - Mark tasks as In Progress, Completed, or On Hold
- 📦 **Material Requests** - Request required inventory items for tasks
- 📊 **Performance Tracking** - View completion rates and audit logs

### For Administrators

- 👥 **User Management** - Create, update, delete users with role assignment
- 📦 **Inventory Control** - Track items, adjust stock, set reorder levels
- 🔀 **Request Assignment** - Assign requests to technicians and track processing
- 📊 **Analytics & Reports** - Generate audit logs, performance reports, timeline views
- ⚙️ **System Configuration** - Manage settings and view system health

---

## 🛠 Tech Stack

### Backend

| Layer                  | Technology        | Purpose                  |
| ---------------------- | ----------------- | ------------------------ |
| **Language**           | PHP 8.2           | Server-side logic        |
| **Database**           | PostgreSQL (Neon) | Data persistence         |
| **Authentication**     | JWT (Firebase)    | Secure token-based auth  |
| **Hashing**            | bcrypt            | Password security        |
| **Dependency Manager** | Composer          | Package management       |
| **Environment**        | .env (phpdotenv)  | Configuration management |

### Frontend

| Layer            | Technology            | Purpose             |
| ---------------- | --------------------- | ------------------- |
| **Language**     | ES6 JavaScript        | Client-side logic   |
| **HTML/CSS**     | Vanilla CSS3          | Markup & styling    |
| **Architecture** | SPA (Single Page App) | Client-side routing |
| **API Client**   | Fetch API             | HTTP requests       |
| **Storage**      | LocalStorage          | Session persistence |

### Infrastructure

| Tool               | Purpose                      |
| ------------------ | ---------------------------- |
| **Docker**         | Containerization             |
| **Docker Compose** | Local dev environment        |
| **Render.app**     | Production hosting (backend) |
| **Vercel/Netlify** | Frontend deployment          |
| **Neon**           | Cloud PostgreSQL database    |

---

## 🏗 Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Vanilla JS)                     │
│  ┌──────────────┬──────────────┬──────────────────────────┐ │
│  │ Auth Pages   │ Dashboards   │  Task/Request/User Pages  │ │
│  │ (login.js)   │ (admin, tech, │  (requests.js, users.js)  │ │
│  │              │   student)   │                          │ │
│  └──────────────┴──────────────┴──────────────────────────┘ │
│                          ↓ (fetch)                           │
│                   API Wrapper (api.js)                       │
└─────────────────────────────────────────────────────────────┘
                             ↕ (REST + JWT)
┌─────────────────────────────────────────────────────────────┐
│                    Backend (PHP 8.2)                         │
│  ┌─────────────────────────────────────────────────────────┐│
│  │           API Routes (bootstrap.php)                     ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │  /api/auth      /api/users     /api/requests │        ││
│  │  │  /api/tasks     /api/inventory /api/reports │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │           Controllers Layer                 │        ││
│  │  │  AuthController  RequestController           │        ││
│  │  │  UserController  InventoryController         │        ││
│  │  │  TaskController  ReportController            │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │         Services & Utilities                 │        ││
│  │  │  AuthMiddleware  JWTHandler  Response        │        ││
│  │  │  NotificationService  AnalyticsService       │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │    Database Layer (PDO)                      │        ││
│  │  │    PostgreSQL / Neon                         │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 💾 Database Schema

### Core Tables

#### **users** - User accounts and authentication

```sql
id | name | email | password_hash | role | status | specialization | last_login | created_at | updated_at
```

**Roles:** Admin, Technician, Student, Staff, Manager

#### **maintenance_requests** - Facility maintenance requests

```sql
id | user_id | title | description | category | priority | status | assigned_to | created_at | updated_at
```

**Status:** Pending, In Progress, Completed, Rejected

#### **request_images** - Photo evidence for requests

```sql
id | request_id | image_path | created_at
```

#### **inventory** - Facility supplies and materials

```sql
id | item_name | category | quantity | unit | reorder_level | created_by | created_at | updated_at
```

#### **tasks** - Work assignments for technicians

```sql
id | request_id | title | assigned_to | status | start_time | due_date | created_at | updated_at
```

#### **notifications** - Real-time system notifications

```sql
id | user_id | type | title | message | related_entity_type | is_read | created_at
```

#### **audit_logs** - System action tracking

```sql
id | user_id | action | entity_type | entity_id | details | created_at
```

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+ with PDO and PostgreSQL extensions
- Composer
- Node.js 18+ (optional, for frontend build tools)
- Neon or PostgreSQL 12+ database account

### Local Development Setup

#### 1. Clone Repository

```bash
git clone https://github.com/yourusername/campus-maintenance-system.git
cd campus-maintenance-system
```

#### 2. Backend Setup

```bash
cd backend
composer install
cp .env.example .env

# Edit .env and add your Neon database URL:
# NEON_DATABASE_URL=postgresql://user:pass@ep-xxx.neon.tech/dbname?sslmode=require
```

#### 3. Frontend Setup

```bash
cd ../frontend
cp .env.example .env

# Edit .env
# VITE_API_URL=http://localhost:8000
```

#### 4. Run with Docker Compose (Recommended)

```bash
cd ..
docker-compose up
# Backend: http://localhost:8000
# Frontend: http://localhost:3000
```

#### 5. Test Login

| Email              | Password | Role       |
| ------------------ | -------- | ---------- |
| admin@campus.edu   | password | Admin      |
| tech@campus.edu    | password | Technician |
| student@campus.edu | password | Student    |

---

## 🔌 API Documentation

### Base URL

```
Local:      http://localhost:8000/api
Production: https://your-backend.onrender.com/api
```

### Authentication

All requests (except login) require JWT token:

```
Authorization: Bearer <jwt_token>
```

### Key Endpoints

| Method        | Endpoint                            | Description            | Auth       |
| ------------- | ----------------------------------- | ---------------------- | ---------- |
| **Auth**      |
| POST          | /auth/login.php                     | Login, get JWT token   | ❌         |
| POST          | /auth/reset-request.php             | Request password reset | ❌         |
| **Users**     |
| GET           | /users/list.php                     | List all users         | ✅ Admin   |
| POST          | /users/create.php                   | Create user            | ✅ Admin   |
| POST          | /users/update.php?id=<id>           | Update user            | ✅ Admin   |
| POST          | /users/delete.php?id=<id>           | Delete user            | ✅ Admin   |
| **Requests**  |
| GET           | /requests/list.php                  | List requests          | ✅ All     |
| GET           | /requests/view.php?id=<id>          | View details           | ✅ All     |
| POST          | /requests/create.php                | Create request         | ✅ Student |
| POST          | /requests/assign.php?id=<id>        | Assign to tech         | ✅ Admin   |
| **Tasks**     |
| GET           | /tasks/my-tasks.php                 | Get assigned tasks     | ✅ Tech    |
| POST          | /tasks/update-status.php            | Update status          | ✅ Tech    |
| POST          | /tasks/request-materials.php        | Request items          | ✅ Tech    |
| **Inventory** |
| GET           | /inventory/list.php                 | List items             | ✅ All     |
| POST          | /inventory/create.php               | Create item            | ✅ Admin   |
| POST          | /inventory/update.php?id=<id>       | Update item            | ✅ Admin   |
| POST          | /inventory/adjust.php?id=<id>       | Adjust stock           | ✅ Admin   |
| **Reports**   |
| GET           | /reports/audit-logs.php             | Audit logs             | ✅ Admin   |
| GET           | /reports/by-category.php            | By category            | ✅ All     |
| GET           | /reports/technician-performance.php | Tech metrics           | ✅ Admin   |

### Response Format

**Success:**

```json
{
  "message": "Operation successful",
  "data": { "id": 1, "name": "Item" }
}
```

**Error:**

```json
{
  "error": "Validation failed",
  "message": "Email is required"
}
```

---

## 👥 User Roles & Permissions

| Role              | Capabilities                                                                   | Access         |
| ----------------- | ------------------------------------------------------------------------------ | -------------- |
| **Admin**         | User management, inventory control, request assignment, reports, system config | Full           |
| **Technician**    | View tasks, update status, request materials, track performance                | Task-focused   |
| **Student/Staff** | Submit requests, track status, upload images                                   | Limited to own |
| **Manager**       | Approve/reject requests, view analytics, manage notifications                  | Oversight      |

---

## 📁 File Structure

```
campus-maintenance-system/
├── backend/                    # PHP Backend
│   ├── api/                    # API endpoints
│   ├── src/
│   │   ├── Controllers/        # Business logic
│   │   ├── Services/           # Support services
│   │   ├── Middleware/         # Auth middleware
│   │   └── Utils/              # Helpers
│   ├── database/
│   │   ├── schema.sql          # MySQL (reference)
│   │   └── schema.pgsql        # PostgreSQL (production)
│   ├── Dockerfile              # Docker config
│   └── composer.json
│
├── frontend/                   # Vanilla JS Frontend
│   ├── src/
│   │   ├── js/
│   │   │   ├── api/            # API wrappers
│   │   │   ├── pages/          # Page components
│   │   │   └── components/     # Reusable UI
│   │   └── css/                # Styling
│   └── index.html
│
├── docker-compose.yml          # Local dev setup
├── render.yaml                 # Render deployment
├── DEPLOYMENT.md               # Deployment guide
└── README.md                   # This file
```

---

## 🚢 Deployment

### Deploy Backend to Render (Recommended)

1. Push to GitHub
2. Create service at [render.com](https://render.com)
3. Add environment variables:
   ```
   NEON_DATABASE_URL=postgresql://...
   CORS_ORIGINS=https://yourdomain.com
   JWT_SECRET=<strong-key>
   ```

### Deploy Frontend to Vercel/Netlify

1. Set `VITE_API_URL` to deployed backend
2. Deploy branch automatically

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed instructions.

---

## ✅ Testing

### Run Integration Tests

```bash
cd backend/scripts
php test_endpoints_with_auth.php
```

Covers:

- ✅ User creation and authentication
- ✅ Request submission and assignment
- ✅ Task management workflows
- ✅ Inventory operations
- ✅ Role-based access control

### Manual Testing Checklist

- [ ] Login with all user roles
- [ ] Submit maintenance request with image
- [ ] Assign request to technician
- [ ] Update task status
- [ ] Manage inventory
- [ ] View notifications
- [ ] Generate reports
- [ ] Dark mode toggle
- [ ] Mobile responsiveness

---

## 🐛 Known Issues & Future Improvements

### Known Issues

1. File uploads limited by server settings
2. Notifications use polling (not real-time WebSocket)
3. Reports export JSON only (no PDF yet)

### Future Improvements

- [ ] Email notifications (SendGrid/Mailgun)
- [ ] WebSocket for real-time updates
- [ ] Mobile app (React Native/Flutter)
- [ ] Two-factor authentication
- [ ] PDF report export
- [ ] Advanced search/filtering

---

## 📝 License

Educational project. See institution policies for reuse guidelines.

**Author:** [Your Name]  
**Institution:** [Your Institution]  
**Date:** May 2026
# Campus Maintenance System

> A comprehensive web-based platform for managing campus facility maintenance requests, inventory, task assignments, and notifications. Built with PHP backend and vanilla JavaScript frontend, deployed on Neon PostgreSQL.

**Live Demo:** [Deploy Instructions](#deployment)  
**Documentation:** See [DEPLOYMENT.md](./DEPLOYMENT.md) for setup and deployment guide

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Quick Start](#quick-start)
- [API Documentation](#api-documentation)
- [User Roles & Permissions](#user-roles--permissions)
- [File Structure](#file-structure)
- [Deployment](#deployment)
- [Testing](#testing)
- [Known Issues & Future Improvements](#known-issues--future-improvements)

---

## 🎯 Project Overview

The **Campus Maintenance System** is an enterprise-grade web application designed to streamline facility maintenance operations at educational institutions. It provides a centralized platform for:

- **Students/Staff** to submit maintenance requests with photo evidence
- **Technicians** to manage assigned tasks and track progress
- **Administrators** to oversee inventory, approve requests, assign work, and generate reports
- **Real-time notifications** for status updates and urgency alerts

This project demonstrates full-stack web development including:
- RESTful API design with security best practices
- JWT-based authentication and role-based access control (RBAC)
- PostgreSQL database with complex relationships
- Interactive responsive UI with real-time updates
- Docker containerization for production deployment

---

## ✨ Key Features

### For All Users
- 🔐 **Secure Authentication** - JWT tokens with password hashing (bcrypt)
- 👤 **User Profiles** - Role-based dashboards and preferences
- 🔔 **Real-time Notifications** - Status updates, assignments, and announcements
- 🌓 **Dark/Light Theme Toggle** - User preference storage

### For Students/Staff
- 📝 **Submit Requests** - Create maintenance requests with title, description, and photos
- 📊 **Track Status** - Monitor request progress from submission to completion
- 📋 **View History** - Access previous requests and outcomes
- 🖼️ **File Uploads** - Attach images to document maintenance issues

### For Technicians
- 📋 **Task Management** - View assigned maintenance tasks
- ✅ **Status Updates** - Mark tasks as In Progress, Completed, or On Hold
- 📦 **Material Requests** - Request required inventory items for tasks
- 📊 **Performance Tracking** - View completion rates and audit logs

### For Administrators
- 👥 **User Management** - Create, update, delete users with role assignment
- 📦 **Inventory Control** - Track items, adjust stock, set reorder levels
- 🔀 **Request Assignment** - Assign requests to technicians and track processing
- 📊 **Analytics & Reports** - Generate audit logs, performance reports, timeline views
- ⚙️ **System Configuration** - Manage settings and view system health

---

## 🛠 Tech Stack

### Backend
| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Language** | PHP 8.2 | Server-side logic |
| **Database** | PostgreSQL (Neon) | Data persistence |
| **Authentication** | JWT (Firebase) | Secure token-based auth |
| **Hashing** | bcrypt | Password security |
| **Dependency Manager** | Composer | Package management |
| **Environment** | .env (phpdotenv) | Configuration management |

### Frontend
| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Language** | ES6 JavaScript | Client-side logic |
| **HTML/CSS** | Vanilla CSS3 | Markup & styling |
| **Architecture** | SPA (Single Page App) | Client-side routing |
| **API Client** | Fetch API | HTTP requests |
| **Storage** | LocalStorage | Session persistence |

### Infrastructure
| Tool | Purpose |
|------|---------|
| **Docker** | Containerization |
| **Docker Compose** | Local dev environment |
| **Render.app** | Production hosting (backend) |
| **Vercel/Netlify** | Frontend deployment |
| **Neon** | Cloud PostgreSQL database |

---

## 🏗 Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Vanilla JS)                     │
│  ┌──────────────┬──────────────┬──────────────────────────┐ │
│  │ Auth Pages   │ Dashboards   │  Task/Request/User Pages  │ │
│  │ (login.js)   │ (admin, tech, │  (requests.js, users.js)  │ │
│  │              │   student)   │                          │ │
│  └──────────────┴──────────────┴──────────────────────────┘ │
│                          ↓ (fetch)                           │
│                   API Wrapper (api.js)                       │
└─────────────────────────────────────────────────────────────┘
                             ↕ (REST + JWT)
┌─────────────────────────────────────────────────────────────┐
│                    Backend (PHP 8.2)                         │
│  ┌─────────────────────────────────────────────────────────┐│
│  │           API Routes (bootstrap.php)                     ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │  /api/auth      /api/users     /api/requests │        ││
│  │  │  /api/tasks     /api/inventory /api/reports │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │           Controllers Layer                 │        ││
│  │  │  AuthController  RequestController           │        ││
│  │  │  UserController  InventoryController         │        ││
│  │  │  TaskController  ReportController            │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │         Services & Utilities                 │        ││
│  │  │  AuthMiddleware  JWTHandler  Response        │        ││
│  │  │  NotificationService  AnalyticsService       │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  │                       ↓                                  ││
│  │  ┌─────────────────────────────────────────────┐        ││
│  │  │    Database Layer (PDO)                      │        ││
│  │  │    PostgreSQL / Neon                         │        ││
│  │  └─────────────────────────────────────────────┘        ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 💾 Database Schema

### Core Tables

#### **users** - User accounts and authentication
```sql
id | name | email | password_hash | role | status | specialization | last_login | created_at | updated_at
```
**Roles:** Admin, Technician, Student, Staff, Manager

#### **maintenance_requests** - Facility maintenance requests
```sql
id | user_id | title | description | category | priority | status | assigned_to | created_at | updated_at
```
**Status:** Pending, In Progress, Completed, Rejected

#### **request_images** - Photo evidence for requests
```sql
id | request_id | image_path | created_at
```

#### **inventory** - Facility supplies and materials
```sql
id | item_name | category | quantity | unit | reorder_level | created_by | created_at | updated_at
```

#### **tasks** - Work assignments for technicians
```sql
id | request_id | title | assigned_to | status | start_time | due_date | created_at | updated_at
```

#### **notifications** - Real-time system notifications
```sql
id | user_id | type | title | message | related_entity_type | is_read | created_at
```

#### **audit_logs** - System action tracking
```sql
id | user_id | action | entity_type | entity_id | details | created_at
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+ with PDO and PostgreSQL extensions
- Composer
- Node.js 18+ (optional, for frontend build tools)
- Neon or PostgreSQL 12+ database account

### Local Development Setup

#### 1. Clone Repository
```bash
git clone https://github.com/yourusername/campus-maintenance-system.git
cd campus-maintenance-system
```

#### 2. Backend Setup
```bash
cd backend
composer install
cp .env.example .env

# Edit .env and add your Neon database URL:
# NEON_DATABASE_URL=postgresql://user:pass@ep-xxx.neon.tech/dbname?sslmode=require
```

#### 3. Frontend Setup
```bash
cd ../frontend
cp .env.example .env

# Edit .env
# VITE_API_URL=http://localhost:8000
```

#### 4. Run with Docker Compose (Recommended)
```bash
cd ..
docker-compose up
# Backend: http://localhost:8000
# Frontend: http://localhost:3000
```

#### 5. Test Login
| Email | Password | Role |
|-------|----------|------|
| admin@campus.edu | password | Admin |
| tech@campus.edu | password | Technician |
| student@campus.edu | password | Student |

---

## 🔌 API Documentation

### Base URL
```
Local:      http://localhost:8000/api
Production: https://your-backend.onrender.com/api
```

### Authentication
All requests (except login) require JWT token:
```
Authorization: Bearer <jwt_token>
```

### Key Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| **Auth** |
| POST | /auth/login.php | Login, get JWT token | ❌ |
| POST | /auth/reset-request.php | Request password reset | ❌ |
| **Users** |
| GET | /users/list.php | List all users | ✅ Admin |
| POST | /users/create.php | Create user | ✅ Admin |
| POST | /users/update.php?id=<id> | Update user | ✅ Admin |
| POST | /users/delete.php?id=<id> | Delete user | ✅ Admin |
| **Requests** |
| GET | /requests/list.php | List requests | ✅ All |
| GET | /requests/view.php?id=<id> | View details | ✅ All |
| POST | /requests/create.php | Create request | ✅ Student |
| POST | /requests/assign.php?id=<id> | Assign to tech | ✅ Admin |
| **Tasks** |
| GET | /tasks/my-tasks.php | Get assigned tasks | ✅ Tech |
| POST | /tasks/update-status.php | Update status | ✅ Tech |
| POST | /tasks/request-materials.php | Request items | ✅ Tech |
| **Inventory** |
| GET | /inventory/list.php | List items | ✅ All |
| POST | /inventory/create.php | Create item | ✅ Admin |
| POST | /inventory/update.php?id=<id> | Update item | ✅ Admin |
| POST | /inventory/adjust.php?id=<id> | Adjust stock | ✅ Admin |
| **Reports** |
| GET | /reports/audit-logs.php | Audit logs | ✅ Admin |
| GET | /reports/by-category.php | By category | ✅ All |
| GET | /reports/technician-performance.php | Tech metrics | ✅ Admin |

### Response Format

**Success:**
```json
{
  "message": "Operation successful",
  "data": { "id": 1, "name": "Item" }
}
```

**Error:**
```json
{
  "error": "Validation failed",
  "message": "Email is required"
}
```

---

## 👥 User Roles & Permissions

| Role | Capabilities | Access |
|------|--------------|--------|
| **Admin** | User management, inventory control, request assignment, reports, system config | Full |
| **Technician** | View tasks, update status, request materials, track performance | Task-focused |
| **Student/Staff** | Submit requests, track status, upload images | Limited to own |
| **Manager** | Approve/reject requests, view analytics, manage notifications | Oversight |

---

## 📁 File Structure

```
campus-maintenance-system/
├── backend/                    # PHP Backend
│   ├── api/                    # API endpoints
│   ├── src/
│   │   ├── Controllers/        # Business logic
│   │   ├── Services/           # Support services
│   │   ├── Middleware/         # Auth middleware
│   │   └── Utils/              # Helpers
│   ├── database/
│   │   ├── schema.sql          # MySQL (reference)
│   │   └── schema.pgsql        # PostgreSQL (production)
│   ├── Dockerfile              # Docker config
│   └── composer.json
│
├── frontend/                   # Vanilla JS Frontend
│   ├── src/
│   │   ├── js/
│   │   │   ├── api/            # API wrappers
│   │   │   ├── pages/          # Page components
│   │   │   └── components/     # Reusable UI
│   │   └── css/                # Styling
│   └── index.html
│
├── docker-compose.yml          # Local dev setup
├── render.yaml                 # Render deployment
├── DEPLOYMENT.md               # Deployment guide
└── README.md                   # This file
```

---

## 🚢 Deployment

### Deploy Backend to Render (Recommended)
1. Push to GitHub
2. Create service at [render.com](https://render.com)
3. Add environment variables:
   ```
   NEON_DATABASE_URL=postgresql://...
   CORS_ORIGINS=https://yourdomain.com
   JWT_SECRET=<strong-key>
   ```

### Deploy Frontend to Vercel/Netlify
1. Set `VITE_API_URL` to deployed backend
2. Deploy branch automatically

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed instructions.

---

## ✅ Testing

### Run Integration Tests
```bash
cd backend/scripts
php test_endpoints_with_auth.php
```

Covers:
- ✅ User creation and authentication
- ✅ Request submission and assignment
- ✅ Task management workflows
- ✅ Inventory operations
- ✅ Role-based access control

### Manual Testing Checklist
- [ ] Login with all user roles
- [ ] Submit maintenance request with image
- [ ] Assign request to technician
- [ ] Update task status
- [ ] Manage inventory
- [ ] View notifications
- [ ] Generate reports
- [ ] Dark mode toggle
- [ ] Mobile responsiveness

---

## 🐛 Known Issues & Future Improvements

### Known Issues
1. File uploads limited by server settings
2. Notifications use polling (not real-time WebSocket)
3. Reports export JSON only (no PDF yet)

### Future Improvements
- [ ] Email notifications (SendGrid/Mailgun)
- [ ] WebSocket for real-time updates
- [ ] Mobile app (React Native/Flutter)
- [ ] Two-factor authentication
- [ ] PDF report export
- [ ] Advanced search/filtering

---
