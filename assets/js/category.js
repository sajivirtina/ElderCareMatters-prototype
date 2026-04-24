(function () {
  'use strict';

  const DEFAULT_CITY_KEY = 'dallas';

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function getParams() {
    const p = new URLSearchParams(location.search);
    return {
      type: p.get('type') || 'home-care',
      cityHint: p.get('city') || ''
    };
  }

  function resolveCityKey(hintName) {
    const D = window.ECM_DATA;
    // Prefer persisted user location (authoritative)
    const loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
    let key = D.normalizeCityKey(loc.city || hintName || DEFAULT_CITY_KEY);
    if (!D.cities[key]) key = DEFAULT_CITY_KEY;
    return key;
  }

  function setText(sel, text) {
    document.querySelectorAll(sel).forEach(el => { el.textContent = text; });
  }

  function priceLabel(pkg) {
    if (pkg.unit === 'covered') return '<span class="package-price-covered">Medicare covered</span>';
    return `<span class="package-price-val">${pkg.price.toLocaleString()}</span><span class="package-price-unit">${pkg.unit}</span>`;
  }

  function startingFrom(provider) {
    const p = provider.packages[0];
    if (!p || !p.price) return '';
    return `$${p.price.toLocaleString()}<span style="font-size:0.72rem;color:var(--muted);font-weight:400">${p.unit}</span>`;
  }

  // ── Free listing card (unclaimed providers) ──
  function renderFreeListingCard(provider, cityInfo) {
    const specialties = (provider.specialties || []).slice(0, 2)
      .map(s => `<span class="specialty-chip">${escapeHtml(s)}</span>`).join('');
    return `
      <div class="provider-card provider-card--unclaimed">
        <div class="unclaimed-banner">🏷️ Unclaimed listing</div>
        <div class="provider-card-header">
          <div class="provider-logo" style="background:${provider.color}">${escapeHtml(provider.initials)}</div>
          <div class="provider-heading">
            <div class="provider-card-name">${escapeHtml(provider.name)}</div>
            <div class="provider-card-city">📍 ${escapeHtml(cityInfo.name)}, ${escapeHtml(cityInfo.state)}</div>
          </div>
          <span class="provider-tier-badge tier-free">Free</span>
        </div>
        <div class="provider-card-body">
          <div class="provider-specialties">${specialties}</div>
          <div class="provider-card-tagline">${escapeHtml(provider.tagline)}</div>
          <div class="provider-card-row">
            <span class="provider-rating">★ ${provider.rating.toFixed(1)}</span>
            <span class="provider-rating-count">(${provider.reviews})</span>
          </div>
          ${provider.nonprofit ? `<span class="provider-nonprofit-badge"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="4.8" r="2.6" stroke="currentColor" stroke-width="1.2"/><path d="M3.8 7.2L2.5 11l3.5-1.4L9.5 11 8.2 7.2" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/></svg>Non-Profit</span>` : ''}
        </div>
        <div class="provider-card-footer provider-card-footer--claim">
          <span class="claim-text">Is this your business?</span>
          <a href="#" class="claim-link">Claim this listing →</a>
        </div>
      </div>`;
  }

  function renderProviderCard(provider, cityInfo) {
    // Free tier gets its own card style
    if (provider.tier === 'free') return renderFreeListingCard(provider, cityInfo);

    const D = window.ECM_DATA;
    const catName = (D.categories[provider.category] || {}).name || '';
    const specialties = (provider.specialties || []).slice(0, 2)
      .map(s => `<span class="specialty-chip">${escapeHtml(s)}</span>`).join('');
    return `
      <a class="provider-card" href="provider.html?id=${encodeURIComponent(provider.id)}">
        <div class="provider-card-header">
          <div class="provider-logo" style="background:${provider.color}">${escapeHtml(provider.initials)}</div>
          <div class="provider-heading">
            <div class="provider-card-name">${escapeHtml(provider.name)}</div>
            <div class="provider-card-city">📍 ${escapeHtml(cityInfo.name)}, ${escapeHtml(cityInfo.state)}</div>
          </div>
          <span class="provider-tier-badge tier-${provider.tier}">${provider.tier}</span>
        </div>
        <div class="provider-card-body">
          <div class="provider-specialties">${specialties}</div>
          <div class="provider-card-tagline">${escapeHtml(provider.tagline)}</div>
          <div class="provider-card-row">
            <span class="provider-rating">★ ${provider.rating.toFixed(1)}</span>
            <span class="provider-rating-count">(${provider.reviews})</span>
            <span>· ${escapeHtml(catName)}</span>
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

  function renderNearbyCard(provider) {
    const D = window.ECM_DATA;
    const city = D.cities[provider.city] || {};
    return `
      <a class="nearby-card" href="provider.html?id=${encodeURIComponent(provider.id)}">
        <div class="nearby-logo" style="background:${provider.color}">${escapeHtml(provider.initials)}</div>
        <div class="nearby-body">
          <div class="nearby-name">${escapeHtml(provider.name)}</div>
          <div class="nearby-meta">
            <span>📍 ${escapeHtml(city.name || '')}</span>
            <span class="nearby-meta-sep">·</span>
            <span>★ ${provider.rating.toFixed(1)}</span>
          </div>
          ${provider.nonprofit ? `<span class="provider-nonprofit-badge" style="margin-top:6px"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="4.8" r="2.6" stroke="currentColor" stroke-width="1.2"/><path d="M3.8 7.2L2.5 11l3.5-1.4L9.5 11 8.2 7.2" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/></svg>Non-Profit</span>` : ''}
        </div>
        <div class="nearby-price">
          <div class="nearby-price-val">from ${startingFrom(provider)}</div>
        </div>
      </a>
    `;
  }

  function renderCrossSell(categories, cityKey) {
    return categories.map(c => `
      <a class="cross-sell-card" href="category.html?type=${encodeURIComponent(c.key)}&city=${encodeURIComponent(cityKey)}">
        <div class="cross-sell-icon">${c.icon}</div>
        <div class="cross-sell-body">
          <div class="cross-sell-name">${escapeHtml(c.name)}</div>
          <div class="cross-sell-meta">Explore ${escapeHtml(c.name.toLowerCase())} in your area</div>
        </div>
        <span class="cross-sell-arrow">→</span>
      </a>
    `).join('');
  }

  // ── Distance helper ──
  function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function sortProviders(list, sortKey, userCoords) {
    const D = window.ECM_DATA;
    const TIER = { featured: 0, premium: 1, basic: 2, free: 3 };
    const sorted = list.slice();
    if (sortKey === 'rating') {
      sorted.sort((a, b) => b.rating - a.rating);
    } else if (sortKey === 'price') {
      sorted.sort((a, b) => (a.packages[0] ? a.packages[0].price : 9999) - (b.packages[0] ? b.packages[0].price : 9999));
    } else if (sortKey === 'distance' && userCoords) {
      sorted.sort((a, b) => {
        const cA = D.cities[a.city] || {}, cB = D.cities[b.city] || {};
        const dA = (cA.lat != null) ? haversineKm(userCoords.lat, userCoords.lon, cA.lat, cA.lon) : 9999;
        const dB = (cB.lat != null) ? haversineKm(userCoords.lat, userCoords.lon, cB.lat, cB.lon) : 9999;
        return dA - dB;
      });
    } else {
      // recommended: tier order then rating
      sorted.sort((a, b) => ((TIER[a.tier] || 0) - (TIER[b.tier] || 0)) || (b.rating - a.rating));
    }
    return sorted;
  }

  // ── Providers section: search + sort + tier filter + sessionStorage cache ──
  function wireProviders(providers, cityInfo, cat, cityKey) {
    const grid     = document.getElementById('all-providers-grid');
    const empty    = document.getElementById('no-providers-msg');
    const chips    = document.querySelectorAll('#filter-row .filter-chip');
    const searchEl = document.getElementById('providers-search');
    const sortEl   = document.getElementById('providers-sort');

    // Read user coords from localStorage (stored by location.js on geolocation success)
    let storedLoc = {};
    try { storedLoc = JSON.parse(localStorage.getItem('eldercare_location') || '{}'); } catch (e) { /* noop */ }
    const userCoords = (storedLoc.lat && storedLoc.lon) ? { lat: storedLoc.lat, lon: storedLoc.lon } : null;

    let activeTier   = 'all';
    let activeSubcat = 'all';
    let searchQuery  = '';
    let activeSort   = 'recommended';
    let debounceTimer = null;

    // Wire subcategory chips (populated in boot() before wireProviders is called)
    const subcatChips = document.querySelectorAll('#subcategory-row .filter-chip[data-subcat]');
    subcatChips.forEach(chip => {
      chip.addEventListener('click', () => {
        subcatChips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        activeSubcat = chip.getAttribute('data-subcat');
        applyAndRender();
      });
    });

    function applyAndRender() {
      const cacheKey = `ecm:${cat.key}:${cityKey}:${activeTier}:${activeSubcat}:${searchQuery}:${activeSort}`;
      let list;
      try {
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
          const ids = JSON.parse(cached);
          list = ids.map(id => providers.find(p => p.id === id)).filter(Boolean);
        }
      } catch (e) { /* noop */ }

      if (!list) {
        // Filter by tier
        let filtered = activeTier === 'all' ? providers : providers.filter(p => p.tier === activeTier);
        // Filter by subcategory specialty
        if (activeSubcat !== 'all') {
          const sub = activeSubcat.toLowerCase();
          filtered = filtered.filter(p =>
            (p.specialties || []).some(s => s.toLowerCase() === sub)
          );
        }
        // Filter by search query
        if (searchQuery) {
          const q = searchQuery.toLowerCase();
          filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.tagline || '').toLowerCase().includes(q) ||
            (p.specialties || []).some(s => s.toLowerCase().includes(q))
          );
        }
        list = sortProviders(filtered, activeSort, userCoords);
        try {
          sessionStorage.setItem(cacheKey, JSON.stringify(list.map(p => p.id)));
        } catch (e) { /* noop */ }
      }

      grid.innerHTML = list.map(p => renderProviderCard(p, cityInfo)).join('');
      grid.style.display = list.length ? '' : 'none';
      empty.style.display = (!list.length && providers.length > 0) ? '' : 'none';
    }

    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        activeTier = chip.getAttribute('data-filter');
        applyAndRender();
      });
    });

    if (searchEl) {
      searchEl.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          searchQuery = searchEl.value.trim().toLowerCase();
          applyAndRender();
        }, 250);
      });
    }

    if (sortEl) {
      sortEl.addEventListener('change', () => {
        activeSort = sortEl.value;
        applyAndRender();
      });
    }

    applyAndRender(); // initial render
  }

  function boot() {
    const D = window.ECM_DATA;
    if (!D) return;

    const { type, cityHint } = getParams();
    const cat = D.getCategory(type) || D.getCategory('home-care');
    const cityKey = resolveCityKey(cityHint);
    const cityInfo = D.cities[cityKey];

    // ── Determine heading display name ──
    // When the user selected a state only (e.g. "Florida") with no specific city,
    // loc.city is the full state name and won't match any DB city key.
    // In that case show the state name in all headings instead of the fallback
    // city ("Dallas"), which would be misleading.
    const loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
    const locCityKey = D.normalizeCityKey(loc.city || '');
    const stateOnlyMode = loc.city && !D.cities[locCityKey];
    const displayCityName = stateOnlyMode ? loc.city : cityInfo.name;

    // ── Hero + breadcrumb ──
    document.title = `${cat.name} in ${displayCityName} | ElderCareMatters`;
    setText('#breadcrumb-current', `${cat.name} in ${displayCityName}`);
    setText('#category-icon', cat.icon);
    setText('#category-name', cat.name);
    setText('#category-inline', cat.name.toLowerCase());
    setText('#category-inline-3', cat.name.toLowerCase());
    setText('#category-blurb', cat.blurb);
    setText('#city-badge-name', `${cityInfo.name}, ${cityInfo.state}`);
    setText('#city-badge-tagline', cityInfo.tagline);
    const cityImg = document.getElementById('city-image');
    if (cityImg) cityImg.src = cityInfo.image;

    // Dynamic CTA button text
    const heroBtn = document.getElementById('hero-cta-btn');
    if (heroBtn) heroBtn.innerHTML = `📋 Find ${escapeHtml(cat.name)} Providers <span class="btn-arrow">→</span>`;

    // Override .js-location-city spans with the display city name.
    // In state-only mode this is the state name ("Florida"); for a real
    // city selection it is the resolved city ("Dallas", "Austin", etc.).
    document.querySelectorAll('.js-location-city').forEach(el => { el.textContent = displayCityName; });

    // Re-apply whenever location.js fires async updates (fix geolocation race)
    window.ECM._onRender = function () {
      document.querySelectorAll('.js-location-city').forEach(el => { el.textContent = displayCityName; });
    };

    // ── Providers in city ──
    const providers = D.getProvidersByCategoryCity(cat.key, cityKey);
    const noMsg     = document.getElementById('no-providers-msg');

    setText('#hero-meta-count', 'Grow your Business');

    // Initial no-providers state (wireProviders handles the grid render itself)
    if (providers.length === 0) {
      noMsg.style.display = '';
    }

    // ── Subcategory chips (category-specific, e.g. grief-counselors) ──
    const SUBCATEGORIES = {
      'grief-counselors': [
        'Bereavement Care',
        'Grief Support Group',
        'Grief Counseling',
        'Grief Support Services',
        'Bereavement Counseling',
        'Grief Support Community',
        'GriefShare'
      ]
    };
    const subRow  = document.getElementById('subcategory-row');
    const subcats = SUBCATEGORIES[cat.key];
    if (subRow && subcats) {
      subRow.innerHTML =
        `<button class="filter-chip active" data-subcat="all">All</button>` +
        subcats.map(s => `<button class="filter-chip" data-subcat="${escapeHtml(s)}">${escapeHtml(s)}</button>`).join('');
      subRow.style.display = 'flex';
    }

    // Wire interactive providers section (search + sort + filter + cache)
    wireProviders(providers, cityInfo, cat, cityKey);

    // ── Nearby city packages ──
    const nearby = D.getProvidersInNearbyCities(cat.key, cityKey, 6);
    const nearbySection = document.getElementById('nearby-section');
    const nearbyGrid    = document.getElementById('nearby-grid');
    if (nearby.length > 0) {
      nearbySection.style.display = '';
      nearbyGrid.innerHTML = nearby.map(renderNearbyCard).join('');
    }

    // ── Cross-sell ──
    const cross = D.getCrossSell(cat.key);
    document.getElementById('cross-sell-grid').innerHTML = renderCrossSell(cross, cityKey);
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
