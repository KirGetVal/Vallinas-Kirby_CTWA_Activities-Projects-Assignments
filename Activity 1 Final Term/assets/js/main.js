// Shared behaviour: validation feedback, confirmations, double-submit guard, small UX helpers.
document.addEventListener('submit', function (e) {
  var form = e.target;
  // Client-side validation feedback (server still validates everything).
  if (form.hasAttribute('novalidate') && !form.checkValidity()) {
    e.preventDefault();
    form.classList.add('was-validated');
    var bad = form.querySelector(':invalid');
    if (bad) { bad.focus(); if (bad.reportValidity) { bad.reportValidity(); } }
    return;
  }
  var msg = form.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
  var btn = form.querySelector('button[type=submit], button:not([type])');
  if (btn && !btn.dataset.noLoading && !btn.disabled) {      // prevents double submission
    // Disable on the next tick so the button's own name/value is still submitted.
    setTimeout(function () {
      btn.disabled = true;
      btn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
    }, 0);
  }
});

// Back/forward cache: re-enable buttons that were disabled by a previous submit.
window.addEventListener('pageshow', function (ev) {
  if (!ev.persisted) { return; }
  document.querySelectorAll('button[disabled] .spinner-border').forEach(function (s) {
    var b = s.closest('button'); s.remove(); if (b) { b.disabled = false; }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  // Password match hint
  document.querySelectorAll('[data-match]').forEach(function (el) {
    var other = document.querySelector(el.dataset.match);
    el.addEventListener('input', function () {
      el.setCustomValidity(other && other.value !== el.value ? 'Passwords do not match' : '');
    });
  });

  // Show/hide toggle for password fields
  document.querySelectorAll('input[type=password]').forEach(function (inp) {
    if (inp.closest('.input-group')) { return; }
    var group = document.createElement('div');
    group.className = 'input-group';
    inp.parentNode.insertBefore(group, inp);
    group.appendChild(inp);
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'btn btn-outline-secondary'; b.setAttribute('aria-label', 'Show password');
    b.innerHTML = '<i class="bi bi-eye"></i>';
    b.addEventListener('click', function () {
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      b.firstChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
    group.appendChild(b);
    // keep server-side error text (if any) visible under the group
    var fb = group.nextElementSibling;
    if (fb && fb.classList.contains('invalid-feedback') && inp.classList.contains('is-invalid')) { fb.style.display = 'block'; }
  });

  // Character counters on larger text areas
  document.querySelectorAll('textarea[maxlength]').forEach(function (t) {
    if (parseInt(t.getAttribute('rows') || '2', 10) < 3) { return; }
    var max = parseInt(t.getAttribute('maxlength'), 10);
    var c = document.createElement('div');
    c.className = 'char-count'; t.insertAdjacentElement('afterend', c);
    var upd = function () { c.textContent = t.value.length + ' / ' + max; c.classList.toggle('near', t.value.length > max * 0.9); };
    t.addEventListener('input', upd); upd();
  });

  // Success messages fade out on their own; errors stay until dismissed.
  document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (a) {
    setTimeout(function () {
      if (window.bootstrap && document.body.contains(a)) { bootstrap.Alert.getOrCreateInstance(a).close(); }
    }, 6000);
  });
});

// Light / dark theme toggle (choice is remembered in localStorage; the initial value is set in header.php).
(function () {
  var root = document.documentElement;
  function current() { return root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light'; }
  function apply(t, save) {
    root.setAttribute('data-bs-theme', t);
    var m = document.querySelector('meta[name="theme-color"]');
    if (m) { m.setAttribute('content', t === 'dark' ? '#0B1F14' : '#14532D'); }
    if (save) { try { localStorage.setItem('cms-theme', t); } catch (e) {} }
    document.dispatchEvent(new CustomEvent('cms-theme', { detail: t }));
  }
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-theme-toggle]');
    if (b) { apply(current() === 'dark' ? 'light' : 'dark', true); }
  });
  // Always print in the light theme to save ink, then restore.
  var before = null;
  window.addEventListener('beforeprint', function () { before = current(); if (before === 'dark') { root.setAttribute('data-bs-theme', 'light'); } });
  window.addEventListener('afterprint', function () { if (before === 'dark') { root.setAttribute('data-bs-theme', 'dark'); } before = null; });
})();

// Stacked-table labels on phones: copy each column heading into its cells (skips cells that already have one).
(function () {
  document.querySelectorAll('table.table-stack').forEach(function (t) {
    var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
    t.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (td.tagName !== 'TD' || td.hasAttribute('data-label')) { return; }
        td.setAttribute('data-label', td.colSpan > 1 ? '' : (heads[i] || ''));
      });
    });
  });
})();
