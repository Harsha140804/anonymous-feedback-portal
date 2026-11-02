<?php

require_once 'database.php';

$input = $_GET['username'] ?? '';

echo "<h1>SQL Injection Demonstration</h1>";

echo "<h2>Input received</h2>";
echo htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

echo "<h2>Vulnerable query structure</h2>";

$sql = "SELECT id, username FROM users WHERE username = '$input'";

echo "<pre>";
echo htmlspecialchars($sql, ENT_QUOTES, 'UTF-8');
echo "</pre>";

echo "<p>This demonstration displays the query structure only. "
   . "It does not execute the vulnerable query.</p>";
   