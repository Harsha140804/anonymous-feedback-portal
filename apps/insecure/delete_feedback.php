<?php

session_start();

require_once 'database.php';

// Require authentication.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only accept deletion requests through POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

$user_id = (int) $_SESSION["user_id"];

$feedback_id = filter_input(
    INPUT_POST,
    "feedback_id",
    FILTER_VALIDATE_INT
);

if (!$feedback_id || $feedback_id < 1) {
    http_response_code(400);
    exit("Invalid feedback ID.");
}

// Delete only feedback owned by the logged-in user.
$stmt = $connection->prepare(
    "DELETE FROM feedback
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param("ii", $feedback_id, $user_id);

if ($stmt->execute()) {
    $stmt->close();

    header("Location: feedback_history.php");
    exit;
}

$stmt->close();

http_response_code(500);
exit("Unable to delete feedback. Please try again.");
