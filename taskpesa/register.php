<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('tasks.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone_number' => '', 'national_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['full_name']    = trim((string)($_POST['full_name'] ?? ''));
    $old['email']        = trim((string)($_POST['email'] ?? ''));
    $old['phone_number'] = trim((string)($_POST['phone_number'] ?? ''));
    $old['national_id']  = trim((string)($_POST['national_id'] ?? ''));
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // ---- Server-side validation (authoritative; never trust the client) ----
    if ($old['full_name'] === '' || mb_strlen($old['full_name']) < 3) {
        $errors[] = 'Please enter your full name (at least 3 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^(?:\+254|0)7\d{8}$/', $old['phone_number'])) {
        $errors[] = 'Please enter a valid Kenyan phone number, e.g. 07XXXXXXXX or +2547XXXXXXXX.';
    }
    if (!preg_match('/^[A-Za-z0-9]{6,20}$/', $old['national_id'])) {
        $errors[] = 'Please enter a valid national ID number.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Normalise phone to +254 format for consistent storage.
        $phone = $old['phone_number'];
        if (str_starts_with($phone, '0')) {
            $phone = '+254' . substr($phone, 1);
        }

        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email OR phone_number = :phone OR national_id = :nid');
            $check->execute(['email' => $old['email'], 'phone' => $phone, 'nid' => $old['national_id']]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email, phone number, or national ID already exists.';
            } else {
                // Section 3.5.1: passwords hashed with bcrypt (PHP's default), never stored in plain text.
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, phone_number, national_id, password_hash)
                     VALUES (:full_name, :email, :phone, :nid, :hash)'
                );
                $stmt->execute([
                    'full_name' => $old['full_name'],
                    'email'     => $old['email'],
                    'phone'     => $phone,
                    'nid'       => $old['national_id'],
                    'hash'      => $hash,
                ]);

                flash('success', 'Account created. You can now log in.');
                redirect('login.php');
            }
        } catch (PDOException $ex) {
            error_log('Registration error: ' . $ex->getMessage());
            $errors[] = 'Something went wrong while creating your account. Please try again.';
        }
    }
}

$pageTitle = 'Create your account — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<div class="container-narrow">
  <div class="card">
    <h2>Create your account</h2>
    <p class="small">Takes about a minute. You'll need a valid email, phone number and national ID.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="register.php" data-once novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
      </div>

      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
      </div>

      <div class="field">
        <label for="phone_number">Phone number</label>
        <input type="tel" id="phone_number" name="phone_number" placeholder="07XXXXXXXX" value="<?= e($old['phone_number']) ?>" required>
        <div class="hint">This is the M-Pesa number your earnings will be sent to.</div>
      </div>

      <div class="field">
        <label for="national_id">National ID number</label>
        <input type="text" id="national_id" name="national_id" value="<?= e($old['national_id']) ?>" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" required>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
        <div class="hint" id="password-match-hint"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="small mt-32">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
