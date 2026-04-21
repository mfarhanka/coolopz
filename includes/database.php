<?php
declare(strict_types=1);

function coolopz_default_services(): array
{
    return [
        ['name' => 'Chemical Service', 'default_price' => 250.00, 'notes' => 'Deep cleaning service for indoor and outdoor units.'],
        ['name' => 'Installation', 'default_price' => 450.00, 'notes' => 'Standard aircond installation service.'],
        ['name' => 'Repair', 'default_price' => 180.00, 'notes' => 'General troubleshooting and repair work.'],
        ['name' => 'Transport Fee', 'default_price' => 50.00, 'notes' => 'Additional transport or outstation charge.'],
    ];
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
         ON DUPLICATE KEY UPDATE
             default_price = VALUES(default_price),
             notes = VALUES(notes)'
    );

    foreach (coolopz_default_services() as $service) {
        $statement->execute([
            'name' => $service['name'],
            'default_price' => $service['default_price'],
            'notes' => $service['notes'],
        ]);
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

    coolopz_ensure_customer_contact_columns($pdo);
    coolopz_ensure_services_table($pdo);

    return $pdo;
}