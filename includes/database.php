<?php
declare(strict_types=1);

function coolopz_generate_secure_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function coolopz_issue_job_client_update_token(PDO $pdo): string
{
    do {
        $token = coolopz_generate_secure_token();
        $statement = $pdo->prepare('SELECT 1 FROM jobs WHERE client_update_token = :token LIMIT 1');
        $statement->execute(['token' => $token]);
    } while ($statement->fetch() !== false);

    return $token;
}

function coolopz_default_services(): array
{
    return [
        ['name' => 'Chemical Service', 'default_price' => 250.00, 'notes' => 'Deep cleaning service for indoor and outdoor units.'],
        ['name' => 'Installation', 'default_price' => 450.00, 'notes' => 'Standard aircond installation service.'],
        ['name' => 'Repair', 'default_price' => 180.00, 'notes' => 'General troubleshooting and repair work.'],
        ['name' => 'Transport Fee', 'default_price' => 50.00, 'notes' => 'Additional transport or outstation charge.'],
    ];
}

function coolopz_username_slug(string $value, int $fallbackId = 0): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '.', $value) ?? '';
    $value = trim($value, '.');

    if ($value === '') {
        $value = $fallbackId > 0 ? 'user' . $fallbackId : 'user';
    }

    return substr($value, 0, 50);
}

function coolopz_ensure_user_columns(PDO $pdo): void
{
    static $userColumnsEnsured = false;

    if ($userColumnsEnsured) {
        return;
    }

    $userColumnsEnsured = true;

    $columnStatement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $columnStatement->execute([
        'table_name' => 'users',
        'column_name' => 'username',
    ]);

    if ($columnStatement->fetch() === false) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(80) DEFAULT NULL AFTER id");
    }

    $users = $pdo->query('SELECT id, email, full_name, username FROM users ORDER BY id ASC')->fetchAll();
    $reservedUsernames = [];
    $updateStatement = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');

    foreach ($users as $user) {
        $currentUsername = trim((string) ($user['username'] ?? ''));
        if ($currentUsername !== '') {
            $candidate = coolopz_username_slug($currentUsername, (int) $user['id']);
        } else {
            $email = trim((string) ($user['email'] ?? ''));
            $localPart = $email !== '' && str_contains($email, '@') ? strstr($email, '@', true) : '';
            $candidate = coolopz_username_slug($localPart !== false ? (string) $localPart : (string) ($user['full_name'] ?? ''), (int) $user['id']);
        }

        $baseCandidate = $candidate;
        $suffix = 2;
        while (isset($reservedUsernames[$candidate])) {
            $candidate = substr($baseCandidate, 0, max(1, 50 - strlen((string) $suffix))) . $suffix;
            $suffix++;
        }

        $reservedUsernames[$candidate] = true;

        if ($candidate !== $currentUsername) {
            $updateStatement->execute([
                'id' => (int) $user['id'],
                'username' => $candidate,
            ]);
        }
    }

    $pdo->exec('ALTER TABLE users MODIFY COLUMN username VARCHAR(80) NOT NULL');

    $indexStatement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name
         LIMIT 1'
    );
    $indexStatement->execute([
        'table_name' => 'users',
        'index_name' => 'uniq_users_username',
    ]);

    if ($indexStatement->fetch() === false) {
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)');
    }
}

function coolopz_ensure_customer_contact_columns(PDO $pdo): void
{
    static $columnsEnsured = false;

    if ($columnsEnsured) {
        return;
    }

    $columnsEnsured = true;

    $requiredColumns = [
        'phone_number' => "ALTER TABLE customers ADD COLUMN phone_number VARCHAR(30) NOT NULL DEFAULT '' AFTER name",
        'email' => 'ALTER TABLE customers ADD COLUMN email VARCHAR(190) DEFAULT NULL AFTER phone_number',
    ];

    foreach ($requiredColumns as $columnName => $alterSql) {
        $statement = $pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name
             LIMIT 1'
        );
        $statement->execute([
            'table_name' => 'customers',
            'column_name' => $columnName,
        ]);

        if ($statement->fetch() === false) {
            $pdo->exec($alterSql);
        }
    }
}

