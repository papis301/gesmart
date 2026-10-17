// Empêche le double envoi des formulaires (équivalent du script jQuery de l'application d'origine).
document.addEventListener('submit', function (event) {
  var form = event.target;
  if (!(form instanceof HTMLFormElement)) { return; }
  window.setTimeout(function () {
    form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) { b.disabled = true; });
  }, 0);
});
