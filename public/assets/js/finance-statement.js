/** finance-statement.js — wires the Record Payment modal to whichever
 *  invoice row's button was clicked, same pattern as billing-tenant-detail.js. */
document.getElementById('payModal')?.addEventListener('show.bs.modal', function (ev) {
  var btn = ev.relatedTarget;
  document.getElementById('payInvoiceId').value = btn.getAttribute('data-invoice-id');
  document.getElementById('payAmount').value = btn.getAttribute('data-amount');
});