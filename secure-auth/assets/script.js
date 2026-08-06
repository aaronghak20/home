/**
 * script.js
 * Progressive-enhancement only: every check here is re-validated
 * server-side in register.php / login.php, so nothing here is
 * load-bearing for security — it just improves the experience.
 */

document.addEventListener('DOMContentLoaded', () => {
  // ---- Show/hide password ----------------------------------------------
  document.querySelectorAll('.toggle-visibility').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.textContent = showing ? '\u{1F441}' : '\u{1F576}';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });

  // ---- Password strength meter (registration only) ----------------------
  const passwordInput = document.getElementById('password');
  const meter = document.getElementById('strengthMeter');
  const strengthLabel = document.getElementById('strengthLabel');

  if (passwordInput && meter && document.getElementById('registerForm')) {
    const labels = ['Too short', 'Weak', 'Okay', 'Good', 'Strong'];

    const scorePassword = (value) => {
      let score = 0;
      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
      if (/\d/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value) && value.length >= 10) score++;
      return score; // 0-4
    };

    passwordInput.addEventListener('input', () => {
      const score = passwordInput.value.length === 0 ? 0 : scorePassword(passwordInput.value);
      meter.setAttribute('data-strength', String(score));
      strengthLabel.textContent = passwordInput.value.length === 0
        ? 'Password strength'
        : labels[score];
    });
  }

  // ---- Live "passwords match" hint (registration only) -------------------
  const confirmInput = document.getElementById('confirm_password');
  const matchHint = document.getElementById('matchHint');

  if (passwordInput && confirmInput && matchHint) {
    const checkMatch = () => {
      if (confirmInput.value.length === 0) {
        matchHint.textContent = '';
        return;
      }
      matchHint.textContent = confirmInput.value === passwordInput.value
        ? ''
        : 'Passwords do not match yet.';
    };
    passwordInput.addEventListener('input', checkMatch);
    confirmInput.addEventListener('input', checkMatch);
  }
});
