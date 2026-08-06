<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in? Send them to the dashboard.
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors  = [];
$success = false;
$old     = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        $old = ['username' => $username, 'email' => $email];

        // ---- Validation ------------------------------------------------
        if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, underscore only).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // ---- Insert ------------------------------------------------------
        if (empty($errors)) {
            try {
                $pdo = get_pdo();
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
                );
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$username, $email, $passwordHash]);

                $success = true;
                $old = ['username' => '', 'email' => ''];
            } catch (PDOException $ex) {
                // Duplicate username/email triggers MySQL error code 23000.
                if ($ex->getCode() === '23000') {
                    $errors[] = 'That username or email is already registered.';
                } else {
                    error_log('Registration error: ' . $ex->getMessage());
                    $errors[] = 'Something went wrong. Please try again later.';
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
<title>Create account · Secure-login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-panel">
    <div class="brand">
      <span class="brand-mark">&#9679;</span>
      <span class="brand-name">Secure-login</span>
    </div>

    <h1>Create your account</h1>
    <p class="subtext">Takes less than a minute. No spam, ever.</p>

    <?php if ($success): ?>
      <div class="alert alert-success" role="status">
        Account created. <a href="login.php">Sign in now &rarr;</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" role="alert">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" action="register.php" novalidate id="registerForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username"
             value="<?= e($old['username']) ?>" required minlength="3" maxlength="50"
             pattern="[A-Za-z0-9_]+" placeholder="Ghak chol">
      <p class="field-hint">Letters, numbers, and underscores only.</p>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email"
             value="<?= e($old['email']) ?>" required placeholder="aaronghak20@gmail.com">

      <label for="password">Password</label>
      <div class="password-field">
        <input type="password" id="password" name="password" autocomplete="new-password"
               required minlength="<?= PASSWORD_MIN_LENGTH ?>" placeholder="At least 8 characters">
        <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">&#128065;</button>
      </div>
      <div class="strength-meter" id="strengthMeter"><span></span></div>
      <p class="field-hint" id="strengthLabel">Password strength</p>

      <label for="confirm_password">Confirm password</label>
      <div class="password-field">
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
               required placeholder="Re-enter your password">
        <button type="button" class="toggle-visibility" data-target="confirm_password" aria-label="Show password">&#128065;</button>
      </div>
      <p class="field-hint field-error" id="matchHint"></p>

      <button type="submit" class="btn-primary">Create account</button>
    </form>
    <?php endif; ?>

    <p class="switch-link">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</div>
<script src="assets/script.js"></script>
</body>
</html>
