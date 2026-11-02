<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "127.0.0.1";
$dbname = "anonymous_feedback";
$username = "root";
$password = "";

$connection = new mysqli(
    $host,
    $username,
    $password,
    $dbname
);

if ($connection->connect_error) {
    exit("Database connection failed.");
}

$connection->set_charset("utf8mb4");

echo "Database connection successful!";