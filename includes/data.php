<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function coolopz_normalize_service_names(array $serviceNames): array
{
    $normalized = [];

    foreach ($serviceNames as $serviceName) {
        $value = trim((string) $serviceName);
        if ($value !== '') {
            $normalized[] = $value;
        }
    }

    return array_values(array_unique($normalized));
}

function coolopz_service_names_summary(array $serviceNames): string
{
    return implode(', ', coolopz_normalize_service_names($serviceNames));
}

function coolopz_normalize_technician_names(array $technicianNames): array
{
    $normalized = [];

    foreach ($technicianNames as $technicianName) {
        $value = trim((string) $technicianName);
        if ($value !== '') {
            $normalized[] = $value;
        }
    }

    return array_values(array_unique($normalized));
}

function coolopz_technician_names_summary(array $technicianNames): string
{
    return implode(', ', coolopz_normalize_technician_names($technicianNames));
}

function coolopz_parse_summary_names(?string $summary): array
{
    if ($summary === null || trim($summary) === '') {
        return [];
    }

    return coolopz_normalize_technician_names(explode(',', $summary));
}

function coolopz_app_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = str_replace('\\', '/', dirname($scriptName));

    if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
        $basePath = '';
    }

    $normalizedPath = ltrim($path, '/');

    return rtrim($scheme . '://' . $host . $basePath, '/') . ($normalizedPath !== '' ? '/' . $normalizedPath : '');
}

function coolopz_job_client_update_url(string $token): string
{
    return coolopz_app_url('job-client-update.php?token=' . urlencode($token));
}

function coolopz_job_client_whatsapp_url(array $job): string
{
    $jobToken = (string) ($job['client_update_token'] ?? '');
    $jobTicket = (string) ($job['ticket_number'] ?? 'your service job');
    $customerName = trim((string) ($job['customer_name'] ?? ''));
    $greeting = $customerName !== '' ? 'Hi ' . $customerName . ',' : 'Hi,';
    $message = $greeting . " Please update the site details for job " . $jobTicket . ': ' . coolopz_job_client_update_url($jobToken);

    return 'https://wa.me/?text=' . rawurlencode($message);
}

function coolopz_is_valid_phone_number(string $phoneNumber): bool
{
    $trimmed = trim($phoneNumber);

    if ($trimmed === '') {
        return true;
    }

    if (str_contains($trimmed, '@')) {
        return false;
    }

    return preg_match('/^\+?[0-9][0-9\s\-()]{5,}$/', $trimmed) === 1;
}

function coolopz_normalize_service_lines(array $serviceNames, array $servicePrices): array
{
    $linesByName = [];

    foreach ($serviceNames as $index => $serviceName) {
        $normalizedName = trim((string) $serviceName);
        if ($normalizedName === '') {
            continue;
        }

        $rawPrice = $servicePrices[$index] ?? 0;
        $linePrice = is_numeric((string) $rawPrice) ? max(0, (float) $rawPrice) : 0.0;
        $linesByName[$normalizedName] = [
            'service_name' => $normalizedName,
            'line_price' => $linePrice,
        ];
    }

    return array_values($linesByName);
}

function coolopz_fetch_job_service_lines(int $jobId): array
{
    $statement = coolopz_db()->prepare(
        'SELECT service_name, line_price FROM job_services WHERE job_id = :job_id ORDER BY service_name ASC'
    );
    $statement->execute(['job_id' => $jobId]);

    return $statement->fetchAll();
}

function coolopz_service_price_map(): array
{
    $statement = coolopz_db()->query('SELECT name, default_price FROM services');
    $rows = $statement->fetchAll();

    $priceMap = [];

    foreach ($rows as $row) {
        $priceMap[(string) $row['name']] = (float) $row['default_price'];
    }

    return $priceMap;
}

