/**
 * MediCore HMS v2 — Frontend JavaScript
 * 
 * Core client-side functionality including:
 * - Auto-dismissing toast notifications
 * - Confirmation dialogs for destructive actions
 */

document.addEventListener('DOMContentLoaded', function () {
  /**
   * Auto-dismiss Bootstrap Toast Notifications
   * 
   * Automatically removes success/error toast messages after 4 seconds
   * by removing the 'show' class which transitions visibility.
   */
  document.querySelectorAll('.toast').forEach(function (el) {
    setTimeout(function () { el.classList.remove('show'); }, 4000);
  });

  /**
   * Confirmation Dialog for Destructive Actions
   * 
   * Intercepts click events on elements with data-confirm attribute.
   * Displays native browser confirm dialog and prevents default action
   * if user cancels the operation.
   * 
   * Example: <button data-confirm="Delete this patient?">Delete</button>
   */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
});
