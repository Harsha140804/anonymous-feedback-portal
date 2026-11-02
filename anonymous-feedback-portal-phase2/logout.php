<?php

require_once 'session_config.php';

// Clear all session data.
$_SESSION = [];

// Destroy the current session.
session_destroy();

// Redirect to the home page.
header("Location: index.php");
exit;