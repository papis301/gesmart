// Empêche le double envoi des formulaires (équivalent du script jQuery de l'application d'origine).
document.addEventListener('submit', function (event) {
  var form = event.target;
  if (!(form instanceof HTMLFormElement)) { return; }
  window.setTimeout(function () {
    form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) { b.disabled = true; });
  }, 0);
});

// Rouvre une fenêtre modale marquée data-auto-open (ex. formulaire de création refusé).
document.addEventListener('DOMContentLoaded', function () {
  if (typeof bootstrap === 'undefined') { return; }
  document.querySelectorAll('.modal[data-auto-open]').forEach(function (el) {
    new bootstrap.Modal(el).show();
  });
});