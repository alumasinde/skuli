/**
 * modal.js — replaces Bootstrap's modal.js for the 4 views that use it
 * (exams/index, subjects/index, teachers/index, super_admin/billing/tenant_detail).
 *
 * Bootstrap's JS-triggered pattern:
 *   <button data-bs-toggle="modal" data-bs-target="#foo" data-id="5">Delete</button>
 *   <div class="modal" id="foo">...</div>
 *
 * This file keeps that exact markup pattern working — you do NOT need to
 * rewrite your view files' data-bs-toggle/data-bs-target attributes, or the
 * 'show.bs.modal' event listeners that read data-id/data-name off the
 * triggering button (several of your views do exactly that, e.g. the
 * delete-confirmation modals in subjects/index.php and teachers/index.php).
 *
 * Only change needed in markup: the modal's root element needs to be a
 * <dialog> tag instead of a <div class="modal">, e.g.:
 *   BEFORE: <div class="modal fade" id="deleteSubjectModal" ...>
 *   AFTER:  <dialog class="modal" id="deleteSubjectModal">
 * Everything inside (modal-dialog, modal-content, modal-header, etc.) stays
 * the same — those are just layout classes, already defined in app.css.
 */
document.addEventListener('DOMContentLoaded', function () {

  // Wire up every [data-bs-toggle="modal"] trigger, same attribute Bootstrap used.
  document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      var targetSelector = trigger.getAttribute('data-bs-target');
      var dialog = document.querySelector(targetSelector);
      if (!dialog) return;

      // Re-emit the same 'show.bs.modal' event your views already listen for,
      // with the same event.relatedTarget shape (the button that was clicked)
      // — so existing listeners reading btn.dataset.id / btn.dataset.name
      // keep working with zero changes to that code.
      var event = new CustomEvent('show.bs.modal', { detail: { relatedTarget: trigger } });
      Object.defineProperty(event, 'relatedTarget', { value: trigger });
      dialog.dispatchEvent(event);

      dialog.showModal();
    });
  });

  // Wire up every [data-bs-dismiss="modal"] close button inside a dialog.
  document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var dialog = btn.closest('dialog.modal');
      if (dialog) dialog.close();
    });
  });

  // Click on the backdrop (outside modal-content) closes it, matching
  // Bootstrap's default behavior.
  document.querySelectorAll('dialog.modal').forEach(function (dialog) {
    dialog.addEventListener('click', function (e) {
      if (e.target === dialog) dialog.close();
    });
  });
});
