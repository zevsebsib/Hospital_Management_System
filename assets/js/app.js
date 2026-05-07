// MediCore HMS v2 — Frontend JS
document.addEventListener('DOMContentLoaded', function () {
  // Auto-dismiss Bootstrap toasts
  document.querySelectorAll('.toast').forEach(function (el) {
    setTimeout(function () { el.classList.remove('show'); }, 4000);
  });
  // Confirm on data-confirm elements
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
});
