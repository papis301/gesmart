// Listes avec recherche (TomSelect) - fichier externe pour rester compatible avec la CSP.
// Options : data-unity (U.P.) et data-meta (ligne libre) s'affichent sous le libellé.
document.addEventListener('DOMContentLoaded', function () {
  if (typeof TomSelect === 'undefined') { return; }
  document.querySelectorAll('select[data-client-select]').forEach(function (el) {
    new TomSelect(el, {
      maxOptions: null,
      render: {
        option: function (data, escape) {
          var src = el.querySelector('option[value="' + CSS.escape(data.value) + '"]');
          var unity = src ? src.getAttribute('data-unity') : '';
          var meta = src ? src.getAttribute('data-meta') : '';
          return '<div class="text-start d-block"><strong>' + escape(data.text) + '</strong>' +
            (unity ? '<br><small class="text-secondary">U.P. : ' + escape(unity) + '</small>' : '') +
            (meta ? '<br><small class="text-secondary">' + escape(meta) + '</small>' : '') + '</div>';
        }
      }
    });
  });
});
