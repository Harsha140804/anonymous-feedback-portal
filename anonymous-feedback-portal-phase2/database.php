<?php

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
    die("Database connection failed.");
}

$connection->set_charset("utf8mb4");