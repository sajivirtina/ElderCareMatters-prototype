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
    if (!p || !p.price) return '—';
    return `$${p.price.toLocaleString()}<span style="font-size:0.72rem;color:var(--muted);font-weight:400">${p.unit}</span>`;
  }

  function renderProviderCard(provider, cityInfo) {
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
        </div>
        <div class="nearby-price">
          <div class="nearby-price-val">from ${startingFrom(provider)}</div>
        </div>
      </a>
    `;
  }

  function renderPackageCard(pkg, provider) {
    return `
      <div class="package-card${pkg.popular ? ' package-card--popular' : ''}">
        ${pkg.popular ? '<span class="package-popular-badge">Most Popular</span>' : ''}
        <div class="package-tier-label">${escapeHtml(pkg.tier)}</div>
        <div class="package-name">${escapeHtml(pkg.name)}</div>
        <div class="package-price">${priceLabel(pkg)}</div>
        <ul class="package-features">
          ${pkg.features.map(f => `<li>${escapeHtml(f)}</li>`).join('')}
        </ul>
        <button type="button" class="package-cta" data-open-form-modal>Request this package</button>
        <div style="font-size:0.72rem;color:var(--muted);text-align:center;margin-top:10px">
          Offered by <a href="provider.html?id=${encodeURIComponent(provider.id)}" style="color:var(--sage);font-weight:600;text-decoration:none">${escapeHtml(provider.name)}</a>
        </div>
      </div>
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

  function wireFilters(providers, cityInfo) {
    const chips = document.querySelectorAll('#filter-row .filter-chip');
    const grid = document.getElementById('all-providers-grid');
    const empty = document.getElementById('no-providers-msg');

    function apply(filter) {
      const list = filter === 'all' ? providers : providers.filter(p => p.tier === filter);
      grid.innerHTML = list.map(p => renderProviderCard(p, cityInfo)).join('');
      grid.style.display = list.length ? '' : 'none';
      empty.style.display = (list.length === 0 && providers.length > 0) ? '' : 'none';
    }

    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        apply(chip.getAttribute('data-filter'));
      });
    });
  }

  function boot() {
    const D = window.ECM_DATA;
    if (!D) return;

    const { type, cityHint } = getParams();
    const cat = D.getCategory(type) || D.getCategory('home-care');
    const cityKey = resolveCityKey(cityHint);
    const cityInfo = D.cities[cityKey];

    // ── Hero + breadcrumb ──
    document.title = `${cat.name} in ${cityInfo.name} — ElderCareMatters`;
    setText('#breadcrumb-current', `${cat.name} in ${cityInfo.name}`);
    setText('#category-icon', cat.icon);
    setText('#category-name', cat.name);
    setText('#category-inline', cat.name.toLowerCase());
    setText('#category-inline-2', cat.name.toLowerCase());
    setText('#category-inline-3', cat.name.toLowerCase());
    setText('#category-blurb', cat.blurb);
    setText('#city-badge-name', `${cityInfo.name}, ${cityInfo.state}`);
    setText('#city-badge-tagline', cityInfo.tagline);
    const cityImg = document.getElementById('city-image');
    if (cityImg) cityImg.src = cityInfo.image;

    // Override .js-location-city spans on this page to show the RESOLVED city (demo-data fallback).
    // Nav badge uses .js-location-full (user's real city) and stays authoritative.
    document.querySelectorAll('.js-location-city').forEach(el => { el.textContent = cityInfo.name; });

    // ── Providers in city ──
    const providers = D.getProvidersByCategoryCity(cat.key, cityKey);
    const featured = providers.filter(p => p.tier === 'featured').slice(0, 3);
    const allGrid = document.getElementById('all-providers-grid');
    const featuredSection = document.getElementById('featured-section');
    const featuredGrid = document.getElementById('featured-grid');
    const noMsg = document.getElementById('no-providers-msg');

    setText('#hero-meta-count', `${D.providers.filter(p => p.category === cat.key).length} providers verified`);

    if (featured.length > 0) {
      featuredSection.style.display = '';
      featuredGrid.innerHTML = featured.map(p => renderProviderCard(p, cityInfo)).join('');
    }

    if (providers.length > 0) {
      allGrid.innerHTML = providers.map(p => renderProviderCard(p, cityInfo)).join('');
      noMsg.style.display = 'none';
    } else {
      allGrid.innerHTML = '';
      allGrid.style.display = 'none';
      noMsg.style.display = '';
    }

    wireFilters(providers, cityInfo);

    // ── Packages (upsell tiers) ──
    // Pull 3 tiered packages — prefer a featured provider's set; fall back to any in-category.
    const pkgSource = featured[0] || providers[0] || D.providers.find(p => p.category === cat.key);
    if (pkgSource) {
      document.getElementById('package-grid').innerHTML =
        pkgSource.packages.map(pkg => renderPackageCard(pkg, pkgSource)).join('');
    }

    // ── Nearby city packages ──
    const nearby = D.getProvidersInNearbyCities(cat.key, cityKey, 6);
    const nearbySection = document.getElementById('nearby-section');
    const nearbyGrid = document.getElementById('nearby-grid');
    if (nearby.length > 0) {
      nearbySection.style.display = '';
      nearbyGrid.innerHTML = nearby.map(renderNearbyCard).join('');
    }

    // ── Cross-sell ──
    const cross = D.getCrossSell(cat.key);
    document.getElementById('cross-sell-grid').innerHTML = renderCrossSell(cross, cityKey);

    // Rewire new [data-open-form-modal] buttons in freshly-rendered package cards.
    // (intake.js wires on load; new DOM nodes need hooking up too.)
    document.querySelectorAll('.package-cta[data-open-form-modal]').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('formModal').classList.add('active');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
