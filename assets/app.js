// Shared behaviour: dropdown menu, confirm prompts, password toggle, avatar preview
document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-menu-toggle]');
  document.querySelectorAll('.menu-list').forEach((l) => {
    const btn = l.parentElement.querySelector('[data-menu-toggle]');
    const open = t === btn && l.hidden;
    l.hidden = !open;
    btn.setAttribute('aria-expanded', String(open));
  });
  const pw = e.target.closest('[data-toggle-pw]');
  if (pw) {
    const input = document.getElementById(pw.dataset.togglePw);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    pw.textContent = show ? 'HIDE' : 'SHOW';
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') document.querySelectorAll('.menu-list').forEach((l) => (l.hidden = true));
});
document.addEventListener('submit', (e) => {
  const msg = e.target.dataset && e.target.dataset.confirm;
  if (msg && !confirm(msg)) e.preventDefault();
});
const pfpInput = document.getElementById('pfpInput');
if (pfpInput) {
  pfpInput.addEventListener('change', () => {
    const f = pfpInput.files[0];
    if (!f) return;
    const img = document.getElementById('pfpPreview');
    img.src = URL.createObjectURL(f);
    img.hidden = false;
    document.getElementById('pfpGlyph').hidden = true;
  });
}
