<?php

session_start();

require_once 'database.php';

// Only logged-in users can submit feedback.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

$subject = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    // Validate the submitted fields.
    if ($subject === "" || $message === "") {
        $error = "Please complete both the subject and message.";
    } elseif (strlen($subject) > 150) {
        $error = "The subject must not exceed 150 characters.";
    } else {

        $user_id = $_SESSION["user_id"];

        $stmt = $connection->prepare(
            "INSERT INTO feedback (user_id, subject, message)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("iss", $user_id, $subject, $message);

        if ($stmt->execute()) {
            $success = "Your feedback was submitted successfully.";

            // Clear the form after successful submission.
            $subject = "";
            $message = "";
        } else {
            $error = "Unable to submit feedback. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="container">

    <h1>Submit Feedback</h1>

    <p>
        Share your feedback below. Your account is used internally
        to manage your submissions.
    </p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p class="success">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="submit_feedback.php">

        <label for="subject">Subject</label>

        <input
            type="text"
            id="subject"
            name="subject"
            maxlength="150"
            value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="message">Feedback message</label>

        <textarea
            id="message"
            name="message"
            rows="7"
            required
        ><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></textarea>

        <button type="submit">Submit Feedback</button>

    </form>

    <p>
        <a href="feedback.php">Back to Dashboard</a>
    </p>

</main>

</body>
</html>