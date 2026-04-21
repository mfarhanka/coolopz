<?php
declare(strict_types=1);

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

    return $pdo;
}