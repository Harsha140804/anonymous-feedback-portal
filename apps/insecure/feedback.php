<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<main class="container">

    <h1>Anonymous Feedback Portal</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>!
    </p>

    <p>Submit feedback and manage your previous submissions.</p>

    <div class="actions">
        <a href="submit_feedback.php" class="button">
            Submit Feedback
        </a>

        <a href="feedback_history.php" class="button secondary">
            My Feedback
        </a>
    </div>

    <?php if (($_SESSION["role"] ?? "") === "admin"): ?>
        <p>
            <a href="admin.php">Admin Panel</a>
        </p>
    <?php endif; ?>

    <p>
        <a href="logout.php">Logout</a>
    </p>

</main>

</body>
</html>