<?php

require_once 'session_config.php';

require_once 'database.php';

// Require authentication.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only administrators can search all feedback.
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

$search = trim($_GET["search"] ?? "");
$result = null;

if ($search !== "") {

    // SECURE: Use a prepared statement to prevent SQL injection.
    $sql = "SELECT feedback.id,
                   feedback.subject,
                   feedback.message,
                   users.username
            FROM feedback
            INNER JOIN users
                ON feedback.user_id = users.id
            WHERE feedback.subject LIKE ?
            ORDER BY feedback.created_at DESC";

    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        exit("Unable to process search.");
    }

    // Add wildcard characters for partial matching.
    $search_term = "%" . $search . "%";

    $stmt->bind_param("s", $search_term);
    $stmt->execute();

    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Feedback</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="container">

    <h1>Admin Feedback Search</h1>

    <form method="GET" action="search_feedback.php">

        <label for="search">Search by feedback subject</label>

        <input
            type="text"
            id="search"
            name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <button type="submit">Search</button>

    </form>

    <?php if ($result !== null): ?>

        <h2>Search Results</h2>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($feedback = $result->fetch_assoc()): ?>

                <article class="feedback-card">

                    <h3>
                        <?= htmlspecialchars($feedback["subject"], ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <p>
                        <?= nl2br(htmlspecialchars($feedback["message"], ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                    <small>
                        Submitted by:
                        <?= htmlspecialchars($feedback["username"], ENT_QUOTES, 'UTF-8') ?>
                    </small>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No matching feedback found.</p>

        <?php endif; ?>

        <?php
        $result->free();
        $stmt->close();
        ?>

    <?php endif; ?>

    <p><a href="admin.php">Back to Admin Panel</a></p>

</main>

</body>
</html>
