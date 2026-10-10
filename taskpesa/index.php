<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('tasks.php');
}

$pageTitle = 'TaskPesa — Complete small tasks, get paid to M-Pesa';
require __DIR__ . '/includes/header.php';
?>

<section class="hero" style="margin: -48px -24px 48px; padding-left: 24px; padding-right: 24px;">
  <div class="container" style="padding: 0;">
    <p class="hero-eyebrow">Micro-tasks &middot; Surveys &middot; Rewards</p>
    <h1>Turn spare minutes into M-Pesa money.</h1>
    <p class="lead">TaskPesa connects you to short surveys, app tests and simple online tasks. Every completed task adds to your balance — withdraw straight to M-Pesa once you hit the threshold.</p>
    <div class="hero-cta">
      <a href="register.php" class="btn btn-accent">Create a free account</a>
      <a href="#how-it-works" class="btn btn-outline" style="border-color: rgba(255,255,255,0.3); color:#fff;">See how it works</a>
    </div>

    <div class="hero-ticker">
      <div class="ledger-row">
        <span class="label">Survey: shopping habits</span>
        <span class="fill"></span>
        <span class="value">+KSh 25.00</span>
      </div>
      <div class="ledger-row">
        <span class="label">App test: bug report</span>
        <span class="fill"></span>
        <span class="value">+KSh 60.00</span>
      </div>
      <div class="ledger-row">
        <span class="label">Withdrawal to M-Pesa</span>
        <span class="fill"></span>
        <span class="value">&minus;KSh 500.00</span>
      </div>
    </div>
  </div>
</section>

<section id="how-it-works">
  <h2>How it works</h2>
  <p>A simple loop: sign up, complete tasks, watch your balance grow, cash out.</p>
  <div class="steps">
    <div class="step">
      <div class="step-index">Step 1</div>
      <h3>Register</h3>
      <p>Create an account with your name, email, phone number and national ID.</p>
    </div>
    <div class="step">
      <div class="step-index">Step 2</div>
      <h3>Complete tasks</h3>
      <p>Browse available surveys and micro-tasks, and mark each one done as you finish it.</p>
    </div>
    <div class="step">
      <div class="step-index">Step 3</div>
      <h3>Track earnings</h3>
      <p>Your dashboard keeps a running ledger of every task and how close you are to the withdrawal threshold.</p>
    </div>
    <div class="step">
      <div class="step-index">Step 4</div>
      <h3>Withdraw to M-Pesa</h3>
      <p>Once you cross the minimum threshold, request a payout — processed via the M-Pesa Daraja API.</p>
    </div>
  </div>
</section>

<section class="mt-32">
  <div class="stat-grid">
    <div class="stat-tile">
      <div class="stat-label">Passwords</div>
      <div class="stat-value" style="font-size:1.1rem;">Bcrypt-hashed</div>
    </div>
    <div class="stat-tile">
      <div class="stat-label">Transfers</div>
      <div class="stat-value" style="font-size:1.1rem;">HTTPS-only</div>
    </div>
    <div class="stat-tile">
      <div class="stat-label">Sessions</div>
      <div class="stat-value" style="font-size:1.1rem;">Auto-expiring</div>
    </div>
    <div class="stat-tile">
      <div class="stat-label">Payouts</div>
      <div class="stat-value" style="font-size:1.1rem;">M-Pesa Daraja</div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
