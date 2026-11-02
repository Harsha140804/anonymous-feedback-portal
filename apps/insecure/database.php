<?php
declare(strict_types=1);

// The baseline is intentionally vulnerable at selected request handlers.
// Database credentials still come from the local test environment.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'anonymous_feedback';
$username = getenv('DB_USER') ?: 'feedback_app';
$password = getenv('DB_PASSWORD');
if ($password === false) {
    http_response_code(500);
    exit('Database configuration missing.');
}

try {
    $connection = new mysqli($host, $username, $password, $dbname);
    $connection->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getCode());
    http_response_code(503);
    exit('Database unavailable.');
}
