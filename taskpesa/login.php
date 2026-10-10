<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('tasks.php');
}

$errors = [];
$emailOld = '';

// Very small login rate-limit to slow down brute-force attempts.
$_SESSION['login_attempts'] ??= 0;
$_SESSION['login_attempts_window'] ??= time();
if (time() - $_SESSION['login_attempts_window'] > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempts_window'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $emailOld = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($_SESSION['login_attempts'] >= 8) {
        $errors[] = 'Too many login attempts. Please wait a few minutes and try again.';
    } elseif ($emailOld === '' || $password === '') {
        $errors[] = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM users WHERE email = :email');
        $stmt->execute(['email' => $emailOld]);
        $user = $stmt->fetch();

        // Compare against the stored bcrypt hash — never compare plain text (Section 3.5.1).
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'] = 0;

            // Regenerate the session ID on privilege change to prevent session fixation.
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect('tasks.php');
        } else {
            $_SESSION['login_attempts']++;
            $errors[] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Log in — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<div class="container-narrow">
  <div class="card">
    <h2>Log in</h2>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="login.php" data-once>
      <?= csrf_field() ?>

      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($emailOld) ?>" required autofocus>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>

    <p class="small mt-32">Don't have an account? <a href="register.php">Create one</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
