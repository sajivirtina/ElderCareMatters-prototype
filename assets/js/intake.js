(function () {
  'use strict';

  const CARE_TYPES = [
    { key: 'home-care',       icon: '🏠', label: 'Home Care' },
    { key: 'assisted-living', icon: '🏡', label: 'Assisted Living' },
    { key: 'memory-care',     icon: '🧠', label: 'Memory Care' },
    { key: 'elder-law',       icon: '⚖️', label: 'Elder Law Attorney' },
    { key: 'care-management', icon: '📋', label: 'Care Management' },
    { key: 'hospice',         icon: '🤝', label: 'Hospice' },
    { key: 'other',           icon: '✏️', label: 'Other' }
  ];

  const URGENCY_OPTIONS = [
    { key: 'immediately', label: 'Immediately' },
    { key: '2-3-days',    label: '2–3 days' },
    { key: '1-week',      label: 'Within a week' }
  ];

  const state = {
    careType: null,
    urgency: null,
    confirmedLocation: false
  };

  function currentLocation() {
    return (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || { city: 'your area', state: '' };
  }

  function careTypeLabel(key) {
    const t = CARE_TYPES.find(c => c.key === key);
    return t ? `${t.icon} ${t.label}` : '';
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  // ── CHAT (renders into #chatPopup .chat-body) ──
  function renderChat() {
    const popup = document.getElementById('chatPopup');
    if (!popup) return;

    const stepBadge = popup.querySelector('.chat-step-badge');
    const body = popup.querySelector('.chat-body');
    if (!body) return;

    let step = 1;
    if (state.careType) step = 2;
    if (state.careType && state.confirmedLocation) step = 3;
    if (state.careType && state.confirmedLocation && state.urgency) step = 4;
    if (stepBadge) stepBadge.textContent = `Step ${step} of 4`;

    body.innerHTML = '';

    appendBubble(body, "Hi! What kind of help are you looking for? Pick one so I can match you with the right local provider 💚");
    if (state.careType) {
      appendBubble(body, careTypeLabel(state.careType), true);
    } else {
      appendOptions(body, CARE_TYPES.map(c => ({
        label: `${c.icon} ${c.label}`, value: c.key
      })), (value) => {
        state.careType = value;
        renderChat();
      });
      scrollChatToBottom(body);
      return;
    }

    const loc = currentLocation();
    appendBubble(body, `Great. I see you're in <strong>${escapeHtml(loc.city)}${loc.state ? ', ' + escapeHtml(loc.state) : ''}</strong>. Is that where you need care?`);
    if (state.confirmedLocation) {
      appendBubble(body, 'Yes, that\u2019s right', true);
    } else {
      appendOptions(body, [
        { label: 'Yes, that\u2019s right', value: 'yes' },
        { label: 'No, change location', value: 'change' }
      ], (value) => {
        if (value === 'change') {
          document.getElementById('locationModal').classList.add('active');
          return;
        }
        state.confirmedLocation = true;
        renderChat();
      }, { row: true });
      scrollChatToBottom(body);
      return;
    }

    appendBubble(body, 'How soon do you need help?');
    if (state.urgency) {
      const u = URGENCY_OPTIONS.find(o => o.key === state.urgency);
      appendBubble(body, u ? u.label : '', true);
    } else {
      appendOptions(body, URGENCY_OPTIONS.map(u => ({ label: u.label, value: u.key })), (value) => {
        state.urgency = value;
        renderChat();
      }, { row: true });
      scrollChatToBottom(body);
      return;
    }

    appendBubble(body, 'Perfect. Share your email on the next step and I\u2019ll get verified providers reaching out within 24 hours.');
    appendCompletionCard(body, () => {
      state.careType = null;
      state.urgency = null;
      state.confirmedLocation = false;
      renderChat();
    });
    scrollChatToBottom(body);
  }

  function scrollChatToBottom(body) {
    requestAnimationFrame(() => { body.scrollTop = body.scrollHeight; });
  }

  function appendBubble(container, html, isUser) {
    const el = document.createElement('div');
    el.className = 'chat-bubble' + (isUser ? ' chat-bubble-user' : '');
    el.innerHTML = html;
    container.appendChild(el);
  }

  function appendOptions(container, options, onPick, opts) {
    opts = opts || {};
    const wrap = document.createElement('div');
    wrap.className = opts.row ? 'chat-option-row' : 'chat-options';
    options.forEach(o => {
      const b = document.createElement('button');
      b.className = 'chat-option';
      b.type = 'button';
      b.textContent = o.label;
      b.addEventListener('click', () => onPick(o.value));
      wrap.appendChild(b);
    });
    container.appendChild(wrap);
  }

  function appendCompletionCard(container, onRestart) {
    const loc = currentLocation();
    const careLabel = careTypeLabel(state.careType);
    const urgencyLabel = (URGENCY_OPTIONS.find(u => u.key === state.urgency) || {}).label || '';

    const card = document.createElement('div');
    card.className = 'intake-complete';
    card.innerHTML = `
      <div class="intake-complete-icon">✓</div>
      <h4>You're all set</h4>
      <p>We'll connect you with verified providers in your area.</p>
      <div class="intake-summary">
        <div><strong>Need:</strong> ${escapeHtml(careLabel)}</div>
        <div><strong>Location:</strong> ${escapeHtml(loc.city)}${loc.state ? ', ' + escapeHtml(loc.state) : ''}</div>
        <div><strong>Urgency:</strong> ${escapeHtml(urgencyLabel)}</div>
      </div>
      <button class="form-submit" type="button" data-restart>Start over</button>
    `;
    container.appendChild(card);
    card.querySelector('[data-restart]').addEventListener('click', onRestart);
  }

  // ── FAB + chat popup wiring ──
  function wireChatFab() {
    const fab = document.getElementById('chatFab');
    const popup = document.getElementById('chatPopup');
    if (!fab || !popup) return;

    const open = () => {
      fab.classList.add('open');
      popup.classList.add('open');
    };
    const close = () => {
      fab.classList.remove('open');
      popup.classList.remove('open');
    };
    const toggle = () => {
      if (popup.classList.contains('open')) close(); else open();
    };

    fab.addEventListener('click', toggle);

    const closeBtn = popup.querySelector('[data-close-chat]');
    if (closeBtn) closeBtn.addEventListener('click', close);

    // Hero "Prefer to chat?" link also opens the popup
    document.querySelectorAll('[data-open-chat]').forEach(btn => {
      btn.addEventListener('click', open);
    });
  }

  // ── FORM modal wiring ──
  function wireFormModal() {
    const modal = document.getElementById('formModal');
    if (!modal) return;

    const card = modal.querySelector('.modal');
    const formEl = modal.querySelector('.intake-form');

    const careSel    = formEl.querySelector('select[name="care-type"]');
    const cityEl     = formEl.querySelector('select[name="city"]');
    const chips      = formEl.querySelectorAll('.urgency-chip');
    const submitBtn  = formEl.querySelector('.form-submit');

    const otherDescField       = formEl.querySelector('#other-desc-field');
    const standardLocationBlock = formEl.querySelector('#standard-location-block');
    const otherContactBlock    = formEl.querySelector('#other-contact-block');

    // ── Populate care type select ──
    CARE_TYPES.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.key;
      opt.textContent = `${c.icon} ${c.label}`;
      careSel.appendChild(opt);
    });

    // ── US States lookup (shared by populateLocationSelects + filterCitiesByState + prefillLocation) ──
    const US_STATES = [
      { abbr: 'AL', name: 'Alabama' },        { abbr: 'AK', name: 'Alaska' },
      { abbr: 'AZ', name: 'Arizona' },        { abbr: 'AR', name: 'Arkansas' },
      { abbr: 'CA', name: 'California' },     { abbr: 'CO', name: 'Colorado' },
      { abbr: 'CT', name: 'Connecticut' },    { abbr: 'DE', name: 'Delaware' },
      { abbr: 'FL', name: 'Florida' },        { abbr: 'GA', name: 'Georgia' },
      { abbr: 'HI', name: 'Hawaii' },         { abbr: 'ID', name: 'Idaho' },
      { abbr: 'IL', name: 'Illinois' },       { abbr: 'IN', name: 'Indiana' },
      { abbr: 'IA', name: 'Iowa' },           { abbr: 'KS', name: 'Kansas' },
      { abbr: 'KY', name: 'Kentucky' },       { abbr: 'LA', name: 'Louisiana' },
      { abbr: 'ME', name: 'Maine' },          { abbr: 'MD', name: 'Maryland' },
      { abbr: 'MA', name: 'Massachusetts' },  { abbr: 'MI', name: 'Michigan' },
      { abbr: 'MN', name: 'Minnesota' },      { abbr: 'MS', name: 'Mississippi' },
      { abbr: 'MO', name: 'Missouri' },       { abbr: 'MT', name: 'Montana' },
      { abbr: 'NE', name: 'Nebraska' },       { abbr: 'NV', name: 'Nevada' },
      { abbr: 'NH', name: 'New Hampshire' },  { abbr: 'NJ', name: 'New Jersey' },
      { abbr: 'NM', name: 'New Mexico' },     { abbr: 'NY', name: 'New York' },
      { abbr: 'NC', name: 'North Carolina' }, { abbr: 'ND', name: 'North Dakota' },
      { abbr: 'OH', name: 'Ohio' },           { abbr: 'OK', name: 'Oklahoma' },
      { abbr: 'OR', name: 'Oregon' },         { abbr: 'PA', name: 'Pennsylvania' },
      { abbr: 'RI', name: 'Rhode Island' },   { abbr: 'SC', name: 'South Carolina' },
      { abbr: 'SD', name: 'South Dakota' },   { abbr: 'TN', name: 'Tennessee' },
      { abbr: 'TX', name: 'Texas' },          { abbr: 'UT', name: 'Utah' },
      { abbr: 'VT', name: 'Vermont' },        { abbr: 'VA', name: 'Virginia' },
      { abbr: 'WA', name: 'Washington' },     { abbr: 'WV', name: 'West Virginia' },
      { abbr: 'WI', name: 'Wisconsin' },      { abbr: 'WY', name: 'Wyoming' }
    ];

    // ── Populate City dropdown from ECM_DATA (all cities, grouped by state) ──
    function populateLocationSelects() {
      const D = window.ECM_DATA;
      if (!D) return;
      const byState = {};
      Object.values(D.cities).forEach(c => {
        if (!byState[c.state]) byState[c.state] = [];
        byState[c.state].push(c);
      });
      Object.keys(byState).sort().forEach(abbr => {
        const stateName = (US_STATES.find(s => s.abbr === abbr) || {}).name || abbr;
        const grp = document.createElement('optgroup');
        grp.label = stateName;
        byState[abbr].forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.key;
          opt.dataset.state = c.state;
          opt.textContent = c.name;
          grp.appendChild(opt);
        });
        cityEl.appendChild(grp);
      });
    }

    // ── Filter city dropdown to a specific state abbreviation ──
    // Pass '' or null to show all cities.
    // If the state has no cities in DB, falls back to showing all cities.
    function filterCitiesByState(stateAbbr) {
      const D = window.ECM_DATA;
      if (!D) return;
      // Clear all options/optgroups except the placeholder
      while (cityEl.options.length > 1) cityEl.remove(1);
      cityEl.querySelectorAll('optgroup').forEach(g => g.remove());

      // Check whether DB has any cities for this state; fall back to all if not
      const hasMatch = stateAbbr && Object.values(D.cities).some(c => c.state === stateAbbr);
      const effectiveAbbr = hasMatch ? stateAbbr : '';

      const byState = {};
      Object.values(D.cities).forEach(c => {
        if (effectiveAbbr && c.state !== effectiveAbbr) return;
        if (!byState[c.state]) byState[c.state] = [];
        byState[c.state].push(c);
      });
      Object.keys(byState).sort().forEach(abbr => {
        const stateName = (US_STATES.find(s => s.abbr === abbr) || {}).name || abbr;
        const grp = document.createElement('optgroup');
        grp.label = stateName;
        byState[abbr].forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.key;
          opt.dataset.state = c.state;
          opt.textContent = c.name;
          grp.appendChild(opt);
        });
        cityEl.appendChild(grp);
      });
    }

    populateLocationSelects();

    // ── Prefill state label + city from current detected/stored location ──
    function prefillLocation() {
      const loc = currentLocation();
      const D = window.ECM_DATA;
      if (!D) return;
      const stateAbbr = loc.state ? (loc.state.length === 2 ? loc.state.toUpperCase() : loc.state) : '';

      // Update inline state display label
      const stateDisplay = formEl.querySelector('#form-state-display');
      if (stateDisplay) {
        const stateName = (US_STATES.find(s => s.abbr === stateAbbr) || {}).name || '';
        stateDisplay.textContent = stateName || 'your state';
      }

      // Rebuild city dropdown filtered to this state, then pre-select matching city
      filterCitiesByState(stateAbbr);
      const cityKey = D.normalizeCityKey(loc.city || '');
      if (cityKey && D.cities[cityKey]) cityEl.value = cityKey;
    }

    // Prefill immediately once options exist (covers the DOMContentLoaded path)
    prefillLocation();

    // ── Toggle Other vs Standard UI ──
    function syncOtherMode() {
      const isOther = careSel.value === 'other';
      otherDescField.style.display        = isOther ? '' : 'none';
      standardLocationBlock.style.display = isOther ? 'none' : '';
      otherContactBlock.style.display     = isOther ? '' : 'none';
      submitBtn.textContent               = isOther ? 'Submit Request →' : 'Find Providers →';
    }

    careSel.addEventListener('change', syncOtherMode);

    // ── Urgency chips ──
    let urgency = null;
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('selected'));
        chip.classList.add('selected');
        urgency = chip.getAttribute('data-value');
      });
    });

    // ── Reset ──
    function resetForm() {
      formEl.style.display = '';
      const done = card.querySelector('.intake-complete');
      if (done) done.remove();
      careSel.value = '';
      cityEl.value = '';
      chips.forEach(c => c.classList.remove('selected'));
      urgency = null;
      // Reset Other fields
      const otherDesc = formEl.querySelector('#other-desc');
      const otherPhone = formEl.querySelector('#other-phone');
      const otherEmail = formEl.querySelector('#other-email');
      if (otherDesc)  otherDesc.value  = '';
      if (otherPhone) otherPhone.value = '';
      if (otherEmail) otherEmail.value = '';
      syncOtherMode();
      prefillLocation();
    }

    function openModal() {
      modal.classList.add('active');
      prefillLocation();
    }
    function closeModal() {
      modal.classList.remove('active');
      setTimeout(resetForm, 200);
    }

    document.querySelectorAll('[data-open-form-modal]').forEach(btn => btn.addEventListener('click', openModal));

    const closeBtn = modal.querySelector('[data-close-form-modal]');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // ── Submit ──
    submitBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const careType = careSel.value;
      if (!careType) { careSel.focus(); return; }

      if (careType === 'other') {
        const otherDesc  = formEl.querySelector('#other-desc');
        const otherPhone = formEl.querySelector('#other-phone');
        const otherEmail = formEl.querySelector('#other-email');
        const desc  = otherDesc  ? otherDesc.value.trim()  : '';
        const phone = otherPhone ? otherPhone.value.trim() : '';
        const email = otherEmail ? otherEmail.value.trim() : '';
        if (!desc)  { if (otherDesc)  otherDesc.focus();  return; }
        if (!phone && !email) { if (otherPhone) otherPhone.focus(); return; }

        // Show completion card for Other (no redirect)
        formEl.style.display = 'none';
        const existing = card.querySelector('.intake-complete');
        if (existing) existing.remove();
        const completionEl = document.createElement('div');
        completionEl.className = 'intake-complete';
        completionEl.innerHTML = `
          <div class="intake-complete-icon">✓</div>
          <h4>Request received!</h4>
          <p>Our care advisor will reach out within 24 hours to understand your specific needs and connect you with the right providers.</p>
          <button class="form-submit" type="button" data-restart>Start over</button>
        `;
        card.appendChild(completionEl);
        completionEl.querySelector('[data-restart]').addEventListener('click', () => resetForm());
        return;
      }

      // Standard flow: location + urgency required, then redirect
      const cityKey  = cityEl.value;
      const stateVal = (cityEl.selectedOptions[0] && cityEl.selectedOptions[0].dataset.state) || '';
      if (!cityKey) { cityEl.focus(); return; }
      if (!urgency) {
        chips.forEach(c => c.animate(
          [{ transform: 'translateX(0)' }, { transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'translateX(0)' }],
          { duration: 240 }
        ));
        return;
      }

      const D = window.ECM_DATA;
      const cityInfo = D && D.cities[cityKey];
      const cityName = cityInfo ? cityInfo.name : cityKey;
      const params = new URLSearchParams({ type: careType, city: cityName, state: stateVal });
      window.location.href = `category.html?${params.toString()}`;
    });

    // Re-prefill when location modal closes (user may have updated their city)
    const locationModal = document.getElementById('locationModal');
    if (locationModal) {
      const obs = new MutationObserver(() => {
        if (!locationModal.classList.contains('active') && modal.classList.contains('active')) {
          prefillLocation();
        }
      });
      obs.observe(locationModal, { attributes: true, attributeFilter: ['class'] });
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireChatFab();
    wireFormModal();
    renderChat();
  });
})();
