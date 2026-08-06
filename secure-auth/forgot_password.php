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

$error    = '';
$resetUrl = null;
$old      = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $old   = ['email' => $email];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $pdo  = get_pdo();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show the same confirmation message whether or not the
            // email exists, so this form can't be used to enumerate accounts.
            if ($user) {
                $rawToken  = generate_reset_token();
                $tokenHash = hash('sha256', $rawToken);
                $expires   = (new DateTime())->modify('+' . RESET_TOKEN_MINUTES . ' minutes')
                                              ->format('Y-m-d H:i:s');

                $upd = $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
                $upd->execute([$tokenHash, $expires, $user['id']]);

                // In production, email $resetUrl to the user instead of
                // displaying it — shown on-screen here only because this
                // demo has no mail server configured.
                $resetUrl = 'reset_password.php?token=' . urlencode($rawToken);
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
<title>Forgot password · SecureAuth</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-panel">
    <div class="brand">
      <span class="brand-mark">&#9679;</span>
      <span class="brand-name">SecureAuth</span>
    </div>

    <h1>Reset your password</h1>
    <p class="subtext">Enter the email on your account and we'll send you a reset link.</p>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
      <div class="alert alert-success" role="status">
        If that email is registered, a reset link has been sent.
      </div>
      <?php if ($resetUrl): ?>
        <p class="field-hint">Demo mode &mdash; no mail server is configured, so here's the link that would normally be emailed:</p>
        <div class="reset-link-box"><a href="<?= e($resetUrl) ?>" style="color:inherit;"><?= e($resetUrl) ?></a></div>
      <?php endif; ?>
    <?php else: ?>
    <form method="post" action="forgot_password.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email"
             value="<?= e($old['email']) ?>" required placeholder="aaronghak20@gmail.com">

      <button type="submit" class="btn-primary">Send reset link</button>
    </form>
    <?php endif; ?>

    <p class="switch-link">Remembered it? <a href="login.php">Back to sign in</a></p>
  </div>
</div>
</body>
</html>
