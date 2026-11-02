<?php
declare(strict_types=1);

require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $username)) {
        $error = 'Username must be 3–50 characters (letters, numbers, _, . or -).';
    } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 1024) {
        $error = 'Password must be between 12 and 1024 characters.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $connection->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
            $stmt->bind_param('ss', $username, $passwordHash);
            $stmt->execute();
            $stmt->close();
            $success = 'Registration successful. You can now log in.';
            rotate_csrf();
        } catch (mysqli_sql_exception $exception) {
            $error = $exception->getCode() === 1062
                ? 'That username is already taken.'
                : 'Registration failed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | Anonymous Feedback Portal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
  <h1>Create an account</h1>
  <?php if ($error !== ''): ?><p class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($success !== ''): ?><p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form method="post" action="register.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <label for="username">Username</label>
    <input id="username" name="username" maxlength="50" minlength="3" autocomplete="username" required>
    <label for="password">Password</label>
    <input id="password" type="password" name="password" minlength="12" autocomplete="new-password" required>
    <button class="button" type="submit">Register</button>
  </form>
  <p>Already registered? <a href="login.php">Log in</a></p>
  <p><a href="index.php">Back to Home</a></p>
</main>
</body>
</html>
