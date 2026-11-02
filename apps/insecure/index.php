<?php
$pageTitle = "Anonymous Feedback Portal";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <main class="container">
        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>

        <p>Share your feedback through our simple feedback portal.</p>

        <div class="actions">
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button secondary">Register</a>
        </div>
    </main>

</body>
</html>