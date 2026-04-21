CREATE DATABASE IF NOT EXISTS coolopz_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coolopz_portal;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    role_name VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    customer_type VARCHAR(50) NOT NULL,
    notes VARCHAR(255) NOT NULL,
    renewal_status VARCHAR(50) NOT NULL,
    rating DECIMAL(3,1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_name (name)
);

CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(190) NOT NULL,
    service_type VARCHAR(80) NOT NULL,
    technician_team VARCHAR(80) NOT NULL,
    zone VARCHAR(80) NOT NULL,
    status VARCHAR(40) NOT NULL,
    priority_level VARCHAR(40) NOT NULL,
    billed_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (email, full_name, role_name, password_hash)
VALUES ('admin@coolopz.local', 'Admin User', 'Operations Admin', '$2y$10$C9hFVK0LFCUv2aw9zR/ChOZRpZHcC/UmCJyFpAjIjFzSM3t.O2b82')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    role_name = VALUES(role_name),
    password_hash = VALUES(password_hash);

INSERT INTO customers (name, phone_number, email, customer_type, notes, renewal_status, rating)
VALUES
    ('Meridian Office Park', '+60 12-801 4401', 'ops@meridianofficepark.my', 'Commercial', 'Quarterly preventive maintenance for 32 indoor units.', 'Contract Active', 4.9),
    ('Bloom Pediatric Center', '+60 17-455 9832', 'facilities@bloompediatric.my', 'Commercial', 'High-priority service account with same-day response terms.', 'Priority', 4.8),
    ('Casa Bayu Residence', '+60 11-2618 4026', NULL, 'Residential', 'Renewal proposal prepared for multi-unit maintenance package.', 'Renewal Due', 4.7),
    ('Northpoint Suites', '+60 16-632 7780', 'admin@northpointsuites.my', 'Residential', 'Send maintenance summary and invoice pack.', 'Contract Active', 4.6),
    ('Pelita Food Hall', '+60 14-718 6405', NULL, 'Commercial', 'Confirm next preventive maintenance slot for kitchen zone.', 'Contract Active', 4.8),
    ('Harbor Dental Clinic', '+60 19-304 1527', 'support@harbordental.my', 'Commercial', 'Collect feedback after completed gas top-up service.', 'Renewal Due', 4.5)
ON DUPLICATE KEY UPDATE
    phone_number = VALUES(phone_number),
    email = VALUES(email),
    customer_type = VALUES(customer_type),
    notes = VALUES(notes),
    renewal_status = VALUES(renewal_status),
    rating = VALUES(rating);

INSERT INTO jobs (ticket_number, customer_name, service_type, technician_team, zone, status, priority_level, billed_amount, notes)
VALUES
    ('#JOB-2048', 'Northpoint Suites', 'Repair', 'Team Alpha', 'KL Central', 'Urgent', 'High', 2800.00, 'Awaiting compressor parts after diagnosis.'),
    ('#JOB-2045', 'Pelita Food Hall', 'Maintenance', 'Team Delta', 'Shah Alam', 'In Progress', 'Medium', 1800.00, 'Routine maintenance in progress.'),
    ('#JOB-2042', 'Riverview Co-Working', 'Installation', 'Team Sigma', 'Putrajaya', 'Queued', 'Medium', 4200.00, 'Installation queued for afternoon handover.'),
    ('#JOB-2039', 'Harbor Dental Clinic', 'Gas Top-Up', 'Team Nova', 'Ampang', 'Completed', 'Low', 950.00, 'Completed and signed off.'),
    ('#JOB-2038', 'Meridian Office Park', 'Preventive Maintenance', 'Team Orion', 'KLCC', 'Completed', 'Low', 3600.00, 'Monthly service completed.'),
    ('#JOB-2037', 'Bloom Pediatric Center', 'Repair', 'Team Echo', 'Damansara', 'In Progress', 'High', 2200.00, 'Electrical fault under inspection.'),
    ('#JOB-2036', 'Casa Bayu Residence', 'Maintenance', 'Team Nova', 'Cheras', 'Queued', 'Low', 650.00, 'Scheduled maintenance visit.'),
    ('#JOB-2035', 'Skyline Residence', 'Repair', 'Team Delta', 'Mont Kiara', 'Urgent', 'High', 3100.00, 'Urgent outage reported by management.')
ON DUPLICATE KEY UPDATE
    customer_name = VALUES(customer_name),
    service_type = VALUES(service_type),
    technician_team = VALUES(technician_team),
    zone = VALUES(zone),
    status = VALUES(status),
    priority_level = VALUES(priority_level),
    billed_amount = VALUES(billed_amount),
    notes = VALUES(notes);