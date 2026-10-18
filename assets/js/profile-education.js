/**
 * Education cards on the profile page.
 *  - at least 1 card must remain (the Delete button is hidden when only one card is left)
 *  - "Add another education" clones the <template> and gives it a new unique index (maximum from data-max)
 * The server validates everything again; this file is only for convenience.
 */
(function () {
  var list = document.getElementById('education-list');
  var template = document.getElementById('education-template');
  var addButton = document.getElementById('education-add');
  if (!list || !template || !addButton) { return; }

  var max = parseInt(list.getAttribute('data-max'), 10) || 5;
  // Cards already on the page use indexes 0..n-1; new cards continue from n and never reuse an index.
  var nextIndex = list.querySelectorAll('[data-education-card]').length;

  function cards() { return list.querySelectorAll('[data-education-card]'); }

  function refresh() {
    var all = cards();
    all.forEach(function (card, i) {
      card.querySelector('[data-education-title]').textContent = 'Education ' + (i + 1);
      // The last remaining card cannot be deleted: a profile needs at least one education entry.
      card.querySelector('[data-education-delete]').hidden = all.length <= 1;
    });
    addButton.disabled = all.length >= max;
  }

  addButton.addEventListener('click', function () {
    if (cards().length >= max) { return; }
    list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)));
    refresh();
  });

  list.addEventListener('click', function (event) {
    var button = event.target.closest('[data-education-delete]');
    if (!button || cards().length <= 1) { return; }
    button.closest('[data-education-card]').remove();
    refresh();
  });

  refresh();
})();