function coolopz_calculate_billed_amount(array $serviceLines): float
{
    $total = 0.0;

    foreach ($serviceLines as $serviceLine) {
        $rawLinePrice = $serviceLine['line_price'] ?? 0;
        $total += is_numeric((string) $rawLinePrice) ? max(0, (float) $rawLinePrice) : 0.0;
    }

    return $total;
}

function coolopz_replace_job_services(PDO $pdo, int $jobId, array $serviceLines): void
{
    $deleteStatement = $pdo->prepare('DELETE FROM job_services WHERE job_id = :job_id');
    $deleteStatement->execute(['job_id' => $jobId]);

    $insertStatement = $pdo->prepare(
        'INSERT INTO job_services (job_id, service_name, line_price) VALUES (:job_id, :service_name, :line_price)'
    );

    foreach ($serviceLines as $serviceLine) {
        $insertStatement->execute([
            'job_id' => $jobId,
            'service_name' => $serviceLine['service_name'],
            'line_price' => number_format((float) $serviceLine['line_price'], 2, '.', ''),
        ]);
    }
}

function coolopz_status_badge_class(string $status): string
{
    return match ($status) {
        'Urgent', 'Priority' => 'status-urgent',
        'In Progress', 'Contract Active' => 'status-progress',
        'Queued', 'Renewal Due', 'Review' => 'status-queued',
        'Completed' => 'status-complete',
        default => 'status-queued',
    };
}

function coolopz_fetch_dashboard_metrics(): array
{
    $pdo = coolopz_db();

    return [
        'active_jobs' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('Urgent', 'In Progress', 'Queued')")->fetchColumn(),
        'available_technicians' => (int) $pdo->query("SELECT COUNT(DISTINCT technician_team) FROM jobs WHERE status IN ('Urgent', 'In Progress', 'Queued') AND TRIM(technician_team) <> ''")->fetchColumn(),
        'completed_today' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn(),
    ];
}

function coolopz_fetch_priority_jobs(int $limit = 4): array
{
    $pdo = coolopz_db();
    $statement = $pdo->prepare(
        "SELECT jobs.ticket_number,
                jobs.customer_name,
                COALESCE(NULLIF(GROUP_CONCAT(job_services.service_name ORDER BY job_services.service_name SEPARATOR ', '), ''), jobs.service_type) AS service_type,
                jobs.technician_team,
                jobs.attending_technicians,
                jobs.site_address,
                jobs.person_in_charge_name,
                jobs.person_in_charge_contact,
                jobs.client_update_token,
                jobs.status,
                jobs.priority_level
         FROM jobs
         LEFT JOIN job_services ON job_services.job_id = jobs.id
         GROUP BY jobs.id
              ORDER BY FIELD(jobs.status, 'Urgent', 'In Progress', 'Queued', 'Completed'), jobs.id DESC
         LIMIT :limit"
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function coolopz_fetch_job_metrics(): array
{
    $pdo = coolopz_db();

    return [
        'open_tickets' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status <> 'Completed'")->fetchColumn(),
        'on_site' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('Urgent', 'In Progress')")->fetchColumn(),
        'awaiting_parts' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE notes LIKE '%parts%'")->fetchColumn(),
    ];
}

function coolopz_job_service_types(): array
{
    $statement = coolopz_db()->query('SELECT name FROM services ORDER BY name ASC');
    $services = $statement->fetchAll(PDO::FETCH_COLUMN);

    return $services !== [] ? array_values($services) : array_map(
        static fn (array $service): string => $service['name'],
        coolopz_default_services()
    );
}

function coolopz_job_statuses(): array
{
    return [
        'Queued',
        'In Progress',
        'Urgent',
        'Completed',
    ];
}

function coolopz_job_priorities(): array
{
    return [
        'Low',
        'Medium',
        'High',
    ];
}

function coolopz_generate_job_ticket_number(): string
{
    $nextSequence = (int) coolopz_db()->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED)), 2034) + 1
         FROM jobs
         WHERE ticket_number LIKE '#JOB-%'"
    )->fetchColumn();

    return sprintf('#JOB-%04d', $nextSequence);
}

