// Confirmation before deletion: add data-confirm="message" to any link/button.
document.addEventListener('click', function (e) {
  const el = e.target.closest('[data-confirm]');
  if (el && !window.confirm(el.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});
