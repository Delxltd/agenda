// Modern Login UI interactions
(() => {
  const $ = (sel, ctx=document) => ctx.querySelector(sel);

  // Password visibility toggles
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="toggle-visibility"]');
    if (!btn) return;
    const targetSel = btn.getAttribute('data-target');
    const input = $(targetSel);
    if (!input) return;
    const isPw = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPw ? 'text' : 'password');
    btn.setAttribute('aria-label', isPw ? 'Verberg wachtwoord' : 'Toon wachtwoord');
  }, {passive:true});

  // Disable submit button briefly to avoid double submit
  const form = $('.auth-form');
  if (form) {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        setTimeout(() => { btn.disabled = false }, 2000);
      }
    });
  }
})();
