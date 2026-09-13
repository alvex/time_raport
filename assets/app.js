// HemFresh TimeRapport — minimal progressive enhancement.
// 1) Two-step confirm for forms with data-confirm (no native dialogs).
// 2) Disable submit buttons on submit (first line of double-click defense;
//    the server's one-time token and is_pending checks are authoritative).
document.addEventListener('submit', function (ev) {
  var form = ev.target;
  var btn = form.querySelector('button[type="submit"]');

  if (form.hasAttribute('data-confirm') && !form.dataset.confirmed) {
    ev.preventDefault();
    if (btn) {
      btn.dataset.originalText = btn.textContent;
      btn.textContent = form.getAttribute('data-confirm');
      btn.classList.add('btn-confirming');
    }
    form.dataset.confirmed = '1';
    // Reset if the user hesitates.
    setTimeout(function () {
      if (form.dataset.confirmed === '1' && btn && btn.dataset.originalText) {
        btn.textContent = btn.dataset.originalText;
        btn.classList.remove('btn-confirming');
        delete form.dataset.confirmed;
      }
    }, 5000);
    return;
  }

  var sending = document.documentElement.getAttribute('data-i18n-sending') || 'Skickar…';
  // Defer: disabling synchronously would drop the clicked button's
  // name=value pair (e.g. the language choice) from the submitted form.
  setTimeout(function () {
    form.querySelectorAll('button[type="submit"]').forEach(function (b) {
      b.disabled = true;
      if (!b.name) { b.textContent = sending; }
    });
  }, 0);
});
