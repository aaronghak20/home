<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/require_login.php';

$userId = (int) $_SESSION['user_id'];

// Completion now happens on task_detail.php, which requires the user to
// actually submit something before the reward is credited.

// ---- Load active tasks, and which ones this user has already done ----
$tasks = $pdo->query(
    "SELECT id, title, description, reward_amount FROM tasks WHERE status = 'active' ORDER BY created_on DESC"
)->fetchAll();

$doneStmt = $pdo->prepare('SELECT task_id FROM earnings WHERE user_id = :uid');
$doneStmt->execute(['uid' => $userId]);
$doneTaskIds = array_column($doneStmt->fetchAll(), 'task_id');

$newCount = count(array_diff(array_column($tasks, 'id'), $doneTaskIds));

$pageTitle = 'Available tasks — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between">
  <h2>Available tasks</h2>
  <span class="small"><?= (int) $newCount ?> new for you</span>
</div>
<p>Complete a task below to add its reward to your earnings balance instantly.</p>

<?php if (empty($tasks)): ?>
  <div class="card text-center">
    <p>There are no active tasks right now. Check back soon.</p>
  </div>
<?php else: ?>
  <div class="task-grid">
    <?php foreach ($tasks as $task): ?>
      <?php $isDone = in_array((int) $task['id'], array_map('intval', $doneTaskIds), true); ?>
      <div class="task-card">
        <span class="task-reward">+<?= e(fmt_money($task['reward_amount'])) ?></span>
        <h3><a href="task_detail.php?id=<?= (int) $task['id'] ?>" style="color:inherit; text-decoration:none;"><?= e($task['title']) ?></a></h3>
        <p class="task-desc"><?= e($task['description']) ?></p>
        <?php if ($isDone): ?>
          <span class="badge-done">&#10003; Completed</span>
        <?php else: ?>
          <a href="task_detail.php?id=<?= (int) $task['id'] ?>" class="btn btn-primary btn-block">Start task</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
