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
    attending_technicians VARCHAR(255) NOT NULL DEFAULT '',
    zone VARCHAR(80) NOT NULL,
    site_address VARCHAR(255) NOT NULL DEFAULT '',
    google_maps_url VARCHAR(255) NOT NULL DEFAULT '',
    person_in_charge_contact VARCHAR(190) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL,
    priority_level VARCHAR(40) NOT NULL,
    billed_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS job_services (
    job_id INT UNSIGNED NOT NULL,
    service_name VARCHAR(120) NOT NULL,
    line_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (job_id, service_name),
    CONSTRAINT fk_job_services_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_service_name (name)
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

INSERT INTO jobs (ticket_number, customer_name, service_type, technician_team, attending_technicians, zone, site_address, google_maps_url, person_in_charge_contact, status, priority_level, billed_amount, notes)
VALUES
    ('#JOB-2048', 'Northpoint Suites', 'Repair', 'Team Alpha', 'Team Alpha, Team Delta', 'KL Central', 'Northpoint Suites, Jalan Sultan Ismail, Kuala Lumpur', 'https://maps.google.com/?q=Northpoint+Suites+Kuala+Lumpur', 'Farid 012-778 3301', 'Urgent', 'High', 2800.00, 'Awaiting compressor parts after diagnosis.'),
    ('#JOB-2045', 'Pelita Food Hall', 'Maintenance', 'Team Delta', 'Team Delta', 'Shah Alam', 'Pelita Food Hall, Seksyen 13, Shah Alam', 'https://maps.google.com/?q=Pelita+Food+Hall+Shah+Alam', 'Aina 014-624 8190', 'In Progress', 'Medium', 1800.00, 'Routine maintenance in progress.'),
    ('#JOB-2042', 'Riverview Co-Working', 'Installation', 'Team Sigma', 'Team Sigma, Team Nova', 'Putrajaya', 'Riverview Co-Working, Presint 5, Putrajaya', 'https://maps.google.com/?q=Riverview+Co-Working+Putrajaya', 'Hakim 013-902 1844', 'Queued', 'Medium', 4200.00, 'Installation queued for afternoon handover.'),
    ('#JOB-2039', 'Harbor Dental Clinic', 'Gas Top-Up', 'Team Nova', 'Team Nova', 'Ampang', 'Harbor Dental Clinic, Jalan Ampang, Kuala Lumpur', 'https://maps.google.com/?q=Harbor+Dental+Clinic+Ampang', 'Dr. Mei 017-338 4500', 'Completed', 'Low', 950.00, 'Completed and signed off.'),
    ('#JOB-2038', 'Meridian Office Park', 'Preventive Maintenance', 'Team Orion', 'Team Orion', 'KLCC', 'Meridian Office Park, Jalan Pinang, Kuala Lumpur', 'https://maps.google.com/?q=Meridian+Office+Park+KLCC', 'Nadia 011-2671 2208', 'Completed', 'Low', 3600.00, 'Monthly service completed.'),
    ('#JOB-2037', 'Bloom Pediatric Center', 'Repair', 'Team Echo', 'Team Echo, Team Alpha', 'Damansara', 'Bloom Pediatric Center, Damansara Utama, Petaling Jaya', 'https://maps.google.com/?q=Bloom+Pediatric+Center+Damansara', 'Sara 019-880 4412', 'In Progress', 'High', 2200.00, 'Electrical fault under inspection.'),
    ('#JOB-2036', 'Casa Bayu Residence', 'Maintenance', 'Team Nova', 'Team Nova', 'Cheras', 'Casa Bayu Residence, Bandar Tun Hussein Onn, Cheras', 'https://maps.google.com/?q=Casa+Bayu+Residence+Cheras', 'Mr. Lim 016-422 3009', 'Queued', 'Low', 650.00, 'Scheduled maintenance visit.'),
    ('#JOB-2035', 'Skyline Residence', 'Repair', 'Team Delta', 'Team Delta, Team Echo', 'Mont Kiara', 'Skyline Residence, Mont Kiara, Kuala Lumpur', 'https://maps.google.com/?q=Skyline+Residence+Mont+Kiara', 'Jasmine 012-991 4088', 'Urgent', 'High', 3100.00, 'Urgent outage reported by management.')
ON DUPLICATE KEY UPDATE
    customer_name = VALUES(customer_name),
    service_type = VALUES(service_type),
    technician_team = VALUES(technician_team),
    attending_technicians = VALUES(attending_technicians),
    zone = VALUES(zone),
    site_address = VALUES(site_address),
    google_maps_url = VALUES(google_maps_url),
    person_in_charge_contact = VALUES(person_in_charge_contact),
    status = VALUES(status),
    priority_level = VALUES(priority_level),
    billed_amount = VALUES(billed_amount),
    notes = VALUES(notes);

INSERT INTO services (name, default_price, notes)
VALUES
    ('Chemical Service', 250.00, 'Deep cleaning service for indoor and outdoor units.'),
    ('Installation', 450.00, 'Standard aircond installation service.'),
    ('Repair', 180.00, 'General troubleshooting and repair work.'),
    ('Transport Fee', 50.00, 'Additional transport or outstation charge.')
ON DUPLICATE KEY UPDATE
    id = id;

INSERT INTO job_services (job_id, service_name, line_price)
SELECT jobs.id, jobs.service_type, COALESCE(services.default_price, 0)
FROM jobs
LEFT JOIN services ON services.name = jobs.service_type
WHERE jobs.service_type <> ''
ON DUPLICATE KEY UPDATE
    service_name = VALUES(service_name),
    line_price = VALUES(line_price);