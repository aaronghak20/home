<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$phoneStmt = $pdo->prepare('SELECT phone_number FROM users WHERE id = :id');
$phoneStmt->execute(['id' => $userId]);
$phone = $phoneStmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT id, amount, status, mpesa_receipt, requested_on, completed_on
     FROM withdrawals
     WHERE user_id = :uid
     ORDER BY requested_on DESC
     LIMIT 15'
);
$stmt->execute(['uid' => $userId]);
$rows = $stmt->fetchAll();

$out = array_map(static function (array $row) use ($phone): array {
    return [
        'id'            => (int) $row['id'],
        'amount'        => (float) $row['amount'],
        'amount_label'  => fmt_money($row['amount']),
        'status'        => $row['status'],
        'status_label'  => ucfirst($row['status']),
        'receipt'       => $row['mpesa_receipt'],
        'phone'         => $phone,
        'completed_on'  => $row['completed_on'],
    ];
}, $rows);

echo json_encode(['withdrawals' => $out]);
