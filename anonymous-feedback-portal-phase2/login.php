<?php
declare(strict_types=1);

require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $attempts = (int) ($_SESSION['login_failures'] ?? 0);
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        http_response_code(429);
        $error = 'Too many attempts. Try again later.';
    } elseif ($username === '' || !is_string($password) || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $connection->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);
            rotate_csrf();
            header('Location: feedback.php', true, 303);
            exit;
        }

        $_SESSION['login_failures'] = $attempts + 1;
        if ($_SESSION['login_failures'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 900;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Anonymous Feedback Portal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
  <h1>Login</h1>
  <?php if ($error !== ''): ?><p class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <label for="username">Username</label>
    <input id="username" name="username" maxlength="50" autocomplete="username" required>
    <label for="password">Password</label>
    <input id="password" type="password" name="password" autocomplete="current-password" required>
    <button type="submit" class="button">Login</button>
  </form>
  <p>Don't have an account? <a href="register.php">Register</a></p>
  <p><a href="index.php">Back to Home</a></p>
</main>
</body>
</html>
