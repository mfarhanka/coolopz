<?php
declare(strict_types=1);

return [
    'host' => getenv('COOLOPZ_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('COOLOPZ_DB_PORT') ?: 3306),
    'database' => getenv('COOLOPZ_DB_NAME') ?: 'coolopz_portal',
    'username' => getenv('COOLOPZ_DB_USER') ?: 'root',
    'password' => getenv('COOLOPZ_DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];