<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/require_login.php';
require __DIR__ . '/mpesa/MpesaClient.php';

$userId = (int) $_SESSION['user_id'];

$userStmt = $pdo->prepare('SELECT phone_number, min_withdrawal_threshold FROM users WHERE id = :id');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

function get_balance(PDO $pdo, int $userId): float
{
    $earned = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM earnings WHERE user_id = :uid');
    $earned->execute(['uid' => $userId]);

    $withdrawn = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = :uid AND status IN ('pending','completed')"
    );
    $withdrawn->execute(['uid' => $userId]);

    return (float) $earned->fetchColumn() - (float) $withdrawn->fetchColumn();
}

$balance   = get_balance($pdo, $userId);
$threshold = (float) $user['min_withdrawal_threshold'];
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = (float) ($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        $errors[] = 'Enter a valid withdrawal amount.';
    } elseif ($balance < $threshold) {
        $errors[] = 'You have not yet reached the minimum withdrawal threshold of ' . fmt_money($threshold) . '.';
    } elseif ($amount > $balance) {
        $errors[] = 'You cannot withdraw more than your available balance of ' . fmt_money($balance) . '.';
    } else {
        // 1. Record the withdrawal as pending first, so we never lose track of the attempt.
        $insert = $pdo->prepare(
            'INSERT INTO withdrawals (user_id, method, amount, status) VALUES (:uid, "M-Pesa", :amount, "pending")'
        );
        $insert->execute(['uid' => $userId, 'amount' => $amount]);
        $withdrawalId = (int) $pdo->lastInsertId();

        // 2. Attempt to trigger the actual M-Pesa payout.
        try {
            $config = require __DIR__ . '/config/mpesa_config.php';

            if (empty($config['consumer_key']) || empty($config['security_cred'])) {
                // Credentials not configured in this environment (e.g. local dev) —
                // leave the withdrawal pending rather than pretending it succeeded.
                flash('success', 'Withdrawal request of ' . fmt_money($amount) . ' recorded and is pending processing. (M-Pesa credentials are not configured in this environment.)');
            } else {
                $client = new MpesaClient($config);
                $phoneForApi = ltrim($user['phone_number'], '+'); // Daraja expects 2547XXXXXXXX
                $result = $client->b2cPayment($phoneForApi, $amount, 'TPW' . $withdrawalId . '-' . time());

                $convId = $result['ConversationID'] ?? null;
                $update = $pdo->prepare('UPDATE withdrawals SET mpesa_conversation_id = :cid WHERE id = :id');
                $update->execute(['cid' => $convId, 'id' => $withdrawalId]);

                flash('success', 'Withdrawal of ' . fmt_money($amount) . ' has been submitted to M-Pesa and is processing. You will be notified once it completes.');
            }
        } catch (Throwable $ex) {
            error_log('M-Pesa withdrawal error: ' . $ex->getMessage());
            $fail = $pdo->prepare("UPDATE withdrawals SET status = 'failed' WHERE id = :id");
            $fail->execute(['id' => $withdrawalId]);
            flash('error', 'We could not reach M-Pesa to process this withdrawal. It has been marked failed — please try again shortly.');
        }

        redirect('withdraw.php');
    }
}

$historyStmt = $pdo->prepare(
    'SELECT id, amount, status, requested_on, completed_on FROM withdrawals WHERE user_id = :uid ORDER BY requested_on DESC LIMIT 15'
);
$historyStmt->execute(['uid' => $userId]);
$history = $historyStmt->fetchAll();

$pageTitle = 'Withdraw — TaskPesa';
require __DIR__ . '/includes/header.php';
?>

<h2>Withdraw to M-Pesa</h2>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-error"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="stat-grid">
  <div class="stat-tile">
    <div class="stat-label">Available balance</div>
    <div class="stat-value"><?= e(fmt_money($balance)) ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-label">Minimum threshold</div>
    <div class="stat-value"><?= e(fmt_money($threshold)) ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-label">Payout number</div>
    <div class="stat-value" style="font-size:1.1rem;"><?= e($user['phone_number']) ?></div>
  </div>
</div>

<div class="card">
  <?php if ($balance < $threshold): ?>
    <p>You need at least <?= e(fmt_money($threshold)) ?> in your balance before you can withdraw. Keep completing tasks — you're at <?= e(fmt_money($balance)) ?> so far.</p>
    <a href="tasks.php" class="btn btn-primary">Find more tasks</a>
  <?php else: ?>
    <form method="post" action="withdraw.php" data-once data-confirm="Send this withdrawal request to M-Pesa?">
      <?= csrf_field() ?>
      <div class="field">
        <label for="amount">Amount to withdraw (KSh)</label>
        <input type="number" id="amount" name="amount" min="1" max="<?= (float) $balance ?>" step="1" value="<?= (float) $balance ?>" required>
        <div class="hint">Sent to <?= e($user['phone_number']) ?> via M-Pesa.</div>
      </div>
      <button type="submit" class="btn btn-accent btn-block">Request withdrawal</button>
    </form>
  <?php endif; ?>
</div>

<div class="card mt-32">
  <h3>Withdrawal history</h3>
  <?php if (empty($history)): ?>
    <p class="small">No withdrawals yet.</p>
  <?php else: ?>
    <table id="withdrawal-history-table">
      <thead>
        <tr><th>Date</th><th>Amount</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($history as $row): ?>
          <tr data-withdrawal-id="<?= (int) $row['id'] ?>" data-status="<?= e($row['status']) ?>">
            <td><?= e(date('d M Y, H:i', strtotime($row['requested_on']))) ?></td>
            <td class="amount"><?= e(fmt_money($row['amount'])) ?></td>
            <td><span class="status-tag status-<?= e($row['status']) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div id="sms-toast-container" aria-live="polite"></div>
<script>
  window.TASKPESA_POLL_WITHDRAWALS = true;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
