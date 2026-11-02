
<?php

session_start();

require_once 'database.php';

// Require authentication.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$error = "";

// Get the feedback ID from the URL.
$feedback_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$feedback_id || $feedback_id < 1) {
    http_response_code(400);
    exit("Invalid feedback ID.");
}

// INTENTIONALLY VULNERABLE:
// Fetch feedback using only its ID.
// There is no ownership verification.
$stmt = $connection->prepare(
    "SELECT id, subject, message
     FROM feedback
     WHERE id = ?"
);

$stmt->bind_param("i", $feedback_id);
$stmt->execute();

$result = $stmt->get_result();
$feedback = $result->fetch_assoc();
$stmt->close();

if (!$feedback) {
    http_response_code(404);
    exit("Feedback not found.");
}

$subject = $feedback["subject"];
$message = $feedback["message"];

// Process the edit form.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($subject === "" || $message === "") {
        $error = "Please complete both fields.";
    } elseif (strlen($subject) > 150) {
        $error = "The subject must not exceed 150 characters.";
    } else {

        // INTENTIONALLY VULNERABLE:
        // Update using only the feedback ID.
        // There is no ownership verification.
        $stmt = $connection->prepare(
            "UPDATE feedback
             SET subject = ?, message = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "ssi",
            $subject,
            $message,
            $feedback_id
        );

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: feedback_history.php");
            exit;
        } else {
            $error = "Unable to update feedback. Please try again.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Feedback</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="container">

    <h1>Edit Feedback</h1>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="edit_feedback.php?id=<?= (int) $feedback_id ?>">

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

        <button type="submit">Save Changes</button>

    </form>

    <p><a href="feedback_history.php">Cancel and return to history</a></p>

</main>

</body>
</html>
