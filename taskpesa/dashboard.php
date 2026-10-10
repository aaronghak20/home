<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/require_login.php';

$userId = (int) $_SESSION['user_id'];

$userStmt = $pdo->prepare('SELECT full_name, min_withdrawal_threshold FROM users WHERE id = :id');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

$totalEarnedStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM earnings WHERE user_id = :uid');
$totalEarnedStmt->execute(['uid' => $userId]);
$totalEarned = (float) $totalEarnedStmt->fetchColumn();

$totalWithdrawnStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(amount),0) AS total FROM withdrawals WHERE user_id = :uid AND status IN ('pending','completed')"
);
$totalWithdrawnStmt->execute(['uid' => $userId]);
$totalWithdrawn = (float) $totalWithdrawnStmt->fetchColumn();

$balance = $totalEarned - $totalWithdrawn;
$threshold = (float) $user['min_withdrawal_threshold'];
$progressPct = $threshold > 0 ? min(100, ($balance / $threshold) * 100) : 100;

$tasksDoneStmt = $pdo->prepare('SELECT COUNT(*) FROM earnings WHERE user_id = :uid');
$tasksDoneStmt->execute(['uid' => $userId]);
$tasksDone = (int) $tasksDoneStmt->fetchColumn();

$recentStmt = $pdo->prepare(
    'SELECT t.title, e.amount, e.earned_on
     FROM earnings e JOIN tasks t ON t.id = e.task_id
     WHERE e.user_id = :uid
     ORDER BY e.earned_on DESC
     LIMIT 10'
);
$recentStmt->execute(['uid' => $userId]);
$recent = $recentStmt->fetchAll();

$pageTitle = 'Dashboard — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<h2>Hello, <?= e(explode(' ', (string) $user['full_name'])[0]) ?>.</h2>
<p>Here is a snapshot of your account and a running ledger of what you've earned.</p>

<div class="stat-grid">
  <div class="stat-tile">
    <div class="stat-label">Available balance</div>
    <div class="stat-value"><?= e(fmt_money($balance)) ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-label">Total earned</div>
    <div class="stat-value"><?= e(fmt_money($totalEarned)) ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-label">Tasks completed</div>
    <div class="stat-value"><?= $tasksDone ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-label">Withdrawal threshold</div>
    <div class="stat-value"><?= e(fmt_money($threshold)) ?></div>
  </div>
</div>

<div class="passbook card">
  <div class="flex-between">
    <h3>Progress to next withdrawal</h3>
    <?php if ($balance >= $threshold): ?>
      <a href="withdraw.php" class="btn btn-accent">Withdraw now</a>
    <?php endif; ?>
  </div>
  <div class="threshold">
    <div class="threshold-track">
      <div class="threshold-fill" style="width: <?= number_format($progressPct, 1) ?>%;"></div>
    </div>
    <div class="threshold-caption">
      <span><?= e(fmt_money($balance)) ?> saved</span>
      <span><?= e(fmt_money($threshold)) ?> goal</span>
    </div>
  </div>

  <h3 class="mt-32">Recent activity</h3>
  <?php if (empty($recent)): ?>
    <p class="small">No tasks completed yet — <a href="tasks.php">browse available tasks</a> to get started.</p>
  <?php else: ?>
    <?php foreach ($recent as $row): ?>
      <div class="ledger-row">
        <span class="label"><?= e($row['title']) ?></span>
        <span class="fill"></span>
        <span class="value positive">+<?= e(fmt_money($row['amount'])) ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
