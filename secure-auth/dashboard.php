<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT username, email, created_at FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // The account may have been deleted elsewhere; clear the stale session.
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · Secure-login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="dash-shell">
  <header class="dash-header">
    <div class="brand">
      <span class="brand-mark">&#9679;</span>
      <span class="brand-name">Secure-login</span>
    </div>
    <form method="post" action="logout.php">
      <button type="submit" class="btn-ghost">Log out</button>
    </form>
  </header>

  <main class="dash-main">
    <div class="dash-card">
      <p class="eyebrow">Account overview</p>
      <h1>Hey, <?= e($user['username']) ?> &#128075;</h1>
      <p class="subtext">congrats🙌🎉, you have successfully login into your dashboard.</p>

      <dl class="detail-list">
        <div><dt>Username</dt><dd><?= e($user['username']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
        <div><dt>date join</dt><dd><?= e(date('F j, Y', strtotime($user['created_at']))) ?></dd></div>
      </dl>
    </div>
  </main>
</div>
</body>
</html>
