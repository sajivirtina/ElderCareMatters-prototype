(function () {
  'use strict';

  const CARE_TYPES = [
    { key: 'home-care',       icon: '🏠', label: 'Home Care' },
    { key: 'assisted-living', icon: '🏡', label: 'Assisted Living' },
    { key: 'memory-care',     icon: '🧠', label: 'Memory Care' },
    { key: 'elder-law',       icon: '⚖️', label: 'Elder Law Attorney' },
    { key: 'care-management', icon: '📋', label: 'Care Management' },
    { key: 'hospice',         icon: '🤝', label: 'Hospice' }
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

    const careSel = formEl.querySelector('select[name="care-type"]');
    const cityInput = formEl.querySelector('input[name="city"]');
    const chips = formEl.querySelectorAll('.urgency-chip');
    const submit = formEl.querySelector('.form-submit');

    CARE_TYPES.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.key;
      opt.textContent = `${c.icon} ${c.label}`;
      careSel.appendChild(opt);
    });

    const syncCity = () => {
      const loc = currentLocation();
      if (!cityInput.dataset.touched) cityInput.value = loc.city || '';
    };
    syncCity();
    setInterval(syncCity, 1500); // cheap: lets auto-detected city flow in
    cityInput.addEventListener('input', () => { cityInput.dataset.touched = '1'; });

    let urgency = null;
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('selected'));
        chip.classList.add('selected');
        urgency = chip.getAttribute('data-value');
      });
    });

    function resetForm() {
      formEl.style.display = '';
      const done = card.querySelector('.intake-complete');
      if (done) done.remove();
      careSel.value = '';
      cityInput.value = currentLocation().city || '';
      delete cityInput.dataset.touched;
      chips.forEach(c => c.classList.remove('selected'));
      urgency = null;
    }

    function openModal() { modal.classList.add('active'); }
    function closeModal() {
      modal.classList.remove('active');
      // Reset after the close transition so the next open is fresh
      setTimeout(resetForm, 200);
    }

    document.querySelectorAll('[data-open-form-modal]').forEach(btn => btn.addEventListener('click', openModal));

    const closeBtn = modal.querySelector('[data-close-form-modal]');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    submit.addEventListener('click', (e) => {
      e.preventDefault();
      const careType = careSel.value;
      const city = cityInput.value.trim();

      if (!careType) { careSel.focus(); return; }
      if (!city) { cityInput.focus(); return; }
      if (!urgency) {
        chips.forEach(c => c.animate(
          [{ transform: 'translateX(0)' }, { transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'translateX(0)' }],
          { duration: 240 }
        ));
        return;
      }

      state.careType = careType;
      state.urgency = urgency;
      state.confirmedLocation = true;

      formEl.style.display = 'none';
      const existing = card.querySelector('.intake-complete');
      if (existing) existing.remove();

      appendCompletionCard(card, () => resetForm());
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireChatFab();
    wireFormModal();
    renderChat();
  });
})();
