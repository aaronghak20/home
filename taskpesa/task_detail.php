<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/require_login.php';

$userId = (int) $_SESSION['user_id'];
$taskId = (int) ($_GET['id'] ?? 0);

$taskStmt = $pdo->prepare('SELECT * FROM tasks WHERE id = :id');
$taskStmt->execute(['id' => $taskId]);
$task = $taskStmt->fetch();

if (!$task) {
    flash('error', 'That task could not be found.');
    redirect('tasks.php');
}

$dupCheck = $pdo->prepare('SELECT amount, earned_on FROM earnings WHERE user_id = :uid AND task_id = :tid');
$dupCheck->execute(['uid' => $userId, 'tid' => $taskId]);
$alreadyDone = $dupCheck->fetch();

$errors = [];
$submissionOld = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyDone && $task['status'] === 'active') {
    verify_csrf();
    $submissionOld = trim((string) ($_POST['submission_note'] ?? ''));
    $confirmed = isset($_POST['confirm_honest']);

    if (mb_strlen($submissionOld) < 10) {
        $errors[] = 'Please write at least a short, genuine response (10+ characters) before submitting.';
    }
    if (!$confirmed) {
        $errors[] = 'Please confirm that you completed this task honestly.';
    }

    if (empty($errors)) {
        // Re-check for a duplicate right before inserting to close the race
        // between two submissions for the same task landing at once.
        $recheck = $pdo->prepare('SELECT id FROM earnings WHERE user_id = :uid AND task_id = :tid');
        $recheck->execute(['uid' => $userId, 'tid' => $taskId]);

        if ($recheck->fetch()) {
            flash('error', 'You have already completed this task.');
            redirect('tasks.php');
        }

        $insert = $pdo->prepare(
            'INSERT INTO earnings (user_id, task_id, amount, submission_note) VALUES (:uid, :tid, :amount, :note)'
        );
        $insert->execute([
            'uid'    => $userId,
            'tid'    => $taskId,
            'amount' => $task['reward_amount'],
            'note'   => $submissionOld,
        ]);

        flash('success', 'Task completed! ' . fmt_money($task['reward_amount']) . ' added to your balance.');
        redirect('dashboard.php');
    }
}

$pageTitle = e($task['title']) . ' — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<a href="tasks.php" class="small">&larr; Back to all tasks</a>

<div class="flex-between mt-32">
  <h2><?= e($task['title']) ?></h2>
  <span class="task-reward">+<?= e(fmt_money($task['reward_amount'])) ?></span>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-error"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card">
  <h3>Task instructions</h3>
  <p><?= nl2br(e($task['description'])) ?></p>
</div>

<div class="passbook card mt-32">
  <?php if ($task['status'] !== 'active'): ?>
    <p>This task is currently closed and no longer accepting submissions.</p>
    <a href="tasks.php" class="btn btn-outline">Find another task</a>

  <?php elseif ($alreadyDone): ?>
    <span class="badge-done">&#10003; Completed</span>
    <h3 class="mt-32">You already completed this task</h3>
    <p class="small">Submitted on <?= e(date('d M Y, H:i', strtotime((string) $alreadyDone['earned_on']))) ?> — you were paid <?= e(fmt_money($alreadyDone['amount'])) ?>.</p>
    <a href="tasks.php" class="btn btn-primary">Find another task</a>

  <?php else: ?>
    <h3>Submit your completion</h3>
    <p class="small">Complete the task above, then fill in the response below to claim your reward.</p>

    <form method="post" action="task_detail.php?id=<?= (int) $task['id'] ?>" data-once>
      <?= csrf_field() ?>

      <div class="field">
        <label for="submission_note"><?= e($task['completion_prompt']) ?></label>
        <textarea id="submission_note" name="submission_note" minlength="10" required placeholder="Type your answer here…"><?= e($submissionOld) ?></textarea>
        <div class="hint">This is recorded against your account so submissions can be reviewed.</div>
      </div>

      <div class="field" style="display:flex; align-items:flex-start; gap:10px;">
        <input type="checkbox" id="confirm_honest" name="confirm_honest" style="width:auto; margin-top:4px;" required>
        <label for="confirm_honest" style="margin:0; font-weight:500;">I confirm I genuinely completed this task.</label>
      </div>

      <button type="submit" class="btn btn-accent btn-block">Submit &amp; claim <?= e(fmt_money($task['reward_amount'])) ?></button>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
