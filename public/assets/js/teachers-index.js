/** teachers-index.js — extracted from views/teachers/index.php's inline <script>. */
document.getElementById('deactivateTeacherModal')?.addEventListener('show.bs.modal', function (event) {
  var btn = event.relatedTarget;
  this.querySelector('#deactivateTeacherName').textContent = btn.getAttribute('data-name') || 'this teacher';
  this.querySelector('#deactivateTeacherForm').action = '/teachers/' + btn.getAttribute('data-id') + '/delete';
});
