/**
 * TaskPesa — client-side interactivity.
 * Note: every check here is UX convenience only. The server
 * re-validates everything (Section 3.5.2), since client-side
 * validation can always be bypassed.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Animate threshold bars in from 0 for a small sense of motion.
  document.querySelectorAll('.threshold-fill').forEach((bar) => {
    const target = bar.style.width;
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      setTimeout(() => { bar.style.width = target; }, 60);
    });
  });

  // Inline password-match feedback on the registration form.
  const pw = document.getElementById('password');
  const pw2 = document.getElementById('confirm_password');
  const matchHint = document.getElementById('password-match-hint');
  function checkMatch() {
    if (!pw || !pw2 || !matchHint) return;
    if (pw2.value.length === 0) { matchHint.textContent = ''; return; }
    matchHint.textContent = pw.value === pw2.value ? 'Passwords match.' : 'Passwords do not match yet.';
    matchHint.style.color = pw.value === pw2.value ? '#0C7A4C' : '#B3452F';
  }
  if (pw2) { pw2.addEventListener('input', checkMatch); pw && pw.addEventListener('input', checkMatch); }

  // Simple double-submit guard on forms with a data-once attribute.
  document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.textContent = 'Please wait…'; }
    });
  });

  // Task "mark complete" confirmation.
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (evt) => {
      const msg = form.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) evt.preventDefault();
    });
  });

  // ---- Withdrawal status polling + SMS-style popup (withdraw.php only) ----
  if (window.TASKPESA_POLL_WITHDRAWALS) {
    initWithdrawalPolling();
  }
});

function initWithdrawalPolling() {
  const toastContainer = document.getElementById('sms-toast-container');
  const table = document.getElementById('withdrawal-history-table');
  if (!toastContainer) return;

  const POLL_INTERVAL_MS = 4000;
  let pollTimer = null;

  function currentPendingIds() {
    if (!table) return [];
    return Array.from(table.querySelectorAll('tr[data-status="pending"]')).map(
      (tr) => tr.getAttribute('data-withdrawal-id')
    );
  }

  function showSmsToast(w) {
    const isFailed = w.status === 'failed';
    const toast = document.createElement('div');
    toast.className = 'sms-toast' + (isFailed ? ' failed' : '');

    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const message = isFailed
      ? `We could not complete your withdrawal of ${w.amount_label} to ${w.phone}. It has been marked failed — please try again from the Withdraw page.`
      : `Confirmed. ${w.amount_label} sent to ${w.phone}` +
        (w.receipt ? ` (Ref: ${w.receipt})` : '') +
        `. Your TaskPesa withdrawal is complete.`;

    toast.innerHTML = `
      <div class="sms-icon">${isFailed ? '!' : 'M'}</div>
      <div class="sms-body">
        <div class="sms-sender"><span>${isFailed ? 'TaskPesa' : 'M-PESA'}</span><span class="sms-time">${time}</span></div>
        <div class="sms-text"></div>
      </div>
      <button class="sms-close" aria-label="Dismiss">&times;</button>
    `;
    toast.querySelector('.sms-text').textContent = message; // textContent, never innerHTML, for user-influenced data
    toastContainer.appendChild(toast);

    const dismiss = () => {
      toast.classList.add('leaving');
      setTimeout(() => toast.remove(), 220);
    };
    toast.querySelector('.sms-close').addEventListener('click', dismiss);
    setTimeout(dismiss, 9000);
  }

  function updateRow(w) {
    if (!table) return;
    const row = table.querySelector(`tr[data-withdrawal-id="${w.id}"]`);
    if (!row) return;
    row.setAttribute('data-status', w.status);
    const badge = row.querySelector('.status-tag');
    if (badge) {
      badge.className = 'status-tag status-' + w.status;
      badge.textContent = w.status_label;
    }
  }

  async function poll() {
    const pendingIds = currentPendingIds();
    if (pendingIds.length === 0) {
      clearInterval(pollTimer);
      return;
    }

    try {
      const res = await fetch('withdrawal_status.php', { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      (data.withdrawals || []).forEach((w) => {
        const wasPending = pendingIds.includes(String(w.id));
        if (wasPending && w.status !== 'pending') {
          updateRow(w);
          showSmsToast(w);
        }
      });
    } catch (err) {
      // Silently skip this cycle — the next poll will retry.
    }
  }

  if (currentPendingIds().length > 0) {
    pollTimer = setInterval(poll, POLL_INTERVAL_MS);
  }
}