function coolopz_ensure_services_table(PDO $pdo): void
{
    static $servicesEnsured = false;

    if ($servicesEnsured) {
        return;
    }

    $servicesEnsured = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS services (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            notes VARCHAR(255) NOT NULL DEFAULT \'\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_service_name (name)
        )'
    );

    $statement = $pdo->prepare(
        'INSERT INTO services (name, default_price, notes)
         VALUES (:name, :default_price, :notes)
         ON DUPLICATE KEY UPDATE id = id'
    );

    foreach (coolopz_default_services() as $service) {
        $statement->execute([
            'name' => $service['name'],
            'default_price' => $service['default_price'],
            'notes' => $service['notes'],
        ]);
    }
}

function coolopz_ensure_job_services_table(PDO $pdo): void
{
    static $jobServicesEnsured = false;

    if ($jobServicesEnsured) {
        return;
    }

    $jobServicesEnsured = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS job_services (
            job_id INT UNSIGNED NOT NULL,
            service_name VARCHAR(120) NOT NULL,
            line_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (job_id, service_name),
            CONSTRAINT fk_job_services_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        )'
    );

    $columnStatement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $columnStatement->execute([
        'table_name' => 'job_services',
        'column_name' => 'line_price',
    ]);

    if ($columnStatement->fetch() === false) {
        $pdo->exec("ALTER TABLE job_services ADD COLUMN line_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER service_name");
    }

    $jobs = $pdo->query('SELECT id, service_type FROM jobs')->fetchAll();
    $existingCounts = $pdo->query('SELECT job_id, COUNT(*) AS total FROM job_services GROUP BY job_id')->fetchAll(PDO::FETCH_KEY_PAIR);
    $servicePriceRows = $pdo->query('SELECT name, default_price FROM services')->fetchAll();
    $servicePriceMap = [];
    foreach ($servicePriceRows as $row) {
        $servicePriceMap[(string) $row['name']] = (float) $row['default_price'];
    }

    $insertStatement = $pdo->prepare(
        'INSERT IGNORE INTO job_services (job_id, service_name, line_price) VALUES (:job_id, :service_name, :line_price)'
    );
    $backfillPriceStatement = $pdo->prepare(
        'UPDATE job_services
         SET line_price = :line_price
         WHERE job_id = :job_id
           AND service_name = :service_name
           AND line_price = 0'
    );

    foreach ($jobs as $job) {
        $jobId = (int) $job['id'];
        if (($existingCounts[$jobId] ?? 0) > 0) {
            $jobServiceNames = array_filter(array_map('trim', explode(',', (string) ($job['service_type'] ?? ''))), static fn (string $name): bool => $name !== '');
            foreach (array_values(array_unique($jobServiceNames)) as $serviceName) {
                $backfillPriceStatement->execute([
                    'job_id' => $jobId,
                    'service_name' => $serviceName,
                    'line_price' => $servicePriceMap[$serviceName] ?? 0,
                ]);
            }

            continue;
        }

        $serviceNames = array_filter(array_map('trim', explode(',', (string) ($job['service_type'] ?? ''))), static fn (string $name): bool => $name !== '');

        foreach (array_values(array_unique($serviceNames)) as $serviceName) {
            $insertStatement->execute([
                'job_id' => $jobId,
                'service_name' => $serviceName,
                'line_price' => $servicePriceMap[$serviceName] ?? 0,
            ]);
        }
    }
}

function coolopz_ensure_job_report_photos_table(PDO $pdo): void
{
    static $jobReportPhotosEnsured = false;

    if ($jobReportPhotosEnsured) {
        return;
    }

    $jobReportPhotosEnsured = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS job_report_photos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            service_name VARCHAR(120) NOT NULL DEFAULT \'\',
            report_type VARCHAR(40) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL DEFAULT \'\',
            uploaded_by_name VARCHAR(120) NOT NULL DEFAULT \'\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_job_report_photo (job_id, service_name, report_type),
            CONSTRAINT fk_job_report_photos_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        )'
    );
}

