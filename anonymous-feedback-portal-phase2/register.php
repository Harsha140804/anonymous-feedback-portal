<?php

require_once 'database.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter both a username and password.";
    } elseif (strlen($username) > 50) {
        $error = "Username must be 50 characters or fewer.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } else {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $connection->prepare(
            "INSERT INTO users (username, password_hash)
             VALUES (?, ?)"
        );

        $stmt->bind_param(
            "ss",
            $username,
            $passwordHash
        );

        if ($stmt->execute()) {
            $success = "Registration successful! You can now log in.";
        } else {
            if ($stmt->errno === 1062) {
                $error = "That username is already taken.";
            } else {
                $error = "Registration failed. Please try again.";
            }
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
    <title>Register - Anonymous Feedback Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<main class="container">

    <h1>Create an Account</h1>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p style="color: green;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <p>
            <label for="username">Username</label><br>
            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                required
            >
        </p>

        <p>
            <label for="password">Password</label><br>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >
        </p>

        <button type="submit" class="button">
            Register
        </button>

    </form>

    <p>
        Already registered?
        <a href="login.php">Log in</a>
    </p>

    <p>
        <a href="index.php">Back to Home</a>
    </p>

</main>

</body>
</html>