(function () {
  'use strict';

  const STORAGE_KEY = 'eldercare_location';
  const DEFAULT_LOCATION = { city: 'Dallas', state: 'TX', auto: false };

  // US state name <-> abbreviation (for modal dropdown -> short code display)
  const STATE_ABBR = {
    'Alabama': 'AL', 'Alaska': 'AK', 'Arizona': 'AZ', 'Arkansas': 'AR',
    'California': 'CA', 'Colorado': 'CO', 'Connecticut': 'CT', 'Delaware': 'DE',
    'Florida': 'FL', 'Georgia': 'GA', 'Hawaii': 'HI', 'Idaho': 'ID',
    'Illinois': 'IL', 'Indiana': 'IN', 'Iowa': 'IA', 'Kansas': 'KS',
    'Kentucky': 'KY', 'Louisiana': 'LA', 'Maine': 'ME', 'Maryland': 'MD',
    'Massachusetts': 'MA', 'Michigan': 'MI', 'Minnesota': 'MN', 'Mississippi': 'MS',
    'Missouri': 'MO', 'Montana': 'MT', 'Nebraska': 'NE', 'Nevada': 'NV',
    'New Hampshire': 'NH', 'New Jersey': 'NJ', 'New Mexico': 'NM', 'New York': 'NY',
    'North Carolina': 'NC', 'North Dakota': 'ND', 'Ohio': 'OH', 'Oklahoma': 'OK',
    'Oregon': 'OR', 'Pennsylvania': 'PA', 'Rhode Island': 'RI', 'South Carolina': 'SC',
    'South Dakota': 'SD', 'Tennessee': 'TN', 'Texas': 'TX', 'Utah': 'UT',
    'Vermont': 'VT', 'Virginia': 'VA', 'Washington': 'WA', 'West Virginia': 'WV',
    'Wisconsin': 'WI', 'Wyoming': 'WY'
  };

  function toAbbr(state) {
    if (!state) return '';
    if (state.length === 2) return state.toUpperCase();
    return STATE_ABBR[state] || state;
  }

  function read() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function write(loc) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(loc)); } catch (e) { /* noop */ }
  }

  function render(loc) {
    const city = loc.city || 'your area';
    const state = toAbbr(loc.state);
    const full = state ? `${city}, ${state}` : city;

    document.querySelectorAll('.js-location-city').forEach(el => { el.textContent = city; });
    document.querySelectorAll('.js-location-full').forEach(el => { el.textContent = full; });

    // Nav badge status: hide the "Detecting..." hint once we have a location
    document.querySelectorAll('.js-location-status').forEach(el => {
      el.textContent = loc.auto ? '' : '';
    });

    // Update category card hrefs to carry city/state query params
    document.querySelectorAll('.js-category-card').forEach(card => {
      const type = card.getAttribute('data-category');
      const params = new URLSearchParams({ type, city, state: state || '' });
      card.setAttribute('href', `category.html?${params.toString()}`);
    });

    // Allow page scripts (e.g. category.js) to react to location renders
    if (typeof window.ECM._onRender === 'function') window.ECM._onRender(loc);
  }

  function setLocation(city, state, opts) {
    opts = opts || {};
    const loc = { city: city || '', state: state || '', auto: !!opts.auto, lat: opts.lat || null, lon: opts.lon || null };
    write(loc);
    render(loc);
    return loc;
  }

  function detectViaGeolocation() {
    return new Promise((resolve, reject) => {
      if (!('geolocation' in navigator)) return reject(new Error('no geolocation'));
      navigator.geolocation.getCurrentPosition(
        pos => resolve(pos.coords),
        err => reject(err),
        { timeout: 6000, maximumAge: 5 * 60 * 1000 }
      );
    });
  }

  function reverseGeocode(lat, lon) {
    const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=en`;
    return fetch(url).then(r => {
      if (!r.ok) throw new Error('geocode failed');
      return r.json();
    }).then(data => ({
      city: data.city || data.locality || data.principalSubdivision || '',
      state: data.principalSubdivision || ''
    }));
  }

  async function boot() {
    // 1. Prefer stored location (respects user's explicit choice across sessions)
    const stored = read();
    if (stored && stored.city) {
      render(stored);
      return;
    }

    // 2. Render default while we try detection (avoids "undefined" flash)
    render(DEFAULT_LOCATION);

    // 3. Try browser geolocation → reverse geocode
    try {
      const coords = await detectViaGeolocation();
      const geo = await reverseGeocode(coords.latitude, coords.longitude);
      if (geo.city) {
        setLocation(geo.city, geo.state, { auto: true, lat: coords.latitude, lon: coords.longitude });
      }
    } catch (e) {
      // Denied / timeout / offline → keep default, don't nag the user
      console.info('[ECM] geolocation unavailable, using default:', DEFAULT_LOCATION.city);
    }
  }

  // Modal wiring
  function wireModal() {
    const overlay = document.getElementById('locationModal');
    if (!overlay) return;

    const badges = document.querySelectorAll('[data-open-location-modal]');
    badges.forEach(b => b.addEventListener('click', () => {
      // Pre-populate inputs from current stored/detected location
      const loc = read() || DEFAULT_LOCATION;
      const zipInput = overlay.querySelector('input[name="zip"]');
      const stateSel = overlay.querySelector('select[name="state"]');
      if (zipInput) zipInput.value = '';  // Clear ZIP — stored city names aren't valid ZIPs
      if (stateSel && loc.state) {
        // Stored state is a 2-letter abbreviation (e.g. "TX");
        // the <select> uses full names ("Texas"), so reverse-map via STATE_ABBR
        const fullName = Object.keys(STATE_ABBR).find(k => STATE_ABBR[k] === loc.state.toUpperCase());
        stateSel.value = fullName || '';
      }
      overlay.classList.add('active');
    }));

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('active');
    });

    const cancelBtn = overlay.querySelector('[data-modal-cancel]');
    if (cancelBtn) cancelBtn.addEventListener('click', () => overlay.classList.remove('active'));

    const submitBtn = overlay.querySelector('[data-modal-submit]');
    if (submitBtn) submitBtn.addEventListener('click', () => {
      const zipInput = overlay.querySelector('input[name="zip"]');
      const stateSel = overlay.querySelector('select[name="state"]');

      const zip = (zipInput && zipInput.value.trim()) || '';
      const state = (stateSel && stateSel.value) || '';

      if (zip) {
        // For a prototype we use the ZIP literal as the city label.
        setLocation(zip, state, { auto: false });
      } else if (state) {
        // Use full state name as the city display label (e.g. "Maryland")
        // so the heading reads "Find Trusted Elder Care in Maryland".
        // toAbbr() stores the 2-letter code as the state field.
        setLocation(state, toAbbr(state), { auto: false });
      }
      overlay.classList.remove('active');
      if (zipInput) zipInput.value = '';
      if (stateSel) stateSel.value = '';
    });
  }

  // Expose a tiny API so other scripts (intake.js) can read the current city.
  window.ECM = window.ECM || {};
  window.ECM.getLocation = function () { return read() || DEFAULT_LOCATION; };
  window.ECM.setLocation = setLocation;
  window.ECM._onRender = null; // page scripts can set this to react to location updates

  document.addEventListener('DOMContentLoaded', () => {
    wireModal();
    boot();
  });
})();
