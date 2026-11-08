// Fills the shared edit / reset modals from the clicked row's data-* attributes.
document.addEventListener('click', function (ev) {
  var b = ev.target.closest('[data-edit],[data-reset]');
  if (!b) { return; }
  var set = function (id, v) { var el = document.getElementById(id); if (el) { el.value = v; } };
  if (b.hasAttribute('data-edit')) {
    set('e_id', b.dataset.id); set('e_name', b.dataset.name); set('e_email', b.dataset.email); set('e_phone', b.dataset.phone);
  } else {
    set('r_id', b.dataset.id); set('r_pw', '');
    var n = document.getElementById('r_name'); if (n) { n.textContent = b.dataset.name; }
  }
});
