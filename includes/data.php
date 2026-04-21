<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

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
        'available_technicians' => (int) $pdo->query("SELECT COUNT(DISTINCT technician_team) FROM jobs WHERE status IN ('Urgent', 'In Progress', 'Queued')")->fetchColumn(),
        'completed_today' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn(),
    ];
}

function coolopz_fetch_priority_jobs(int $limit = 4): array
{
    $pdo = coolopz_db();
    $statement = $pdo->prepare(
        "SELECT ticket_number, customer_name, service_type, technician_team, zone, status, priority_level
         FROM jobs
         ORDER BY FIELD(status, 'Urgent', 'In Progress', 'Queued', 'Completed'), id DESC
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
        "SELECT id, ticket_number, customer_name, service_type, technician_team, zone, status, priority_level, billed_amount, notes, created_at
         FROM jobs
         ORDER BY FIELD(status, 'Urgent', 'In Progress', 'Queued', 'Completed'), id DESC"
    );

    return $statement->fetchAll();
}

function coolopz_find_job(int $jobId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, ticket_number, customer_name, service_type, technician_team, zone, status, priority_level, billed_amount, notes FROM jobs WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $jobId]);
    $job = $statement->fetch();

    return $job === false ? null : $job;
}

function coolopz_create_job(array $jobData): void
{
    $statement = coolopz_db()->prepare(
        'INSERT INTO jobs (ticket_number, customer_name, service_type, technician_team, zone, status, priority_level, billed_amount, notes)
         VALUES (:ticket_number, :customer_name, :service_type, :technician_team, :zone, :status, :priority_level, :billed_amount, :notes)'
    );

    $statement->execute([
        'ticket_number' => $jobData['ticket_number'],
        'customer_name' => $jobData['customer_name'],
        'service_type' => $jobData['service_type'],
        'technician_team' => $jobData['technician_team'],
        'zone' => $jobData['zone'],
        'status' => $jobData['status'],
        'priority_level' => $jobData['priority_level'],
        'billed_amount' => $jobData['billed_amount'],
        'notes' => $jobData['notes'],
    ]);
}

function coolopz_update_job(int $jobId, array $jobData): void
{
    $statement = coolopz_db()->prepare(
        'UPDATE jobs
         SET ticket_number = :ticket_number,
             customer_name = :customer_name,
             service_type = :service_type,
             technician_team = :technician_team,
             zone = :zone,
             status = :status,
             priority_level = :priority_level,
             billed_amount = :billed_amount,
             notes = :notes
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $jobId,
        'ticket_number' => $jobData['ticket_number'],
        'customer_name' => $jobData['customer_name'],
        'service_type' => $jobData['service_type'],
        'technician_team' => $jobData['technician_team'],
        'zone' => $jobData['zone'],
        'status' => $jobData['status'],
        'priority_level' => $jobData['priority_level'],
        'billed_amount' => $jobData['billed_amount'],
        'notes' => $jobData['notes'],
    ]);
}

function coolopz_delete_job(int $jobId): void
{
    $statement = coolopz_db()->prepare('DELETE FROM jobs WHERE id = :id');
    $statement->execute(['id' => $jobId]);
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

function coolopz_find_customer(int $customerId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, name, phone_number, email, notes FROM customers WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $customerId]);
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
        "SELECT service_type, COUNT(*) AS total
         FROM jobs
         GROUP BY service_type
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