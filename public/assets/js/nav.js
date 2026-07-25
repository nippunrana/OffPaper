document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('navToggle');
  const panel = document.getElementById('navMobilePanel');

  if (!toggle || !panel) return;

  toggle.addEventListener('click', () => {
    const isOpen = panel.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
  });
});
