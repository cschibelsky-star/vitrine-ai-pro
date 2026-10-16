(() => {
  'use strict';
  const menuButton = document.querySelector('.menu-button');
  const menu = document.getElementById('primary-nav');
  if (menuButton && menu) {
    const closeMenu = () => { menu.classList.remove('is-open'); menuButton.setAttribute('aria-expanded','false'); menuButton.setAttribute('aria-label','Abrir menu'); };
    menuButton.addEventListener('click', () => {
      const next = menuButton.getAttribute('aria-expanded') !== 'true';
      menu.classList.toggle('is-open', next);
      menuButton.setAttribute('aria-expanded',String(next));
      menuButton.setAttribute('aria-label',next ? 'Fechar menu' : 'Abrir menu');
    });
    menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', e => { if(e.key === 'Escape') closeMenu(); });
    document.addEventListener('click', e => { if (!menu.contains(e.target) && !menuButton.contains(e.target)) closeMenu(); });
    window.matchMedia('(min-width:821px)').addEventListener('change', e => { if (e.matches) closeMenu(); });
  }
  const filterButtons = [...document.querySelectorAll('[data-filter]')];
  const cards = [...document.querySelectorAll('.project-card[data-category]')];
  const count = document.getElementById('result-count');
  const empty = document.getElementById('filter-empty');
  filterButtons.forEach(btn => btn.addEventListener('click', () => {
    const choice = btn.dataset.filter;
    let visible = 0;
    filterButtons.forEach(b => { const selected = b === btn; b.classList.toggle('is-active',selected); b.setAttribute('aria-pressed',String(selected)); });
    cards.forEach(card => { const show = choice === 'todos' || card.dataset.category === choice; card.hidden = !show; if(show) visible++; });
    if (count) count.textContent = `${visible} ${visible === 1 ? 'projeto' : 'projetos'}`;
    if (empty) empty.hidden = visible !== 0;
  }));
  // Real tab interaction; diagrams are conceptual and contain no fabricated live data.
  const labTabs = [...document.querySelectorAll('[data-lab]')];
  const activateLab = (next, focus = false) => {
    labTabs.forEach(t => {
      const active = t === next;
      t.setAttribute('aria-selected', String(active));
      t.tabIndex = active ? 0 : -1;
      const panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) panel.hidden = !active;
    });
    if (focus) next.focus();
  };
  labTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateLab(tab));
    tab.addEventListener('keydown', (event) => {
      let i = index;
      if (event.key === 'ArrowRight') i = (index + 1) % labTabs.length;
      else if (event.key === 'ArrowLeft') i = (index - 1 + labTabs.length) % labTabs.length;
      else if (event.key === 'Home') i = 0;
      else if (event.key === 'End') i = labTabs.length - 1;
      else return;
      event.preventDefault();
      activateLab(labTabs[i], true);
    });
  });
  const year = document.getElementById('copyright-year');
  if (year) year.textContent = String(new Date().getFullYear());
})();
