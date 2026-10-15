-- Ökaro and Associates Property & Tenant Management Schema
-- Hostinger import note:
-- Select the target database in phpMyAdmin before importing this file.

-- Roles
CREATE TABLE roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id TINYINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  coin_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role_id);

-- Buildings
CREATE TABLE buildings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address_line1 VARCHAR(200) NOT NULL,
  address_line2 VARCHAR(200) NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NULL,
  CONSTRAINT fk_buildings_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Units
CREATE TABLE units (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id INT UNSIGNED NOT NULL,
  unit_number VARCHAR(50) NOT NULL,
  floor VARCHAR(20) NULL,
  bedrooms TINYINT UNSIGNED DEFAULT 0,
  bathrooms TINYINT UNSIGNED DEFAULT 0,
  status ENUM('AVAILABLE','OCCUPIED','MAINTENANCE') DEFAULT 'AVAILABLE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NULL,
  CONSTRAINT fk_units_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_units_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE UNIQUE INDEX uq_units_building_unit_floor ON units(building_id, unit_number, floor);
CREATE INDEX idx_units_building ON units(building_id);

-- Tenants
CREATE TABLE tenants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  unit_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,
  room_number VARCHAR(50) NULL,
  move_in_date DATE NOT NULL,
  move_out_date DATE NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NULL,
  CONSTRAINT fk_tenants_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_tenants_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_tenants_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_tenants_unit ON tenants(unit_id);
CREATE INDEX idx_tenants_user ON tenants(user_id);

-- Rents (recurring rent agreement per tenant/unit)
CREATE TABLE rents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  unit_id INT UNSIGNED NOT NULL,
  annual_amount DECIMAL(10,2) NOT NULL,
  due_day TINYINT UNSIGNED NOT NULL COMMENT 'Day of month rent is due (1-31)',
  start_date DATE NOT NULL,
  end_date DATE NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rents_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rents_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_rents_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_rents_tenant ON rents(tenant_id);
CREATE INDEX idx_rents_unit ON rents(unit_id);

-- Payments
CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rent_id INT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) NULL,
  reference VARCHAR(100) NULL,
  notes VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_rent FOREIGN KEY (rent_id) REFERENCES rents(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_payments_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_payments_rent ON payments(rent_id);
CREATE INDEX idx_payments_date ON payments(payment_date);

-- Seed roles
INSERT INTO roles (name) VALUES ('admin'), ('manager'), ('tenant')
  ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Example admin user (password: ChangeMe123!)
INSERT INTO users (name, email, password, role_id, is_active)
VALUES (
  'Super Admin',
  'admin@howsey.local',
  '$2y$10$5TtFNOvIvxwXjYFslhdvhui6bfxcqdvLXzskSLYzVkHgU11oJTfnG',
  (SELECT id FROM roles WHERE name = 'admin' LIMIT 1),
  1
) ON DUPLICATE KEY UPDATE email = email;
