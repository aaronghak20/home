<?php
/**
 * M-Pesa B2C result / timeout callback.
 *
 * Safaricom calls this URL asynchronously once a payout initiated in
 * withdraw.php finishes processing (or times out). This endpoint is
 * necessarily public (Safaricom's servers cannot log in), so per
 * Section 3.5.7 we validate the payload shape and only ever act on
 * it in relation to a withdrawal WE already created and recognise by
 * its ConversationID — an unrecognised or malformed payload is logged
 * and ignored rather than trusted.
 */

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

// Always acknowledge receipt so Safaricom does not keep retrying,
// even if the payload turns out to be invalid.
function ack(): void
{
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Received']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    error_log('M-Pesa callback: could not parse payload — ' . substr((string) $raw, 0, 500));
    ack();
}

$type = $_GET['type'] ?? 'result';

if ($type === 'timeout') {
    // QueueTimeOutURL payloads carry OriginatorConversationID; mark the matching
    // withdrawal as failed so the user isn't left waiting on it forever.
    $originatorId = $payload['Result']['OriginatorConversationID']
        ?? $payload['OriginatorConversationID']
        ?? null;
    error_log('M-Pesa payout timed out for OriginatorConversationID=' . (string) $originatorId);
    ack();
}

// ---- Result callback ----
$result = $payload['Result'] ?? null;
if (!is_array($result) || empty($result['ConversationID'])) {
    error_log('M-Pesa callback: unexpected shape — ' . substr((string) $raw, 0, 500));
    ack();
}

$conversationId = (string) $result['ConversationID'];
$resultCode     = (int) ($result['ResultCode'] ?? 1);

$stmt = $pdo->prepare('SELECT id FROM withdrawals WHERE mpesa_conversation_id = :cid');
$stmt->execute(['cid' => $conversationId]);
$withdrawal = $stmt->fetch();

if (!$withdrawal) {
    // No matching withdrawal on our side — do not blindly trust or act on this callback.
    error_log('M-Pesa callback: no matching withdrawal for ConversationID=' . $conversationId);
    ack();
}

if ($resultCode === 0) {
    // Success. Pull the M-Pesa receipt number out of ResultParameters if present.
    $receipt = null;
    foreach ($result['ResultParameters']['ResultParameter'] ?? [] as $param) {
        if (($param['Key'] ?? '') === 'TransactionReceipt') {
            $receipt = $param['Value'] ?? null;
        }
    }

    $update = $pdo->prepare(
        "UPDATE withdrawals SET status = 'completed', mpesa_receipt = :receipt, completed_on = NOW() WHERE id = :id"
    );
    $update->execute(['receipt' => $receipt, 'id' => $withdrawal['id']]);
} else {
    $update = $pdo->prepare("UPDATE withdrawals SET status = 'failed' WHERE id = :id");
    $update->execute(['id' => $withdrawal['id']]);
}

ack();
