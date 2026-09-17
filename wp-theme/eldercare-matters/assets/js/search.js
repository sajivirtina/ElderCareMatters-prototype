/**
 * ECM Find Care — real data from /wp-json/ecm/v1/search
 * Replaces the static data.js dependency with live WP Job Manager results.
 */
(function () {
  'use strict';

  // Base URL for the REST API (works on both local and staging).
  var API_BASE = (window.ECM_SEARCH_API && window.ECM_SEARCH_API.root) || '/wp-json/ecm/v1';

  // State
  var meta      = null;   // { categories, cities }
  var lastTotal = 0;
  var loadTimer = null;

  // ── Utilities ────────────────────────────────────────────────────────────────

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function el(id) { return document.getElementById(id); }

  // ── Card rendering ───────────────────────────────────────────────────────────

  function renderCard(p) {
    var logo = p.logo_url
      ? '<img src="' + esc(p.logo_url) + '" alt="' + esc(p.name) + '" style="width:100%;height:100%;object-fit:cover;border-radius:14px" loading="lazy">'
      : esc(p.initials);

    var logoBg = p.logo_url ? '#fff' : p.color;
    var logoPad = p.logo_url ? 'overflow:hidden;padding:0' : '';

    var tierBadge = p.tier
      ? '<span class="provider-tier-badge tier-' + esc(p.tier) + '">' + esc(p.tier) + '</span>'
      : '';

    var cityLine = p.city_label
      ? '<div class="provider-card-city">📍 ' + esc(p.city_label) + '</div>'
      : '';

    var chips = (p.specialties || []).slice(0, 2).map(function (s) {
      return '<span class="specialty-chip">' + esc(s) + '</span>';
    }).join('');

    var specialtiesBlock = chips
      ? '<div class="provider-specialties">' + chips + '</div>'
      : '';

    var tagline = p.tagline
      ? '<div class="provider-card-tagline">' + esc(p.tagline) + '</div>'
      : '';

    var ratingBlock = '';
    if (p.rating > 0) {
      ratingBlock = '<div class="provider-card-row">'
        + '<span class="provider-rating">★ ' + p.rating.toFixed(1) + '</span>'
        + (p.reviews ? '<span class="provider-rating-count">(' + p.reviews + ')</span>' : '')
        + (p.cat_name ? '<span>· ' + esc(p.cat_name) + '</span>' : '')
        + '</div>';
    } else if (p.cat_name) {
      ratingBlock = '<div class="provider-card-row"><span>· ' + esc(p.cat_name) + '</span></div>';
    }

    var nonprofitBadge = p.nonprofit
      ? '<span class="provider-nonprofit-badge">'
        + '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">'
        + '<circle cx="6" cy="4.8" r="2.6" stroke="currentColor" stroke-width="1.2"/>'
        + '<path d="M3.8 7.2L2.5 11l3.5-1.4L9.5 11 8.2 7.2" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/>'
        + '</svg>Non-Profit</span>'
      : '';

    return '<a class="provider-card" href="' + esc(p.permalink) + '">'
      + '<div class="provider-card-header">'
      +   '<div class="provider-logo" style="background:' + esc(logoBg) + ';color:#fff;font-weight:700;' + logoPad + '">' + logo + '</div>'
      +   '<div class="provider-heading">'
      +     '<div class="provider-card-name">' + esc(p.name) + '</div>'
      +     cityLine
      +   '</div>'
      +   tierBadge
      + '</div>'
      + '<div class="provider-card-body">'
      +   specialtiesBlock
      +   tagline
      +   ratingBlock
      +   nonprofitBadge
      + '</div>'
      + '<div class="provider-card-footer">'
      +   '<span class="provider-card-arrow">View details →</span>'
      + '</div>'
      + '</a>';
  }

  // ── Grid update ──────────────────────────────────────────────────────────────

  function setLoading(on) {
    var grid = el('search-grid');
    if (on) {
      grid.style.opacity = '0.5';
      grid.style.pointerEvents = 'none';
    } else {
      grid.style.opacity = '';
      grid.style.pointerEvents = '';
    }
  }

  function showResults(data) {
    var grid  = el('search-grid');
    var empty = el('empty-state');
    var count = el('result-count');

    lastTotal = data.total || 0;
    if (count) count.textContent = lastTotal;

    setLoading(false);

    if (!data.providers || data.providers.length === 0) {
      grid.innerHTML = '';
      grid.style.display = 'none';
      if (empty) empty.style.display = '';
    } else {
      grid.style.display = '';
      if (empty) empty.style.display = 'none';
      grid.innerHTML = data.providers.map(renderCard).join('');
    }
  }

  // ── Filters ──────────────────────────────────────────────────────────────────

  function getFilters() {
    return {
      q:        (el('search-q')       || {}).value || '',
      category: (el('search-category')|| {}).value || '',
      city:     (el('search-city')    || {}).value || '',
      sort:     (el('sort-select')    || {}).value || 'recommended',
    };
  }

  function run() {
    var f = getFilters();
    var params = new URLSearchParams();
    if (f.q)        params.set('q',        f.q);
    if (f.category) params.set('category', f.category);
    if (f.city)     params.set('city',     f.city);
    if (f.sort)     params.set('sort',     f.sort);
    params.set('per_page', '24');

    setLoading(true);

    fetch(API_BASE + '/search?' + params.toString())
      .then(function (r) { return r.json(); })
      .then(showResults)
      .catch(function () { setLoading(false); });
  }

  function debounceRun() {
    clearTimeout(loadTimer);
    loadTimer = setTimeout(run, 200);
  }

  // ── Chip row ─────────────────────────────────────────────────────────────────

  function buildChips(categories) {
    var chipRow = el('category-chip-row');
    if (!chipRow) return;
    chipRow.innerHTML = '';

    var allChip = document.createElement('button');
    allChip.className = 'filter-chip active';
    allChip.dataset.cat = '';
    allChip.textContent = 'All';
    chipRow.appendChild(allChip);

    // Show top 10 categories by count to keep the chip row manageable.
    categories.slice(0, 10).forEach(function (c) {
      var chip = document.createElement('button');
      chip.className = 'filter-chip';
      chip.dataset.cat = c.key;
      chip.textContent = (c.icon || '') + ' ' + c.name;
      chipRow.appendChild(chip);
    });

    chipRow.addEventListener('click', function (e) {
      var chip = e.target.closest('.filter-chip');
      if (!chip) return;
      chipRow.querySelectorAll('.filter-chip').forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      var catSel = el('search-category');
      if (catSel) catSel.value = chip.dataset.cat || '';
      run();
    });
  }

  function syncChipRow(catValue) {
    var chipRow = el('category-chip-row');
    if (!chipRow) return;
    chipRow.querySelectorAll('.filter-chip').forEach(function (c) {
      c.classList.toggle('active', c.dataset.cat === catValue);
    });
  }

  // ── Populate selects ─────────────────────────────────────────────────────────

  function buildSelects(categories, cities) {
    var catSel = el('search-category');
    if (catSel) {
      // Keep the first "All categories" option
      while (catSel.options.length > 1) catSel.remove(1);
      categories.forEach(function (c) {
        var opt = document.createElement('option');
        opt.value = c.key;
        opt.textContent = (c.icon || '') + ' ' + c.name;
        catSel.appendChild(opt);
      });
    }

    var citySel = el('search-city');
    if (citySel) {
      while (citySel.options.length > 1) citySel.remove(1);
      cities.forEach(function (c) {
        var opt = document.createElement('option');
        opt.value = c.city;
        opt.textContent = c.state ? c.city + ', ' + c.state : c.city;
        citySel.appendChild(opt);
      });
    }
  }

  // ── Wiring ───────────────────────────────────────────────────────────────────

  function wire() {
    var q       = el('search-q');
    var catSel  = el('search-category');
    var citySel = el('search-city');
    var sort    = el('sort-select');
    var btn     = el('search-btn');

    if (btn)     btn.addEventListener('click',  run);
    if (q)       q.addEventListener('input',    debounceRun);
    if (q)       q.addEventListener('keydown',  function (e) { if (e.key === 'Enter') run(); });
    if (citySel) citySel.addEventListener('change', run);
    if (sort)    sort.addEventListener('change', run);

    if (catSel) {
      catSel.addEventListener('change', function () {
        syncChipRow(catSel.value);
        run();
      });
    }
  }

  // ── Prefill from URL / location ───────────────────────────────────────────────

  function prefillFromURL(categories, cities) {
    var params = new URLSearchParams(location.search);
    var urlCat = params.get('type') || params.get('category') || '';
    var urlQ   = params.get('q') || '';
    var urlCity = params.get('city') || '';

    if (urlQ) {
      var q = el('search-q');
      if (q) q.value = urlQ;
    }

    if (urlCat) {
      var catSel = el('search-category');
      if (catSel) catSel.value = urlCat;
      syncChipRow(urlCat);
    }

    if (urlCity) {
      var citySel = el('search-city');
      if (citySel) citySel.value = urlCity;
    }

    // Prefill city from JS location only if user has explicitly set a location
    // (location.js stores it in localStorage; don't prefill on first visit).
    if (!urlCity) {
      var storedLoc = null;
      try { storedLoc = JSON.parse(localStorage.getItem('ecm_location') || 'null'); } catch(e) {}
      if (storedLoc && storedLoc.city && storedLoc.userSet) {
        var citySel2 = el('search-city');
        if (citySel2) {
          Array.from(citySel2.options).forEach(function (opt) {
            if (opt.value && opt.value.toLowerCase() === storedLoc.city.toLowerCase()) {
              citySel2.value = opt.value;
            }
          });
        }
      }
    }
  }

  // ── Boot ─────────────────────────────────────────────────────────────────────

  function boot() {
    // Fetch metadata (categories + cities) first, then run initial search.
    fetch(API_BASE + '/search/meta')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        meta = data;
        buildSelects(data.categories || [], data.cities || []);
        buildChips(data.categories || []);
        prefillFromURL(data.categories, data.cities);
        wire();
        run();
      })
      .catch(function (err) {
        console.warn('ECM search meta failed, running with empty filters.', err);
        wire();
        run();
      });
  }

  document.addEventListener('DOMContentLoaded', boot);

})();
