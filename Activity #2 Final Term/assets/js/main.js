// Confirm destructive actions: <form data-confirm="Are you sure?">
document.addEventListener('submit', function (e) {
  const msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
  const btn = e.target.querySelector('button[type=submit]');
  if (btn && !btn.dataset.noLoading) {            // prevents double submission
    btn.disabled = true;
    btn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2"></span>');
  }
});
// Client-side password match hint
document.querySelectorAll('[data-match]').forEach(function (el) {
  const other = document.querySelector(el.dataset.match);
  el.addEventListener('input', function () {
    el.setCustomValidity(other && other.value !== el.value ? 'Passwords do not match' : '');
  });
});
