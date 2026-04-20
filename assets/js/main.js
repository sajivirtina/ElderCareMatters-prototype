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

  document.addEventListener('DOMContentLoaded', () => {
    wireFaq();
    wireReveal();
    wireCategoryCards();
  });
})();
