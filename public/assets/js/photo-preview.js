/**
 * photo-preview.js — shared by every form with a photo upload input:
 * students/create.php, students/edit.php, teachers/create.php,
 * teachers/edit.php (and views/exams/edit.php, which has this same markup
 * but doesn't appear to actually use a photo field — leaving it wired for
 * consistency rather than guessing whether to remove it; harmless either way
 * since it does nothing without a matching #photoPreview element + file input).
 *
 * Markup contract (unchanged from the original inline version):
 *   <img id="photoPreview" ...>
 *   <input type="file" onchange="previewPhoto(this)">
 */
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    var img = document.getElementById('photoPreview');
    img.src = URL.createObjectURL(input.files[0]);
    img.style.visibility = 'visible';
  }
}
