/**
 * Language cards on the profile page.
 *  - "Add another language" clones the <template> and gives it a new unique index
 *  - "Delete" removes a card from the page (the server saves exactly the cards that remain)
 *  - the same language cannot be chosen twice (already-used options are disabled)
 *  - "I am fluent" is a shortcut: it sets the four level selects to "Fluent" (it is never stored)
 * The server validates everything again, so this file is only for convenience.
 */
(function () {
  var list = document.getElementById('language-list');
  var template = document.getElementById('language-template');
  var addButton = document.getElementById('language-add');
  if (!list || !template || !addButton) { return; }

  var max = parseInt(list.getAttribute('data-max'), 10) || 8;
  // Cards already on the page use indexes 0..n-1; new cards continue from n and never reuse an index.
  var nextIndex = list.querySelectorAll('[data-language-card]').length;

  function cards() { return list.querySelectorAll('[data-language-card]'); }

  function refresh() {
    var used = {};
    cards().forEach(function (card, i) {
      card.querySelector('[data-language-title]').textContent = 'Language ' + (i + 1);
      var value = card.querySelector('[data-language-select]').value;
      if (value) { used[value] = true; }
    });
    // Disable an option when another card already uses it.
    cards().forEach(function (card) {
      var select = card.querySelector('[data-language-select]');
      Array.prototype.forEach.call(select.options, function (option) {
        option.disabled = option.value !== '' && !!used[option.value] && select.value !== option.value;
      });
    });
    addButton.disabled = cards().length >= max;
  }

  addButton.addEventListener('click', function () {
    if (cards().length >= max) { return; }
    list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)));
    refresh();
  });

  list.addEventListener('click', function (event) {
    var button = event.target.closest('[data-language-delete]');
    if (!button) { return; }
    button.closest('[data-language-card]').remove();
    refresh();
  });

  list.addEventListener('change', function (event) {
    if (event.target.matches('[data-language-select]')) {
      refresh();
    } else if (event.target.matches('[data-fluent-shortcut]') && event.target.checked) {
      event.target.closest('[data-language-card]').querySelectorAll('[data-language-level]').forEach(function (select) {
        select.value = 'fluent';
      });
    }
  });

  refresh();
})();