function coolopz_fetch_jobs(): array
{
    $statement = coolopz_db()->query(
        "SELECT jobs.id,
                jobs.ticket_number,
                jobs.customer_name,
                COALESCE(NULLIF(GROUP_CONCAT(job_services.service_name ORDER BY job_services.service_name SEPARATOR ', '), ''), jobs.service_type) AS service_type,
                jobs.technician_team,
                jobs.attending_technicians,
                jobs.site_address,
                jobs.google_maps_url,
                jobs.person_in_charge_name,
                jobs.person_in_charge_contact,
                jobs.client_update_token,
                jobs.status,
                jobs.priority_level,
                jobs.billed_amount,
                jobs.notes,
                jobs.created_at
         FROM jobs
         LEFT JOIN job_services ON job_services.job_id = jobs.id
         GROUP BY jobs.id
         ORDER BY FIELD(jobs.status, 'Urgent', 'In Progress', 'Queued', 'Completed'), jobs.id DESC"
    );

    return $statement->fetchAll();
}

function coolopz_find_job(int $jobId): ?array
{
    $statement = coolopz_db()->prepare(
        "SELECT jobs.id,
                jobs.ticket_number,
                jobs.customer_name,
                COALESCE(NULLIF(GROUP_CONCAT(job_services.service_name ORDER BY job_services.service_name SEPARATOR ', '), ''), jobs.service_type) AS service_type,
                jobs.technician_team,
                jobs.attending_technicians,
                jobs.site_address,
                jobs.google_maps_url,
                jobs.person_in_charge_name,
                jobs.person_in_charge_contact,
                jobs.client_update_token,
                jobs.status,
                jobs.priority_level,
                jobs.billed_amount,
                jobs.notes
         FROM jobs
         LEFT JOIN job_services ON job_services.job_id = jobs.id
         WHERE jobs.id = :id
         GROUP BY jobs.id
         LIMIT 1"
    );
    $statement->execute(['id' => $jobId]);
    $job = $statement->fetch();

    return $job === false ? null : $job;
}

