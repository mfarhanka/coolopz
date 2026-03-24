<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

coolopz_logout();

header('Location: login.php');
exit;