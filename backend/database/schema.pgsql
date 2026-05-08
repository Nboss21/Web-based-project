-- PostgreSQL schema converted from schema.sql
-- Suitable for Neon or other Postgres hosts. Do NOT run CREATE DATABASE here;
-- run the import script or psql against the target database.

-- enum types
DO $$ BEGIN
    CREATE TYPE user_role_enum AS ENUM ('Admin', 'User', 'Manager');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE user_status_enum AS ENUM ('Active', 'Disabled');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- helper to auto-update updated_at
CREATE OR REPLACE FUNCTION trigger_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- users
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role user_role_enum NOT NULL DEFAULT 'User',
    status user_status_enum NOT NULL DEFAULT 'Active',
    specialization VARCHAR(150),
    last_login TIMESTAMP WITH TIME ZONE,
    is_locked BOOLEAN DEFAULT FALSE,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER users_set_timestamp
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION trigger_set_timestamp();

-- password_resets
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- maintenance_requests
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    priority VARCHAR(50) DEFAULT 'Medium',
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    processed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER maintenance_requests_set_timestamp
BEFORE UPDATE ON maintenance_requests
FOR EACH ROW
EXECUTE FUNCTION trigger_set_timestamp();

-- request_images
CREATE TABLE IF NOT EXISTS request_images (
    id SERIAL PRIMARY KEY,
    request_id INTEGER NOT NULL REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- notifications (extended)
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    message TEXT NOT NULL,
    related_entity_type VARCHAR(100),
    related_entity_id INTEGER,
    metadata JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- indexes: create any indexes that were implicit in MySQL
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- users: notification preferences (JSON)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS notification_preferences TEXT;

-- audit logs for actions
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INTEGER,
    details TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- analytics cache for reports
CREATE TABLE IF NOT EXISTS analytics_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    cache_value TEXT,
    expires_at TIMESTAMP WITH TIME ZONE
);

-- inventory table and related objects
CREATE TABLE IF NOT EXISTS inventory (
    id SERIAL PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    unit VARCHAR(30) DEFAULT 'unit',
    quantity INTEGER DEFAULT 0,
    reorder_level INTEGER DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id SERIAL PRIMARY KEY,
    item_id INTEGER NOT NULL REFERENCES inventory(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    type VARCHAR(50) NOT NULL,
    quantity_change INTEGER NOT NULL,
    before_quantity INTEGER,
    after_quantity INTEGER,
    reason TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- low stock alerts
CREATE TABLE IF NOT EXISTS low_stock_alerts (
    id SERIAL PRIMARY KEY,
    item_id INTEGER NOT NULL REFERENCES inventory(id) ON DELETE CASCADE,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- material requests (for requesting inventory items for tasks)
CREATE TABLE IF NOT EXISTS material_requests (
    id SERIAL PRIMARY KEY,
    task_id INTEGER REFERENCES tasks(id) ON DELETE SET NULL,
    requested_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    notes TEXT,
    processed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS material_request_items (
    id SERIAL PRIMARY KEY,
    request_id INTEGER NOT NULL REFERENCES material_requests(id) ON DELETE CASCADE,
    item_id INTEGER NOT NULL REFERENCES inventory(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1
);

-- tasks table (lightweight representation used by material requests)
CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    request_id INTEGER REFERENCES maintenance_requests(id) ON DELETE SET NULL,
    title VARCHAR(255),
    description TEXT,
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(50) DEFAULT 'Open',
    is_active BOOLEAN DEFAULT TRUE,
    start_time TIMESTAMP WITH TIME ZONE,
    completion_time TIMESTAMP WITH TIME ZONE,
    changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- task completions (photos)
CREATE TABLE IF NOT EXISTS task_completions (
    id SERIAL PRIMARY KEY,
    task_id INTEGER NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
    photo_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- indexes for inventory
CREATE INDEX IF NOT EXISTS idx_inventory_item_name ON inventory(item_name);
CREATE INDEX IF NOT EXISTS idx_inventory_category ON inventory(category);

