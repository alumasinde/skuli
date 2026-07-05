/** subjects-index.js — extracted from views/subjects/index.php's inline <script>. */
document.getElementById('deleteSubjectModal')?.addEventListener('show.bs.modal', function (event) {
  var btn  = event.relatedTarget;
  var id   = btn.getAttribute('data-id');
  var name = btn.getAttribute('data-name');
  this.querySelector('#deleteSubjectName').textContent = name || 'this subject';
  this.querySelector('#deleteSubjectForm').action = '/subjects/' + id + '/delete';
});
