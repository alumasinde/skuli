/**
 * nav-toggle.js — replaces Bootstrap's collapse.js for the single use in
 * views/marketing/layout.php (the mobile hamburger menu).
 *
 * Markup stays the same:
 *   <button data-bs-toggle="collapse" data-bs-target="#nav">...
 *   <div class="collapse navbar-collapse" id="nav">...
 *
 * Just add .show / remove it — pair with a tiny CSS rule:
 *   .collapse { display: none; }
 *   .collapse.show { display: block; }
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-bs-target'));
      if (target) target.classList.toggle('show');
    });
  });
});