function coolopz_ensure_staff_attendance_table(PDO $pdo): void
{
    static $staffAttendanceEnsured = false;

    if ($staffAttendanceEnsured) {
        return;
    }

    $staffAttendanceEnsured = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS staff_attendance (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            clock_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            clock_out_at DATETIME DEFAULT NULL,
            source VARCHAR(20) NOT NULL DEFAULT \'clock\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_staff_attendance_user_clock_in (user_id, clock_in_at),
            KEY idx_staff_attendance_open_shift (user_id, clock_out_at),
            CONSTRAINT fk_staff_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $columnStatement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $columnStatement->execute([
        'table_name' => 'staff_attendance',
        'column_name' => 'source',
    ]);

    if ($columnStatement->fetch() === false) {
        $pdo->exec("ALTER TABLE staff_attendance ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'clock' AFTER clock_out_at");
    }
}

function coolopz_ensure_staff_attendance_audit_table(PDO $pdo): void
{
    static $staffAttendanceAuditEnsured = false;

    if ($staffAttendanceAuditEnsured) {
        return;
    }

    $staffAttendanceAuditEnsured = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS staff_attendance_audit (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attendance_id INT UNSIGNED DEFAULT NULL,
            action_name VARCHAR(20) NOT NULL,
            changed_by_user_id INT UNSIGNED DEFAULT NULL,
            affected_user_id INT UNSIGNED NOT NULL,
            old_clock_in_at DATETIME DEFAULT NULL,
            old_clock_out_at DATETIME DEFAULT NULL,
            new_clock_in_at DATETIME DEFAULT NULL,
            new_clock_out_at DATETIME DEFAULT NULL,
            old_source VARCHAR(20) DEFAULT NULL,
            new_source VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_staff_attendance_audit_attendance (attendance_id),
            KEY idx_staff_attendance_audit_changed_by (changed_by_user_id),
            KEY idx_staff_attendance_audit_affected_user (affected_user_id),
            CONSTRAINT fk_staff_attendance_audit_changed_by_user FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_staff_attendance_audit_affected_user FOREIGN KEY (affected_user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
}

function coolopz_ensure_job_columns(PDO $pdo): void
{
    static $jobColumnsEnsured = false;

    if ($jobColumnsEnsured) {
        return;
    }

    $jobColumnsEnsured = true;

    $requiredColumns = [
        'attending_technicians' => "ALTER TABLE jobs ADD COLUMN attending_technicians VARCHAR(255) NOT NULL DEFAULT '' AFTER technician_team",
        'site_address' => "ALTER TABLE jobs ADD COLUMN site_address VARCHAR(255) NOT NULL DEFAULT '' AFTER attending_technicians",
        'google_maps_url' => "ALTER TABLE jobs ADD COLUMN google_maps_url VARCHAR(255) NOT NULL DEFAULT '' AFTER site_address",
        'person_in_charge_name' => "ALTER TABLE jobs ADD COLUMN person_in_charge_name VARCHAR(120) NOT NULL DEFAULT '' AFTER google_maps_url",
        'person_in_charge_contact' => "ALTER TABLE jobs ADD COLUMN person_in_charge_contact VARCHAR(190) NOT NULL DEFAULT '' AFTER person_in_charge_name",
        'client_update_token' => "ALTER TABLE jobs ADD COLUMN client_update_token VARCHAR(64) NOT NULL DEFAULT '' AFTER person_in_charge_contact",
    ];

    foreach ($requiredColumns as $columnName => $alterSql) {
        $statement = $pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name
             LIMIT 1'
        );
        $statement->execute([
            'table_name' => 'jobs',
            'column_name' => $columnName,
        ]);

        if ($statement->fetch() === false) {
            $pdo->exec($alterSql);
        }
    }

    $jobsWithoutTokens = $pdo->query("SELECT id FROM jobs WHERE TRIM(client_update_token) = ''")->fetchAll(PDO::FETCH_COLUMN);
    $updateTokenStatement = $pdo->prepare('UPDATE jobs SET client_update_token = :token WHERE id = :id');

    foreach ($jobsWithoutTokens as $jobId) {
        $updateTokenStatement->execute([
            'id' => (int) $jobId,
            'token' => coolopz_issue_job_client_update_token($pdo),
        ]);
    }

    $indexStatement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name
         LIMIT 1'
    );
    $indexStatement->execute([
        'table_name' => 'jobs',
        'index_name' => 'uniq_jobs_client_update_token',
    ]);

    if ($indexStatement->fetch() === false) {
        $pdo->exec('ALTER TABLE jobs ADD UNIQUE KEY uniq_jobs_client_update_token (client_update_token)');
    }
}

function coolopz_db_config(): array
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/database.php';
    }

    return $config;
}

function coolopz_db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = coolopz_db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    coolopz_ensure_user_columns($pdo);
    coolopz_ensure_customer_contact_columns($pdo);
    coolopz_ensure_services_table($pdo);
    coolopz_ensure_job_columns($pdo);
    coolopz_ensure_job_services_table($pdo);
    coolopz_ensure_job_report_photos_table($pdo);
    coolopz_ensure_staff_attendance_table($pdo);
    coolopz_ensure_staff_attendance_audit_table($pdo);

    return $pdo;
}