(function () {
  'use strict';

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function startingFrom(provider) {
    const p = (provider.packages || [])[0];
    if (!p || !p.price) return '';
    return `$${p.price.toLocaleString()}<span style="font-size:0.72rem;color:var(--muted);font-weight:400">${p.unit}</span>`;
  }

  function renderProviderCard(provider) {
    const D = window.ECM_DATA;
    const cat = D.categories[provider.category] || {};
    const city = D.cities[provider.city] || {};
    const specialties = (provider.specialties || []).slice(0, 2)
      .map(s => `<span class="specialty-chip">${escapeHtml(s)}</span>`).join('');
    return `
      <a class="provider-card" href="provider.html?id=${encodeURIComponent(provider.id)}">
        <div class="provider-card-header">
          <div class="provider-logo" style="background:${provider.color}">${escapeHtml(provider.initials)}</div>
          <div class="provider-heading">
            <div class="provider-card-name">${escapeHtml(provider.name)}</div>
            <div class="provider-card-city">📍 ${escapeHtml(city.name || '')}, ${escapeHtml(city.state || '')}</div>
          </div>
          <span class="provider-tier-badge tier-${provider.tier}">${provider.tier}</span>
        </div>
        <div class="provider-card-body">
          <div class="provider-specialties">${specialties}</div>
          <div class="provider-card-tagline">${escapeHtml(provider.tagline)}</div>
          <div class="provider-card-row">
            <span class="provider-rating">★ ${provider.rating.toFixed(1)}</span>
            <span class="provider-rating-count">(${provider.reviews})</span>
            <span>· ${escapeHtml(cat.name || '')}</span>
          </div>
          ${provider.nonprofit ? `<span class="provider-nonprofit-badge"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="4.8" r="2.6" stroke="currentColor" stroke-width="1.2"/><path d="M3.8 7.2L2.5 11l3.5-1.4L9.5 11 8.2 7.2" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/></svg>Non-Profit</span>` : ''}
        </div>
        <div class="provider-card-footer">
          <div class="provider-price">from <strong>${startingFrom(provider)}</strong></div>
          <span class="provider-card-arrow">View details →</span>
        </div>
      </a>
    `;
  }

  function populateSelects() {
    const D = window.ECM_DATA;
    const catSel = document.getElementById('search-category');
    Object.values(D.categories).forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.key;
      opt.textContent = `${c.icon} ${c.name}`;
      catSel.appendChild(opt);
    });

    const citySel = document.getElementById('search-city');
    Object.values(D.cities).forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.key;
      opt.textContent = `${c.name}, ${c.state}`;
      citySel.appendChild(opt);
    });

    // Chip row for quick category filtering
    const chipRow = document.getElementById('category-chip-row');
    const allChip = document.createElement('button');
    allChip.className = 'filter-chip active';
    allChip.dataset.cat = '';
    allChip.textContent = 'All';
    chipRow.appendChild(allChip);
    Object.values(D.categories).forEach(c => {
      const chip = document.createElement('button');
      chip.className = 'filter-chip';
      chip.dataset.cat = c.key;
      chip.textContent = `${c.icon} ${c.name}`;
      chipRow.appendChild(chip);
    });
  }

  function sortResults(list, key) {
    const sorted = list.slice();
    if (key === 'rating') sorted.sort((a, b) => b.rating - a.rating);
    else if (key === 'reviews') sorted.sort((a, b) => b.reviews - a.reviews);
    else if (key === 'price') sorted.sort((a, b) => (a.packages[0].price || 0) - (b.packages[0].price || 0));
    else {
      // default: recommended = tier then rating
      const order = { featured: 0, premium: 1, basic: 2 };
      sorted.sort((a, b) => (order[a.tier] - order[b.tier]) || (b.rating - a.rating));
    }
    return sorted;
  }

  function prefillCity() {
    const D = window.ECM_DATA;
    const loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
    const key = D.normalizeCityKey(loc.city || '');
    const citySel = document.getElementById('search-city');
    if (key && D.cities[key] && citySel) citySel.value = key;
  }

  function run() {
    const D = window.ECM_DATA;
    const q = document.getElementById('search-q').value;
    const cat = document.getElementById('search-category').value;
    const city = document.getElementById('search-city').value;
    const sort = document.getElementById('sort-select').value;

    const results = sortResults(
      D.search(q, { category: cat || null, city: city || null }),
      sort
    );

    const grid = document.getElementById('search-grid');
    const empty = document.getElementById('empty-state');
    document.getElementById('result-count').textContent = results.length;

    if (results.length === 0) {
      grid.innerHTML = '';
      grid.style.display = 'none';
      empty.style.display = '';
    } else {
      grid.style.display = '';
      empty.style.display = 'none';
      grid.innerHTML = results.map(renderProviderCard).join('');
    }
  }

  function wire() {
    const btn = document.getElementById('search-btn');
    const q = document.getElementById('search-q');
    const catSel = document.getElementById('search-category');
    const citySel = document.getElementById('search-city');
    const sort = document.getElementById('sort-select');

    btn.addEventListener('click', run);
    q.addEventListener('input', run);
    catSel.addEventListener('change', () => {
      // keep chip row in sync
      document.querySelectorAll('#category-chip-row .filter-chip').forEach(c => {
        c.classList.toggle('active', c.dataset.cat === catSel.value);
      });
      run();
    });
    citySel.addEventListener('change', run);
    sort.addEventListener('change', run);

    document.querySelectorAll('#category-chip-row .filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('#category-chip-row .filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        catSel.value = chip.dataset.cat || '';
        run();
      });
    });

    q.addEventListener('keydown', (e) => { if (e.key === 'Enter') run(); });
  }

  function boot() {
    if (!window.ECM_DATA) return;
    populateSelects();

    // Seed from URL params (so homepage category clicks can deep-link here if needed).
    const params = new URLSearchParams(location.search);
    const urlCat = params.get('type');
    const urlQ = params.get('q');
    if (urlCat) document.getElementById('search-category').value = urlCat;
    if (urlQ) document.getElementById('search-q').value = urlQ;

    prefillCity();
    wire();

    // Sync chip row with initial category, then run the first search
    const initialCat = document.getElementById('search-category').value;
    document.querySelectorAll('#category-chip-row .filter-chip').forEach(c => {
      c.classList.toggle('active', c.dataset.cat === initialCat);
    });
    run();
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
