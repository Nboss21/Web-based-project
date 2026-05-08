# Test Users & Credentials

The following test users have been seeded to your Neon database for role-based access testing:

## Admin User
- **Email:** admin@maintenance.local
- **Password:** AdminPass123!
- **Role:** Admin
- **Permissions:** Full access to all endpoints, admin-only features

## Manager User
- **Email:** manager@maintenance.local
- **Password:** ManagerPass123!
- **Role:** Manager
- **Permissions:** Can manage requests, users, and basic operations

## Regular User
- **Email:** user@maintenance.local
- **Password:** UserPass123!
- **Role:** User
- **Permissions:** Can create and view own requests, limited access

---

## Testing Login

### 1. Login Endpoint
```bash
POST /api/auth/login.php
Content-Type: application/json

{
  "email": "admin@maintenance.local",
  "password": "AdminPass123!"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@maintenance.local",
    "role": "Admin"
  }
}
```

### 2. Use JWT Token for Protected Routes
```bash
GET /api/admin-only.php
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## Protected Endpoints by Role

| Endpoint | Admin | Manager | User |
|----------|-------|---------|------|
| `/api/users/list.php` | ✓ | ✓ | ✗ |
| `/api/users/create.php` | ✓ | ✗ | ✗ |
| `/api/requests/list.php` | ✓ | ✓ | ✓* |
| `/api/requests/create.php` | ✓ | ✓ | ✓ |
| `/api/admin-only.php` | ✓ | ✗ | ✗ |

*User can only view their own requests

---

## Testing with cURL

### Login as Admin
```bash
curl -X POST http://localhost:8000/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@maintenance.local","password":"AdminPass123!"}'
```

### Access Admin Endpoint
```bash
curl -X GET http://localhost:8000/api/admin-only.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

---

## Database Notes

- **Database:** maintenance_system (Neon PostgreSQL)
- **Connection:** NEON_DATABASE_URL in `.env`
- **Password Hash:** SHA256 (for testing only - use bcrypt/argon2 in production)

To test with different roles, simply login with the respective email/password and use the returned JWT token.
