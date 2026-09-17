(function () {
  'use strict';

  const MOBILE_NAV_BREAKPOINT = 1199;

  // FAQ accordion — only one open at a time
  function wireFaq() {
    document.querySelectorAll('.faq-q').forEach(q => {
      q.addEventListener('click', () => {
        const item = q.parentElement;
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
      });
    });
  }

  // Scroll reveal
  function wireReveal() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          observer.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  }

  // Category cards: href is set by location.js with ?type=…&city=…&state=…
  // Default anchor navigation takes the user to category.html.
  function wireCategoryCards() {
    document.querySelectorAll('.js-category-card').forEach(card => {
      card.addEventListener('click', () => {
        const type = card.getAttribute('data-category');
        const loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
        console.log('[ECM] Category clicked →', { type, city: loc.city, state: loc.state });
      });
    });
  }

  function wireMobileNav() {
    const nav = document.querySelector('nav');
    if (!nav || nav.querySelector('.nav-toggle')) return;

    const navLogo = nav.querySelector('.nav-logo');
    const navLinks = nav.querySelector('.nav-links');
    if (!navLogo || !navLinks) return;

    const navToggle = document.createElement('button');
    navToggle.type = 'button';
    navToggle.className = 'nav-toggle';
    navToggle.setAttribute('aria-label', 'Open menu');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.innerHTML = [
      '<span class="nav-toggle-icon" aria-hidden="true">',
      '<span class="nav-toggle-line"></span>',
      '<span class="nav-toggle-line"></span>',
      '<span class="nav-toggle-line"></span>',
      '</span>'
    ].join('');
    nav.appendChild(navToggle);

    const overlay = document.createElement('div');
    overlay.className = 'nav-drawer-overlay';
    overlay.setAttribute('aria-hidden', 'true');

    const drawer = document.createElement('aside');
    drawer.className = 'nav-drawer';
    drawer.setAttribute('role', 'dialog');
    drawer.setAttribute('aria-modal', 'true');
    drawer.setAttribute('aria-label', 'Site menu');

    const drawerHeader = document.createElement('div');
    drawerHeader.className = 'nav-drawer-header';
    drawerHeader.appendChild(navLogo.cloneNode(true));

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'nav-drawer-close';
    closeButton.setAttribute('aria-label', 'Close menu');
    closeButton.textContent = '×';
    drawerHeader.appendChild(closeButton);

    const drawerBody = document.createElement('div');
    drawerBody.className = 'nav-drawer-body';
    drawerBody.appendChild(navLinks.cloneNode(true));

    Array.from(nav.children)
      .filter(child => child !== navLogo && child !== navLinks && !child.classList.contains('nav-toggle'))
      .forEach(child => drawerBody.appendChild(child.cloneNode(true)));

    drawer.appendChild(drawerHeader);
    drawer.appendChild(drawerBody);
    overlay.appendChild(drawer);
    document.body.appendChild(overlay);

    const setOpen = (isOpen) => {
      overlay.classList.toggle('active', isOpen);
      overlay.setAttribute('aria-hidden', String(!isOpen));
      navToggle.setAttribute('aria-expanded', String(isOpen));
      navToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
      document.body.classList.toggle('nav-drawer-open', isOpen);
    };

    const closeMenu = () => setOpen(false);
    const openMenu = () => setOpen(true);

    navToggle.addEventListener('click', () => {
      const shouldOpen = navToggle.getAttribute('aria-expanded') !== 'true';
      if (shouldOpen) openMenu();
      else closeMenu();
    });

    closeButton.addEventListener('click', closeMenu);
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) closeMenu();
    });

    drawer.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', closeMenu);
    });

    drawer.querySelectorAll('[data-open-location-modal]').forEach(trigger => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        closeMenu();
        if (window.ECM && typeof window.ECM.openLocationModal === 'function') {
          window.ECM.openLocationModal();
        }
        if (typeof trigger.blur === 'function') trigger.blur();
      });
    });

    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeMenu();
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > MOBILE_NAV_BREAKPOINT) closeMenu();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireMobileNav();
    wireFaq();
    wireReveal();
    wireCategoryCards();
  });
})();
