<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/require_login.php';

$userId = (int) $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare('SELECT full_name, email, phone_number, national_id FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = $_POST['form'] ?? '';

    if ($form === 'details') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $phone    = trim((string) ($_POST['phone_number'] ?? ''));

        if (mb_strlen($fullName) < 3) {
            $errors[] = 'Please enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^(?:\+254|0)7\d{8}$/', $phone)) {
            $errors[] = 'Please enter a valid Kenyan phone number.';
        }

        if (empty($errors)) {
            if (str_starts_with($phone, '0')) {
                $phone = '+254' . substr($phone, 1);
            }

            $dupCheck = $pdo->prepare(
                'SELECT id FROM users WHERE (email = :email OR phone_number = :phone) AND id != :id'
            );
            $dupCheck->execute(['email' => $email, 'phone' => $phone, 'id' => $userId]);

            if ($dupCheck->fetch()) {
                $errors[] = 'That email or phone number is already used by another account.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE users SET full_name = :name, email = :email, phone_number = :phone WHERE id = :id'
                );
                $update->execute(['name' => $fullName, 'email' => $email, 'phone' => $phone, 'id' => $userId]);
                $_SESSION['user_name'] = $fullName;
                flash('success', 'Profile updated.');
                redirect('profile.php');
            }
        }
    } elseif ($form === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_new_password'] ?? '');

        $hashStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $hashStmt->execute(['id' => $userId]);
        $currentHash = $hashStmt->fetchColumn();

        if (!password_verify($current, (string) $currentHash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (mb_strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute(['hash' => password_hash($new, PASSWORD_BCRYPT), 'id' => $userId]);
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }

    // Re-fetch in case of validation errors so the form re-renders current values.
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
}

$pageTitle = 'Your profile — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<h2>Your profile</h2>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-error"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card">
  <h3>Account details</h3>
  <form method="post" action="profile.php" data-once>
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="details">

    <div class="field">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
    </div>
    <div class="field">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
    </div>
    <div class="field">
      <label for="phone_number">Phone number (M-Pesa)</label>
      <input type="tel" id="phone_number" name="phone_number" value="<?= e($user['phone_number']) ?>" required>
    </div>
    <div class="field">
      <label>National ID</label>
      <input type="text" value="<?= e($user['national_id']) ?>" disabled>
      <div class="hint">National ID cannot be changed. Contact support if this was entered incorrectly.</div>
    </div>

    <button type="submit" class="btn btn-primary">Save changes</button>
  </form>
</div>

<div class="card mt-32">
  <h3>Change password</h3>
  <form method="post" action="profile.php" data-once>
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="password">

    <div class="field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" required>
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input type="password" id="new_password" name="new_password" minlength="8" required>
    </div>
    <div class="field">
      <label for="confirm_new_password">Confirm new password</label>
      <input type="password" id="confirm_new_password" name="confirm_new_password" minlength="8" required>
    </div>

    <button type="submit" class="btn btn-outline">Update password</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
