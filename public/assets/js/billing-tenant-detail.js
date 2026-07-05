/** billing-tenant-detail.js — extracted from views/super_admin/billing/tenant_detail.php's inline <script>. */
document.getElementById('payModal')?.addEventListener('show.bs.modal', function (ev) {
  var btn = ev.relatedTarget;
  var invoiceId = btn.getAttribute('data-invoice-id');
  document.getElementById('payForm').action = '/super-admin/billing/invoices/' + invoiceId + '/pay';
  document.getElementById('payAmount').value = btn.getAttribute('data-amount');
});
