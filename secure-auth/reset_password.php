<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$token      = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error      = '';
$success    = false;
$validToken = false;
$userId     = null;

if ($token !== '') {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();

    if ($user && $user['reset_expires'] !== null && strtotime($user['reset_expires']) > time()) {
        $validToken = true;
        $userId = (int)$user['id'];
    } else {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare(
                'UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL,
                 failed_attempts = 0, locked_until = NULL WHERE id = ?'
            );
            $upd->execute([$passwordHash, $userId]);
            $success    = true;
            $validToken = false; // token is now used up, don't show the form again
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset password · SecureAuth</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-panel">
    <div class="brand">
      <span class="brand-mark">&#9679;</span>
      <span class="brand-name">SecureAuth</span>
    </div>

    <h1>Set a new password</h1>
    <p class="subtext">Choose something you haven't used before.</p>

    <?php if ($success): ?>
      <div class="alert alert-success" role="status">
        Password updated. <a href="login.php">Sign in now &rarr;</a>
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($validToken && !$success): ?>
    <form method="post" action="reset_password.php?token=<?= e($token) ?>" novalidate id="registerForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <label for="password">New password</label>
      <div class="password-field">
        <input type="password" id="password" name="password" autocomplete="new-password"
               required minlength="<?= PASSWORD_MIN_LENGTH ?>" placeholder="At least 8 characters">
        <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">&#128065;</button>
      </div>
      <div class="strength-meter" id="strengthMeter"><span></span></div>
      <p class="field-hint" id="strengthLabel">Password strength</p>

      <label for="confirm_password">Confirm new password</label>
      <div class="password-field">
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
               required placeholder="Re-enter your new password">
        <button type="button" class="toggle-visibility" data-target="confirm_password" aria-label="Show password">&#128065;</button>
      </div>
      <p class="field-hint field-error" id="matchHint"></p>

      <button type="submit" class="btn-primary">Update password</button>
    </form>
    <?php endif; ?>

    <p class="switch-link"><a href="login.php">Back to sign in</a></p>
  </div>
</div>
<script src="assets/script.js"></script>
</body>
</html>
