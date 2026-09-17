/**
 * ECM Category Page — live data from /wp-json/ecm/v1/category
 *
 * Replaces the data.js dependency for providers and category metadata.
 * City images/taglines/nearby still use data.js as geographic reference data.
 */
(function () {
  'use strict';

  var API_BASE = (window.ECM_SEARCH_API && window.ECM_SEARCH_API.root) || '/wp-json/ecm/v1';

  // ── Utilities ────────────────────────────────────────────────────────────────

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function el(id) { return document.getElementById(id); }

  function setText(sel, text) {
    document.querySelectorAll(sel).forEach(function (node) { node.textContent = text; });
  }

  function getParams() {
    var p = new URLSearchParams(location.search);
    return {
      type:     p.get('type')  || 'home-care',
      cityHint: p.get('city')  || '',
      state:    p.get('state') || '',
    };
  }

  // ── Card renderers ───────────────────────────────────────────────────────────

  function renderProviderCard(p, displayCity) {
    var logo = p.logo_url
      ? '<img src="' + esc(p.logo_url) + '" alt="' + esc(p.name) + '" style="width:100%;height:100%;object-fit:cover;border-radius:14px" loading="lazy">'
      : esc(p.initials);
    var logoBg  = p.logo_url ? '#fff' : p.color;
    var logoPad = p.logo_url ? 'overflow:hidden;padding:0' : '';

    var tierBadge = p.tier
      ? '<span class="provider-tier-badge tier-' + esc(p.tier) + '">' + esc(p.tier) + '</span>'
      : '';

    var cityLine = (p.city_label || displayCity)
      ? '<div class="provider-card-city">📍 ' + esc(p.city_label || displayCity) + '</div>'
      : '';

    var chips = (p.specialties || []).slice(0, 2).map(function (s) {
      return '<span class="specialty-chip">' + esc(s) + '</span>';
    }).join('');

    var ratingBlock = '';
    if (p.rating > 0) {
      ratingBlock = '<div class="provider-card-row">'
        + '<span class="provider-rating">★ ' + p.rating.toFixed(1) + '</span>'
        + (p.reviews ? '<span class="provider-rating-count">(' + p.reviews + ')</span>' : '')
        + (p.cat_name ? '<span>· ' + esc(p.cat_name) + '</span>' : '')
        + '</div>';
    }

    var nonprofitBadge = p.nonprofit
      ? '<span class="provider-nonprofit-badge"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="4.8" r="2.6" stroke="currentColor" stroke-width="1.2"/><path d="M3.8 7.2L2.5 11l3.5-1.4L9.5 11 8.2 7.2" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/></svg>Non-Profit</span>'
      : '';

    return '<a class="provider-card" href="' + esc(p.permalink) + '" data-tier="' + esc(p.tier) + '">'
      + '<div class="provider-card-header">'
      +   '<div class="provider-logo" style="background:' + esc(logoBg) + ';color:#fff;font-weight:700;' + logoPad + '">' + logo + '</div>'
      +   '<div class="provider-heading">'
      +     '<div class="provider-card-name">' + esc(p.name) + '</div>'
      +     cityLine
      +   '</div>'
      +   tierBadge
      + '</div>'
      + '<div class="provider-card-body">'
      +   (chips ? '<div class="provider-specialties">' + chips + '</div>' : '')
      +   (p.tagline ? '<div class="provider-card-tagline">' + esc(p.tagline) + '</div>' : '')
      +   ratingBlock
      +   nonprofitBadge
      + '</div>'
      + '<div class="provider-card-footer">'
      +   '<span class="provider-card-arrow">View details →</span>'
      + '</div>'
      + '</a>';
  }

  function renderNearbyCard(p) {
    var logo = p.logo_url
      ? '<img src="' + esc(p.logo_url) + '" alt="' + esc(p.name) + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px" loading="lazy">'
      : esc(p.initials);
    var logoBg  = p.logo_url ? '#fff' : p.color;
    var logoPad = p.logo_url ? 'overflow:hidden;padding:0' : '';
    return '<a class="nearby-card" href="' + esc(p.permalink) + '">'
      + '<div class="nearby-logo" style="background:' + esc(logoBg) + ';color:#fff;font-weight:700;' + logoPad + '">' + logo + '</div>'
      + '<div class="nearby-body">'
      +   '<div class="nearby-name">' + esc(p.name) + '</div>'
      +   '<div class="nearby-meta">'
      +     (p.city_label ? '<span>📍 ' + esc(p.city_label) + '</span>' : '')
      +     (p.rating > 0 ? '<span class="nearby-meta-sep">·</span><span>★ ' + p.rating.toFixed(1) + '</span>' : '')
      +   '</div>'
      + '</div>'
      + '<div class="nearby-price"><span class="provider-card-arrow">View →</span></div>'
      + '</a>';
  }

  function renderCrossSellCard(c, cityHint) {
    var href = home_url('/category/') + '?type=' + encodeURIComponent(c.key)
      + (cityHint ? '&city=' + encodeURIComponent(cityHint) : '');
    return '<a class="cross-sell-card" href="' + esc(href) + '">'
      + '<div class="cross-sell-icon">' + esc(c.icon) + '</div>'
      + '<div class="cross-sell-body">'
      +   '<div class="cross-sell-name">' + esc(c.name) + '</div>'
      +   '<div class="cross-sell-meta">Explore ' + esc(c.name.toLowerCase()) + ' in your area</div>'
      + '</div>'
      + '<span class="cross-sell-arrow">→</span>'
      + '</a>';
  }

  // home_url helper — reads from localized data or falls back to origin.
  function home_url(path) {
    var base = (window.ECM_SEARCH_API && window.ECM_SEARCH_API.home) || window.location.origin;
    return base.replace(/\/$/, '') + path;
  }

  // ── Provider grid wiring (search + sort + tier filter) ───────────────────────

  function wireProviders(allProviders, displayCity, fetchPage) {
    var grid      = el('all-providers-grid');
    var empty     = el('no-providers-msg');
    var chips     = document.querySelectorAll('#filter-row .filter-chip');
    var searchEl  = el('providers-search');
    var sortEl    = el('providers-sort');
    var activeTier   = 'all';
    var activeSubcat = 'all';
    var searchQuery  = '';
    var debounce     = null;

    // Subcategory chips
    var subcatChips = document.querySelectorAll('#subcategory-row .filter-chip[data-subcat]');
    subcatChips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        subcatChips.forEach(function (c) { c.classList.remove('active'); });
        chip.classList.add('active');
        activeSubcat = chip.getAttribute('data-subcat');
        render();
      });
    });

    function render() {
      var list = allProviders.slice();

      // Tier filter
      if (activeTier !== 'all') {
        list = list.filter(function (p) { return p.tier === activeTier; });
      }
      // Subcategory filter
      if (activeSubcat !== 'all') {
        var sub = activeSubcat.toLowerCase();
        list = list.filter(function (p) {
          return (p.specialties || []).some(function (s) { return s.toLowerCase() === sub; });
        });
      }
      // Text search
      if (searchQuery) {
        var q = searchQuery.toLowerCase();
        list = list.filter(function (p) {
          return p.name.toLowerCase().includes(q)
            || (p.tagline || '').toLowerCase().includes(q)
            || (p.specialties || []).some(function (s) { return s.toLowerCase().includes(q); });
        });
      }
      // Sort (client-side; data already arrives sorted by recommended)
      if (sortEl && sortEl.value === 'rating') {
        list.sort(function (a, b) { return b.rating - a.rating; });
      } else if (sortEl && sortEl.value === 'newest') {
        // already date-sorted from server; no re-sort needed
      }

      grid.innerHTML = list.map(function (p) { return renderProviderCard(p, displayCity); }).join('');
      grid.style.display   = list.length ? '' : 'none';
      if (empty) empty.style.display = (!list.length) ? '' : 'none';
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        chips.forEach(function (c) { c.classList.remove('active'); });
        chip.classList.add('active');
        activeTier = chip.getAttribute('data-filter');
        render();
      });
    });

    if (searchEl) {
      searchEl.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          searchQuery = searchEl.value.trim().toLowerCase();
          render();
        }, 250);
      });
    }

    if (sortEl) {
      sortEl.addEventListener('change', function () {
        if (sortEl.value === 'recommended' || sortEl.value === 'newest') {
          // re-fetch from server for these sort modes
          fetchPage(sortEl.value);
        } else {
          render();
        }
      });
    }

    render();
    return render; // expose for external re-render
  }

  // ── Boot ─────────────────────────────────────────────────────────────────────

  function boot() {
    var params   = getParams();
    var type     = params.type;
    var cityHint = params.cityHint;
    var state    = params.state;

    // City display name: use URL ?city= param, or location.js city, or 'your area'
    var loc = (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || {};
    var displayCity = cityHint || loc.city || 'your area';

    // City hero image/tagline from data.js (geographic reference data — stays static)
    var D = window.ECM_DATA;
    var cityKey = D && D.normalizeCityKey ? D.normalizeCityKey(displayCity) : displayCity.toLowerCase().replace(/\s+/g, '-');
    var cityInfo = (D && D.cities && D.cities[cityKey]) || { name: displayCity, state: state || '', tagline: '', image: '' };
    var cityName = cityInfo.name || displayCity;

    // Update all city spans immediately (before fetch)
    document.querySelectorAll('.js-location-city').forEach(function (node) {
      node.textContent = cityName;
    });

    // City hero image
    var cityImg = el('city-image');
    if (cityImg && cityInfo.image) {
      cityImg.src = cityInfo.image;
      cityImg.alt = cityName;
    }
    if (el('city-badge-name'))    el('city-badge-name').textContent    = cityName + (cityInfo.state ? ', ' + cityInfo.state : '');
    if (el('city-badge-tagline')) el('city-badge-tagline').textContent = cityInfo.tagline || '';

    // ── Fetch category + providers ──────────────────────────────────────────
    var currentSort = 'recommended';

    function fetchPage(sort) {
      currentSort = sort || currentSort;
      var params = new URLSearchParams({ type: type, per_page: '50', sort: currentSort });
      if (cityHint) params.set('city', cityHint);

      fetch(API_BASE + '/category?' + params.toString())
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var cat       = data.category || {};
          var providers = data.providers || [];
          var total     = data.total || 0;

          // ── Hero ──
          var catName  = cat.name || type;
          var catIcon  = cat.icon || '🏥';
          var catBlurb = cat.blurb || '';

          document.title = catName + ' in ' + cityName + ' | ElderCareMatters';
          setText('#breadcrumb-current', catName + ' in ' + cityName);
          setText('#category-icon',      catIcon);
          setText('#category-name',      catName);
          setText('#category-inline',    catName.toLowerCase());
          setText('#category-inline-3',  catName.toLowerCase());
          setText('#category-blurb',     catBlurb);
          setText('#hero-meta-count',    total ? total + ' providers verified' : 'Grow your Business');

          var heroBtn = el('hero-cta-btn');
          if (heroBtn) heroBtn.innerHTML = '📋 Find ' + esc(catName) + ' Providers <span class="btn-arrow">→</span>';

          // ── Providers grid ──
          var grid  = el('all-providers-grid');
          var empty = el('no-providers-msg');

          if (providers.length === 0) {
            if (grid)  grid.style.display  = 'none';
            if (empty) empty.style.display = '';
            // Fetch without city filter as "nearby" fallback
            fetchNearby(type, cityHint);
          } else {
            if (empty) empty.style.display = 'none';

            // Subcategory chips (for grief-counselors etc.)
            var SUBCATS = { 'grief-counselors': ['Bereavement Care','Grief Support Group','Grief Counseling','Grief Support Services','Bereavement Counseling'] };
            var subRow = el('subcategory-row');
            if (subRow && SUBCATS[type]) {
              subRow.innerHTML = '<button class="filter-chip active" data-subcat="all">All</button>'
                + SUBCATS[type].map(function (s) {
                    return '<button class="filter-chip" data-subcat="' + esc(s) + '">' + esc(s) + '</button>';
                  }).join('');
              subRow.style.display = 'flex';
            }

            wireProviders(providers, cityName, fetchPage);
          }

          // ── Cross-sell ──
          var crossGrid = el('cross-sell-grid');
          if (crossGrid && cat.cross_sell && cat.cross_sell.length) {
            crossGrid.innerHTML = cat.cross_sell.map(function (c) {
              return renderCrossSellCard(c, cityHint);
            }).join('');
            var crossSection = el('cross-sell-section');
            if (crossSection) crossSection.style.display = '';
          } else {
            // Fallback cross-sell from data.js
            if (D && D.getCrossSell) {
              var cross = D.getCrossSell(type);
              if (crossGrid && cross.length) {
                crossGrid.innerHTML = cross.map(function (c) {
                  return renderCrossSellCard(c, cityHint);
                }).join('');
              }
            }
          }
        })
        .catch(function (err) {
          console.warn('ECM category fetch failed', err);
          // Fall back to data.js if API fails
          if (D) fallbackToDataJs(type, cityKey, cityInfo, cityName, cityHint);
        });
    }

    fetchPage('recommended');

    // React to city changes made via the header location modal.
    // location.js calls window.ECM._onRender(loc) whenever the city is updated.
    if (window.ECM) {
      window.ECM._onRender = function (newLoc) {
        if (!newLoc || !newLoc.city) return;

        // Update closed-over variables so fetchPage() uses the new city.
        cityHint    = newLoc.city;
        displayCity = newLoc.city;
        cityName    = newLoc.city;
        state       = newLoc.state || state;

        // Update city image/badge from data.js
        var newKey  = D && D.normalizeCityKey ? D.normalizeCityKey(cityHint) : cityHint.toLowerCase().replace(/\s+/g, '-');
        var newInfo = (D && D.cities && D.cities[newKey]) || { name: cityHint, state: newLoc.state || '', tagline: '', image: '' };
        cityName = newInfo.name || cityHint;

        document.querySelectorAll('.js-location-city').forEach(function (node) {
          node.textContent = cityName;
        });
        var img = el('city-image');
        if (img && newInfo.image) { img.src = newInfo.image; img.alt = cityName; }
        if (el('city-badge-name'))    el('city-badge-name').textContent    = cityName + (newInfo.state ? ', ' + newInfo.state : '');
        if (el('city-badge-tagline')) el('city-badge-tagline').textContent = newInfo.tagline || '';

        // Re-fetch providers for the new city.
        fetchPage(currentSort);
      };
    }
  }

  // ── Nearby providers (shown when city returns 0 results) ──────────────────

  function fetchNearby(type, excludeCity) {
    var params = new URLSearchParams({ type: type, per_page: '12', sort: 'recommended' });
    // Don't filter by city — get all providers for the category
    fetch(API_BASE + '/category?' + params.toString())
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var providers = (data.providers || []).filter(function (p) {
          // Exclude providers already in the current city
          return !excludeCity || !p.city || p.city.toLowerCase() !== excludeCity.toLowerCase();
        }).slice(0, 9);

        var nearbySection = el('nearby-section');
        var nearbyGrid    = el('nearby-grid');
        if (providers.length && nearbyGrid) {
          nearbyGrid.innerHTML = providers.map(renderNearbyCard).join('');
          if (nearbySection) nearbySection.style.display = '';
        }
      })
      .catch(function () {});
  }

  // ── Fallback to data.js if REST API is unavailable ────────────────────────

  function fallbackToDataJs(type, cityKey, cityInfo, cityName, cityHint) {
    var D = window.ECM_DATA;
    if (!D) return;
    var cat       = D.getCategory(type) || D.getCategory('home-care');
    var providers = D.getProvidersByCategoryCity(cat.key, cityKey);

    document.title = cat.name + ' in ' + cityName + ' | ElderCareMatters';
    setText('#breadcrumb-current', cat.name + ' in ' + cityName);
    setText('#category-icon',   cat.icon);
    setText('#category-name',   cat.name);
    setText('#category-inline', cat.name.toLowerCase());
    setText('#category-blurb',  cat.blurb);

    var grid = el('all-providers-grid');
    if (grid) {
      // data.js providers don't have permalinks; link to find-care instead
      grid.innerHTML = providers.map(function (p) {
        return renderProviderCard(Object.assign({}, p, { permalink: home_url('/find-care/') }), cityName);
      }).join('');
    }
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
