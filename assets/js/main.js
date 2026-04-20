(function () {
  'use strict';

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

  // Category cards: log the payload so developers see the Phase 2 hand-off contract.
  // The href is set by location.js, so the default anchor navigation already carries
  // ?type=…&city=…&state=… — we just add an observable console log.
  function wireCategoryCards() {
    document.querySelectorAll('.js-category-card').forEach(card => {
      card.addEventListener('click', (e) => {
        const type = card.getAttribute('data-category');
        const loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
        console.log('[ECM] Category clicked →', { type, city: loc.city, state: loc.state });
        // Since category.html doesn't exist yet in Phase 1, keep the user on the page.
        e.preventDefault();
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireFaq();
    wireReveal();
    wireCategoryCards();
  });
})();
