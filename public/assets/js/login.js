document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.auth-tabs__btn');
  const forms = document.querySelectorAll('.auth-form');

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const mode = tab.dataset.mode;

      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', String(active));
      });

      forms.forEach((form) => {
        form.classList.toggle('is-active', form.dataset.panel === mode);
      });

      if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('mode', mode);
        window.history.replaceState({}, '', url);
      }
    });
  });
});
