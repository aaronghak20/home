<?php
/**
 * Site header. Expects $pageTitle to optionally be set before include.
 */
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'TaskPesa';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <nav class="nav">
    <a href="index.php" class="brand">TaskPesa <span class="brand-mark">KE</span></a>
    <ul class="nav-links">
      <?php if (is_logged_in()): ?>
        <li><a href="tasks.php">Tasks</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="withdraw.php">Withdraw</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Log out</a></li>
      <?php else: ?>
        <li><a href="index.php#how-it-works">How it works</a></li>
        <li><a href="login.php">Log in</a></li>
        <li><a href="register.php" class="pill">Get started</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
<main>
  <div class="container">
    <?php foreach (get_flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
