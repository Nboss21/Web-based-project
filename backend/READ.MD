# PHP Backend Setup

This is a modular PHP backend with JWT authentication and role-based access control.

## Setup Instructions

1.  **Install Dependencies**:
    Open a terminal in the `backend/` folder and run:
    ```bash
    composer install
    ```

2.  **Database Configuration**:
    - Create a MySQL database named `web_project_db`.
    - Import the schema from `database/schema.sql`.
    - Update the `.env` file with your database credentials.

3.  **Run the Server**:
    If you have PHP installed, you can run:
    ```bash
    php -S localhost:8000
    ```
    Or place the project in your `htdocs` (XAMPP) or `www` (WAMP) folder.

## API Endpoints

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/api/auth/register.php` | POST | Register a new user |
| `/api/auth/login.php` | POST | Login and receive JWT |
| `/api/auth/reset-request.php` | POST | Request a password reset token |
| `/api/auth/reset-complete.php` | POST | Reset password using token |
| `/api/admin-only.php` | GET | Protected endpoint (requires Admin role) |

## Authentication
All protected endpoints require an `Authorization` header:
`Authorization: Bearer <your_jwt_token>`
