<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
$old   = ['login' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $login    = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $old      = ['login' => $login];

        if ($login === '' || $password === '') {
            $error = 'Please enter your username/email and password.';
        } else {
            $pdo  = get_pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();

            if (!$user) {
                // Same generic message as a wrong password, so we don't reveal
                // whether the account exists.
                $error = 'Invalid login details.';
            } elseif ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
                $minutesLeft = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
                $error = "This account is temporarily locked. Try again in {$minutesLeft} minute(s).";
            } elseif (password_verify($password, $user['password_hash'])) {
                // ---- Success: reset attempt counter, rotate session ID ----
                $reset = $pdo->prepare(
                    'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?'
                );
                $reset->execute([$user['id']]);

                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                redirect('dashboard.php');
            } else {
                // ---- Failure: increment attempts, lock out if over threshold ----
                $attempts = (int)$user['failed_attempts'] + 1;

                if ($attempts >= MAX_FAILED_ATTEMPTS) {
                    $lockUntil = (new DateTime())->modify('+' . LOCKOUT_MINUTES . ' minutes')
                                                  ->format('Y-m-d H:i:s');
                    $upd = $pdo->prepare(
                        'UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?'
                    );
                    $upd->execute([$attempts, $lockUntil, $user['id']]);
                    $error = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
                } else {
                    $upd = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
                    $upd->execute([$attempts, $user['id']]);
                    $error = 'Invalid login details.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · SecureAuth</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-panel">
    <div class="brand">
      <span class="brand-mark">&#9679;</span>
      <span class="brand-name">SecureAuth</span>
    </div>

    <h1>Welcome back</h1>
    <p class="subtext">Sign in to continue to your dashboard.</p>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="login">Username or email</label>
      <input type="text" id="login" name="login" autocomplete="username"
             value="<?= e($old['login']) ?>" required placeholder="aaronghak20@gmail.com">

      <label for="password">Password</label>
      <div class="password-field">
        <input type="password" id="password" name="password" autocomplete="current-password"
               required placeholder="Your password">
       <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">&#128065;</button>
      </div>
      <p class="field-hint" style="text-align:right;">
        <a href="forgot_password.php" style="color:var(--gold);text-decoration:none;">Forgot password?</a>
      </p>

      <button type="submit" class="btn-primary">login</button>
    </form>

    <p class="switch-link">New here? <a href="register.php">Create an account</a></p>
  </div>
</div>
<script src="assets/script.js"></script>
</body>
</html>
