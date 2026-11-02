```php
<?php

session_start();

require_once 'database.php';

// Only logged-in users can access this page.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Retrieve only feedback belonging to the logged-in user.
$stmt = $connection->prepare(
    "SELECT id, subject, message, created_at, updated_at
     FROM feedback
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feedback History</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="container">

    <h1>My Feedback History</h1>

    <p>View the feedback you have previously submitted.</p>

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

                <p><?= nl2br($feedback["message"]) ?></p>

                <small>
                    Submitted:
                    <?= htmlspecialchars(
                        $feedback["created_at"],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </small>

                <?php if ($feedback["updated_at"] !== $feedback["created_at"]): ?>
                    <br>
                    <small>
                        Last updated:
                        <?= htmlspecialchars(
                            $feedback["updated_at"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </small>
                <?php endif; ?>

                <div class="feedback-actions">
    <a href="edit_feedback.php?id=<?= (int) $feedback["id"] ?>">
        Edit
    </a>

    <form
        method="POST"
        action="delete_feedback.php"
        onsubmit="return confirm('Are you sure you want to delete this feedback?');"
    >
        <input
            type="hidden"
            name="feedback_id"
            value="<?= (int) $feedback["id"] ?>"
        >

        <button type="submit" class="delete-button">
            Delete
        </button>
    </form>
</div>

            </article>

        <?php endwhile; ?>

    <?php else: ?>

        <p>You haven't submitted any feedback yet.</p>

    <?php endif; ?>

    <p>
        <a href="submit_feedback.php">Submit New Feedback</a>
    </p>

    <p>
        <a href="feedback.php">Back to Dashboard</a>
    </p>

</main>

</body>
</html>

<?php
$stmt->close();
?>
```