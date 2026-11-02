<?php

require_once 'session_config.php';

require_once 'database.php';

// Require authentication.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only administrators can access this page.
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator privileges are required.");
}

// Retrieve all feedback for the administrator.
$stmt = $connection->prepare(
    "SELECT
        feedback.id,
        feedback.subject,
        feedback.message,
        feedback.created_at,
        users.username
     FROM feedback
     INNER JOIN users ON feedback.user_id = users.id
     ORDER BY feedback.created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="container">

    <h1>Admin Panel</h1>

    <p>View feedback submitted by registered users.</p>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($feedback = $result->fetch_assoc()): ?>

            <article class="feedback-card">

                <h2>
                    <?= htmlspecialchars(
                        $feedback["subject"],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars(
                        $feedback["message"],
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>
                </p>

                <p>
                    <strong>Submitted by:</strong>
                    <?= htmlspecialchars(
                        $feedback["username"],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <small>
                    Submitted:
                    <?= htmlspecialchars(
                        $feedback["created_at"],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </small>

            </article>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No feedback has been submitted yet.</p>

    <?php endif; ?>

    <p><a href="feedback.php">Back to Dashboard</a></p>

</main>

</body>
</html>

<?php
$stmt->close();
?>