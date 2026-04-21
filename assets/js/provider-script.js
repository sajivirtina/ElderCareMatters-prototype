/* =============================================
   ECM Provider Script — provider-script.js
   Used by: all provider dashboard pages
   ============================================= */

/* ── TOAST NOTIFICATION ─────────────────────────────── */
(function () {
  const container = document.createElement('div');
  container.id = 'toast-container';
  document.body.appendChild(container);
})();

function showToast(message, type) {
  type = type || 'success';
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = 'toast toast--' + type;
  const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
  toast.innerHTML = '<span>' + icon + '</span> ' + message;
  container.appendChild(toast);
  setTimeout(function () {
    toast.style.animation = 'fadeOut 0.3s ease forwards';
    setTimeout(function () { toast.remove(); }, 300);
  }, 3000);
}

/* ── SCROLL HANDLER (topbar shadow) ─────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  var topbar = document.querySelector('.prov-topbar');
  if (topbar) {
    window.addEventListener('scroll', function () {
      topbar.style.boxShadow = window.scrollY > 10
        ? '0 2px 14px rgba(0,0,0,0.25)'
        : 'none';
    });
  }
});

/* ── COUNTDOWN TIMER ─────────────────────────────────── */
/**
 * startCountdown(selector, totalSeconds)
 * Looks for .h, .m, .s spans inside the matched element and ticks down.
 * @param {string} selector  CSS selector for the countdown container
 * @param {number} totalSeconds  Starting value in seconds
 */
function startCountdown(selector, totalSeconds) {
  var el = document.querySelector(selector);
  if (!el) return;
  var remaining = totalSeconds;

  function pad(n) { return String(n).padStart(2, '0'); }

  function tick() {
    var h = Math.floor(remaining / 3600);
    var m = Math.floor((remaining % 3600) / 60);
    var s = remaining % 60;

    var hEl = el.querySelector('.h');
    var mEl = el.querySelector('.m');
    var sEl = el.querySelector('.s');

    if (hEl) hEl.textContent = pad(h);
    if (mEl) mEl.textContent = pad(m);
    if (sEl) sEl.textContent = pad(s);

    if (remaining > 0) {
      remaining--;
      setTimeout(tick, 1000);
    } else {
      el.textContent = 'Window closed';
    }
  }

  tick();
}
