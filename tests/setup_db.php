<?php
declare(strict_types=1);

/**
 * Initialise disposable CI database tables with the repository schema.
 * Run only against the temporary database provisioned for integration tests.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'anonymous_feedback';
$rootPassword = getenv('DB_ROOT_PASSWORD');
if ($rootPassword === false || $rootPassword === '') {
    fwrite(STDERR, "DB_ROOT_PASSWORD must be configured for test setup.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($host, 'root', $rootPassword);
$db->set_charset('utf8mb4');

$sql = file_get_contents(__DIR__ . '/../database/schema.sql');
if ($sql === false || trim($sql) === '') {
    throw new RuntimeException('SQL schema file missing or empty.');
}

$db->multi_query($sql);
do {
    $result = $db->store_result();
    if ($result instanceof mysqli_result) {
        $result->free();
    }
} while ($db->more_results() && $db->next_result());

// The disposable CI database should not contain any existing real user data.
$db->select_db($dbName);
$db->query('DELETE FROM feedback');
$db->query('DELETE FROM users');

echo "Disposable database initialised from database/schema.sql\n";
