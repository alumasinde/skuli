/** exams-index.js — extracted from views/exams/index.php's inline <script>. */
document.getElementById('deleteExamModal')?.addEventListener('show.bs.modal', function (ev) {
  var b = ev.relatedTarget;
  this.querySelector('#deleteExamName').textContent = b.getAttribute('data-name') || 'this exam';
  this.querySelector('#deleteExamForm').action = '/exams/' + b.getAttribute('data-id') + '/delete';
});