function coolopz_create_job(array $jobData): void
{
    $pdo = coolopz_db();
    $statement = $pdo->prepare(
        'INSERT INTO jobs (ticket_number, customer_name, service_type, technician_team, attending_technicians, site_address, google_maps_url, person_in_charge_name, person_in_charge_contact, client_update_token, status, priority_level, billed_amount, notes)
         VALUES (:ticket_number, :customer_name, :service_type, :technician_team, :attending_technicians, :site_address, :google_maps_url, :person_in_charge_name, :person_in_charge_contact, :client_update_token, :status, :priority_level, :billed_amount, :notes)'
    );

    $pdo->beginTransaction();

    try {
        $statement->execute([
            'ticket_number' => $jobData['ticket_number'],
            'customer_name' => $jobData['customer_name'],
            'service_type' => coolopz_service_names_summary(array_column($jobData['service_lines'], 'service_name')),
            'technician_team' => $jobData['technician_team'],
            'attending_technicians' => coolopz_technician_names_summary($jobData['attending_technicians']),
            'site_address' => $jobData['site_address'],
            'google_maps_url' => $jobData['google_maps_url'],
            'person_in_charge_name' => $jobData['person_in_charge_name'],
            'person_in_charge_contact' => $jobData['person_in_charge_contact'],
            'client_update_token' => $jobData['client_update_token'] !== '' ? $jobData['client_update_token'] : coolopz_issue_job_client_update_token($pdo),
            'status' => $jobData['status'],
            'priority_level' => $jobData['priority_level'],
            'billed_amount' => $jobData['billed_amount'],
            'notes' => $jobData['notes'],
        ]);

        coolopz_replace_job_services($pdo, (int) $pdo->lastInsertId(), $jobData['service_lines']);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function coolopz_update_job(int $jobId, array $jobData): void
{
    $pdo = coolopz_db();
    $statement = $pdo->prepare(
        'UPDATE jobs
         SET ticket_number = :ticket_number,
             customer_name = :customer_name,
             service_type = :service_type,
             technician_team = :technician_team,
             attending_technicians = :attending_technicians,
             site_address = :site_address,
             google_maps_url = :google_maps_url,
             person_in_charge_name = :person_in_charge_name,
             person_in_charge_contact = :person_in_charge_contact,
             client_update_token = :client_update_token,
             status = :status,
             priority_level = :priority_level,
             billed_amount = :billed_amount,
             notes = :notes
         WHERE id = :id'
    );

    $pdo->beginTransaction();

    try {
        $statement->execute([
            'id' => $jobId,
            'ticket_number' => $jobData['ticket_number'],
            'customer_name' => $jobData['customer_name'],
            'service_type' => coolopz_service_names_summary(array_column($jobData['service_lines'], 'service_name')),
            'technician_team' => $jobData['technician_team'],
            'attending_technicians' => coolopz_technician_names_summary($jobData['attending_technicians']),
            'site_address' => $jobData['site_address'],
            'google_maps_url' => $jobData['google_maps_url'],
            'person_in_charge_name' => $jobData['person_in_charge_name'],
            'person_in_charge_contact' => $jobData['person_in_charge_contact'],
            'client_update_token' => $jobData['client_update_token'] !== '' ? $jobData['client_update_token'] : coolopz_issue_job_client_update_token($pdo),
            'status' => $jobData['status'],
            'priority_level' => $jobData['priority_level'],
            'billed_amount' => $jobData['billed_amount'],
            'notes' => $jobData['notes'],
        ]);

        coolopz_replace_job_services($pdo, $jobId, $jobData['service_lines']);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function coolopz_delete_job(int $jobId): void
{
    $statement = coolopz_db()->prepare('DELETE FROM jobs WHERE id = :id');
    $statement->execute(['id' => $jobId]);
}

function coolopz_find_job_by_client_token(string $token): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id,
                ticket_number,
                customer_name,
                service_type,
                site_address,
                google_maps_url,
                person_in_charge_name,
                person_in_charge_contact,
                client_update_token
         FROM jobs
         WHERE client_update_token = :token
         LIMIT 1'
    );
    $statement->execute(['token' => $token]);
    $job = $statement->fetch();

    return $job === false ? null : $job;
}

function coolopz_update_job_client_details(string $token, array $clientDetails): void
{
    $statement = coolopz_db()->prepare(
        'UPDATE jobs
         SET site_address = :site_address,
             google_maps_url = :google_maps_url,
             person_in_charge_name = :person_in_charge_name,
             person_in_charge_contact = :person_in_charge_contact
         WHERE client_update_token = :token'
    );

    $statement->execute([
        'token' => $token,
        'site_address' => $clientDetails['site_address'],
        'google_maps_url' => $clientDetails['google_maps_url'],
        'person_in_charge_name' => $clientDetails['person_in_charge_name'],
        'person_in_charge_contact' => $clientDetails['person_in_charge_contact'],
    ]);
}

function coolopz_regenerate_job_client_update_token(int $jobId): string
{
    $pdo = coolopz_db();
    $token = coolopz_issue_job_client_update_token($pdo);
    $statement = $pdo->prepare('UPDATE jobs SET client_update_token = :token WHERE id = :id');
    $statement->execute([
        'id' => $jobId,
        'token' => $token,
    ]);

    return $token;
}

function coolopz_fetch_customer_metrics(): array
{
    $pdo = coolopz_db();

    return [
        'total' => (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
        'with_phone_number' => (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE TRIM(phone_number) <> ''")->fetchColumn(),
        'with_email' => (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE email IS NOT NULL AND TRIM(email) <> ''")->fetchColumn(),
    ];
}

function coolopz_fetch_customers(): array
{
    $pdo = coolopz_db();
    $statement = $pdo->query(
        'SELECT id, name, phone_number, email, notes, created_at
         FROM customers
         ORDER BY id DESC'
    );

    return $statement->fetchAll();
}

function coolopz_customer_phone_lookup_key(string $phoneNumber): string
{
    return preg_replace('/\D+/', '', trim($phoneNumber)) ?? '';
}

function coolopz_find_customer(int $customerId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, name, phone_number, email, notes FROM customers WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $customerId]);
    $customer = $statement->fetch();

    return $customer === false ? null : $customer;
}

function coolopz_find_customer_by_phone_number(string $phoneNumber, ?int $excludeCustomerId = null): ?array
{
    $lookupKey = coolopz_customer_phone_lookup_key($phoneNumber);

    if ($lookupKey === '') {
        return null;
    }

        $sql = 'SELECT id, name, phone_number, email, notes
            FROM customers
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :lookup_key';
    $params = ['lookup_key' => $lookupKey];

    if ($excludeCustomerId !== null) {
        $sql .= ' AND id <> :exclude_customer_id';
        $params['exclude_customer_id'] = $excludeCustomerId;
    }

    $sql .= ' LIMIT 1';

    $statement = coolopz_db()->prepare($sql);
    $statement->execute($params);
    $customer = $statement->fetch();

    return $customer === false ? null : $customer;
}

function coolopz_create_customer(array $customerData): void
{
    $statement = coolopz_db()->prepare(
        'INSERT INTO customers (name, phone_number, email, notes)
         VALUES (:name, :phone_number, :email, :notes)'
    );

    $statement->execute([
        'name' => $customerData['name'],
        'phone_number' => $customerData['phone_number'],
        'email' => $customerData['email'],
        'notes' => $customerData['notes'],
    ]);
}

function coolopz_update_customer(int $customerId, array $customerData): void
{
    $statement = coolopz_db()->prepare(
        'UPDATE customers
         SET name = :name,
             phone_number = :phone_number,
             email = :email,
             notes = :notes
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $customerId,
        'name' => $customerData['name'],
        'phone_number' => $customerData['phone_number'],
        'email' => $customerData['email'],
        'notes' => $customerData['notes'],
    ]);
}

function coolopz_delete_customer(int $customerId): void
{
    $statement = coolopz_db()->prepare('DELETE FROM customers WHERE id = :id');
    $statement->execute(['id' => $customerId]);
}

function coolopz_fetch_report_metrics(): array
{
    $pdo = coolopz_db();

    $monthlyRevenue = (float) $pdo->query('SELECT COALESCE(SUM(billed_amount), 0) FROM jobs')->fetchColumn();
    $totalJobs = max((int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn(), 1);
    $completedJobs = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn();
    $customerRating = (float) $pdo->query('SELECT COALESCE(AVG(rating), 0) FROM customers WHERE rating IS NOT NULL')->fetchColumn();

    return [
        'monthly_revenue' => $monthlyRevenue,
        'completion_rate' => (int) round(($completedJobs / $totalJobs) * 100),
        'customer_rating' => round($customerRating, 1),
    ];
}

function coolopz_fetch_service_breakdown(): array
{
    $pdo = coolopz_db();
    $rows = $pdo->query(
        "SELECT service_name AS service_type, COUNT(*) AS total
         FROM job_services
         GROUP BY service_name
         ORDER BY total DESC"
    )->fetchAll();

    $grandTotal = array_sum(array_map(static fn (array $row): int => (int) $row['total'], $rows));
    $grandTotal = max($grandTotal, 1);

    $items = array_map(
        static function (array $row) use ($grandTotal): array {
            $percentage = (int) round(((int) $row['total'] / $grandTotal) * 100);

            return [
                'service_type' => $row['service_type'],
                'percentage' => $percentage,
            ];
        },
        $rows
    );

    return $items !== [] ? $items : [
        ['service_type' => 'No Data', 'percentage' => 0],
    ];
}

function coolopz_fetch_staff_metrics(): array
{
    $pdo = coolopz_db();

    return [
        'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'admins' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role_name = 'Operations Admin'")->fetchColumn(),
        'staff' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role_name <> 'Operations Admin'")->fetchColumn(),
    ];
}

function coolopz_fetch_staff_users(): array
{
    $statement = coolopz_db()->query(
        'SELECT id, full_name, email, role_name, created_at FROM users ORDER BY created_at DESC, id DESC'
    );

    return $statement->fetchAll();
}

function coolopz_fetch_customer_options(): array
{
    $statement = coolopz_db()->query(
        'SELECT name FROM customers ORDER BY name ASC'
    );

    return $statement->fetchAll();
}

function coolopz_fetch_assignable_staff(): array
{
    $statement = coolopz_db()->query(
        "SELECT id, full_name, role_name
         FROM users
         WHERE role_name LIKE 'Technician%'
         ORDER BY full_name ASC"
    );

    return $statement->fetchAll();
}

function coolopz_find_staff_user(int $userId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, full_name, email, role_name, created_at FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    return $user === false ? null : $user;
}

function coolopz_create_staff_user(string $fullName, string $email, string $roleName, string $password): void
{
    $statement = coolopz_db()->prepare(
        'INSERT INTO users (email, full_name, role_name, password_hash) VALUES (:email, :full_name, :role_name, :password_hash)'
    );

    $statement->execute([
        'email' => $email,
        'full_name' => $fullName,
        'role_name' => $roleName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function coolopz_reset_staff_password(int $userId, string $password): void
{
    $statement = coolopz_db()->prepare(
        'UPDATE users SET password_hash = :password_hash WHERE id = :id'
    );

    $statement->execute([
        'id' => $userId,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function coolopz_delete_staff_user(int $userId): void
{
    $statement = coolopz_db()->prepare('DELETE FROM users WHERE id = :id');
    $statement->execute(['id' => $userId]);
}

function coolopz_fetch_service_metrics(): array
{
    $pdo = coolopz_db();

    return [
        'total' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
        'priced' => (int) $pdo->query('SELECT COUNT(*) FROM services WHERE default_price > 0')->fetchColumn(),
        'average_price' => (float) $pdo->query('SELECT COALESCE(AVG(default_price), 0) FROM services')->fetchColumn(),
    ];
}

function coolopz_fetch_services(): array
{
    $statement = coolopz_db()->query(
        'SELECT id, name, default_price, notes, created_at FROM services ORDER BY name ASC'
    );

    return $statement->fetchAll();
}

function coolopz_find_service(int $serviceId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, name, default_price, notes FROM services WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $serviceId]);
    $service = $statement->fetch();

    return $service === false ? null : $service;
}

function coolopz_create_service(array $serviceData): void
{
    $statement = coolopz_db()->prepare(
        'INSERT INTO services (name, default_price, notes)
         VALUES (:name, :default_price, :notes)'
    );

    $statement->execute([
        'name' => $serviceData['name'],
        'default_price' => $serviceData['default_price'],
        'notes' => $serviceData['notes'],
    ]);
}

function coolopz_update_service(int $serviceId, array $serviceData): void
{
    $statement = coolopz_db()->prepare(
        'UPDATE services
         SET name = :name,
             default_price = :default_price,
             notes = :notes
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $serviceId,
        'name' => $serviceData['name'],
        'default_price' => $serviceData['default_price'],
        'notes' => $serviceData['notes'],
    ]);
}

function coolopz_delete_service(int $serviceId): void
{
    $statement = coolopz_db()->prepare('DELETE FROM services WHERE id = :id');
    $statement->execute(['id' => $serviceId]);
}