(function () {
  'use strict';

  var CARE_TYPES = [
    { key: 'home-care',        icon: '🏠', label: 'Home Care' },
    { key: 'assisted-living',  icon: '🏡', label: 'Assisted Living' },
    { key: 'memory-care',      icon: '🧠', label: 'Memory Care' },
    { key: 'elder-law',        icon: '⚖️', label: 'Elder Law' },
    { key: 'care-management',  icon: '📋', label: 'Care Management' },
    { key: 'hospice',          icon: '🤝', label: 'Hospice' },
    { key: 'grief-counselors', icon: '💙', label: 'Grief Counselors' }
  ];

  var URGENCY_OPTIONS = [
    { key: 'immediately', label: 'Immediately' },
    { key: '2-3-days',    label: '2–3 days' },
    { key: '1-week',      label: 'Within a week' }
  ];

  var CROSS_SELL = {
    'home-care':        ['care-management', 'elder-law'],
    'assisted-living':  ['care-management', 'elder-law'],
    'memory-care':      ['home-care', 'hospice'],
    'elder-law':        ['home-care', 'care-management'],
    'care-management':  ['home-care', 'elder-law'],
    'hospice':          ['grief-counselors', 'care-management'],
    'grief-counselors': ['hospice', 'care-management']
  };

  var FORM_TOTAL = 7;

  // ── Shared utilities ────────────────────────────────────────────────────────

  function currentLocation() {
    return (window.ECM && window.ECM.getLocation && window.ECM.getLocation()) || { city: 'your area', state: '' };
  }

  function careTypeLabel(key) {
    var t = CARE_TYPES.find(function (c) { return c.key === key; });
    return t ? (t.icon + ' ' + t.label) : key;
  }

  function escHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function mockVerify(type) {
    return new Promise(function (resolve) {
      setTimeout(resolve, type === 'sms' ? 1500 : 1100);
    });
  }

  function mockSubmitLead() {
    return new Promise(function (resolve) {
      setTimeout(resolve, 700);
    });
  }

  function crossSellHtml(careKey) {
    var keys = (CROSS_SELL[careKey] || []).slice(0, 2);
    var items = keys.map(function (k) { return CARE_TYPES.find(function (c) { return c.key === k; }); }).filter(Boolean);
    if (!items.length) return '';
    var loc = currentLocation();
    return '<div class="crosssell-section">'
      + '<p class="crosssell-label">You may also need…</p>'
      + '<div class="crosssell-cards">'
      + items.map(function (c) {
          return '<a href="/category/?type=' + c.key
            + '&city=' + encodeURIComponent(loc.city)
            + '&state=' + encodeURIComponent(loc.state || '')
            + '" class="crosssell-card">' + c.icon + ' ' + escHtml(c.label) + '</a>';
        }).join('')
      + '</div></div>';
  }

  // ── CHAT ────────────────────────────────────────────────────────────────────

  // undefined = not yet reached; null = skipped; string = provided
  var chatState = {
    careType: null,
    firstName: '',
    lastName: '',
    confirmedLocation: false,
    urgency: null,
    email: '',
    emailVerified: false,
    phone: undefined,
    phoneVerified: false
  };

  function resetChatState() {
    chatState = {
      careType: null, firstName: '', lastName: '',
      confirmedLocation: false, urgency: null,
      email: '', emailVerified: false,
      phone: undefined, phoneVerified: false
    };
  }

  function chatStep() {
    if (!chatState.careType)                                   return 1;
    if (!chatState.firstName)                                  return 2;
    if (!chatState.confirmedLocation)                          return 3;
    if (!chatState.urgency)                                    return 4;
    if (!chatState.email || !chatState.emailVerified)          return 5;
    if (chatState.phone === undefined)                         return 6;
    return 7;
  }

  function renderChat() {
    var popup = document.getElementById('chatPopup');
    if (!popup) return;
    var badge = popup.querySelector('.chat-step-badge');
    var body  = popup.querySelector('.chat-body');
    if (!body) return;

    var step = chatStep();
    if (badge) badge.textContent = 'Step ' + step + ' of 7';
    body.innerHTML = '';

    // ── Step 1: Care type ────────────────────────────────────────────────────
    bubble(body, 'Hi! What kind of help are you looking for? Pick one so I can match you with the right local provider 💚');
    if (chatState.careType) {
      bubble(body, escHtml(careTypeLabel(chatState.careType)), true);
    } else {
      options(body, CARE_TYPES.map(function (c) { return { label: c.icon + ' ' + c.label, value: c.key }; }), function (v) {
        chatState.careType = v; renderChat();
      });
      scrollBottom(body); return;
    }

    // ── Step 2: Name ─────────────────────────────────────────────────────────
    bubble(body, 'Got it — <strong>' + escHtml(careTypeLabel(chatState.careType)) + '</strong>. What’s your name?');
    if (chatState.firstName) {
      bubble(body, escHtml(chatState.firstName + (chatState.lastName ? ' ' + chatState.lastName : '')), true);
    } else {
      var nameWrap = el('div', 'chat-name-wrap');
      var firstInp = inp('text', 'First name', 'given-name');
      var lastInp  = inp('text', 'Last name',  'family-name');
      var nameBtn  = btn('Next →', 'chat-name-submit chat-text-submit');
      nameBtn.addEventListener('click', function () {
        var first = firstInp.value.trim();
        if (!first) { firstInp.focus(); firstInp.classList.add('shake'); setTimeout(function () { firstInp.classList.remove('shake'); }, 300); return; }
        chatState.firstName = first;
        chatState.lastName  = lastInp.value.trim();
        renderChat();
      });
      [firstInp, lastInp].forEach(function (i) {
        i.addEventListener('keydown', function (e) { if (e.key === 'Enter') nameBtn.click(); });
      });
      nameWrap.appendChild(firstInp);
      nameWrap.appendChild(lastInp);
      nameWrap.appendChild(nameBtn);
      body.appendChild(nameWrap);
      requestAnimationFrame(function () { firstInp.focus(); });
      scrollBottom(body); return;
    }

    // ── Step 3: Location ─────────────────────────────────────────────────────
    var loc = currentLocation();
    var locStr = escHtml(loc.city) + (loc.state ? ', ' + escHtml(loc.state) : '');
    bubble(body, 'Hi ' + escHtml(chatState.firstName) + '! I see you’re in <strong>' + locStr + '</strong>. Is that where you need care?');
    if (chatState.confirmedLocation) {
      bubble(body, 'Yes, that’s right', true);
    } else {
      options(body, [
        { label: 'Yes, that’s right', value: 'yes' },
        { label: 'No, change location',   value: 'change' }
      ], function (v) {
        if (v === 'change') {
          var lm = document.getElementById('locationModal');
          if (lm) lm.classList.add('active');
          return;
        }
        chatState.confirmedLocation = true; renderChat();
      }, true);
      scrollBottom(body); return;
    }

    // ── Step 4: Urgency ──────────────────────────────────────────────────────
    bubble(body, 'How soon do you need help?');
    if (chatState.urgency) {
      var uLabel = (URGENCY_OPTIONS.find(function (u) { return u.key === chatState.urgency; }) || {}).label || '';
      bubble(body, escHtml(uLabel), true);
    } else {
      options(body, URGENCY_OPTIONS.map(function (u) { return { label: u.label, value: u.key }; }), function (v) {
        chatState.urgency = v; renderChat();
      }, true);
      scrollBottom(body); return;
    }

    // ── Step 5: Email ────────────────────────────────────────────────────────
    bubble(body, 'To connect you with providers, what’s your email? 🔐 Verified contacts get responses 3\xD7 faster.');
    if (chatState.email) {
      bubble(body, escHtml(chatState.email) + (chatState.emailVerified ? ' ✓' : ''), true);
      if (chatState.emailVerified) {
        bubble(body, '✅ Email verified! Providers can reach you faster.');
      } else {
        scrollBottom(body); return; // waiting on mock verify
      }
    } else {
      var emailWrap = el('div', 'chat-text-input-wrap');
      var emailRow  = el('div', 'chat-text-input-row');
      var emailInp  = inp('email', 'your@email.com', 'email');
      var emailBtn  = btn('Verify →', 'chat-text-submit');
      emailBtn.addEventListener('click', function () {
        var val = emailInp.value.trim();
        if (!val || val.indexOf('@') < 0) { emailInp.focus(); emailInp.classList.add('shake'); setTimeout(function () { emailInp.classList.remove('shake'); }, 300); return; }
        chatState.email = val;
        emailBtn.disabled = true; emailBtn.textContent = '⏳…';
        var loadBubble = bubble(body, '⏳ Verifying your email…');
        mockVerify('email').then(function () {
          chatState.emailVerified = true;
          loadBubble.textContent = '✅ Email verified!';
          renderChat();
        });
      });
      emailInp.addEventListener('keydown', function (e) { if (e.key === 'Enter') emailBtn.click(); });
      emailRow.appendChild(emailInp);
      emailRow.appendChild(emailBtn);
      emailWrap.appendChild(emailRow);
      body.appendChild(emailWrap);
      requestAnimationFrame(function () { emailInp.focus(); });
      scrollBottom(body); return;
    }

    // ── Step 6: Phone (optional) ─────────────────────────────────────────────
    bubble(body, 'Optional: add your phone for even faster provider responses via text. 📱');
    if (chatState.phone !== undefined) {
      if (chatState.phone) {
        bubble(body, escHtml(chatState.phone) + (chatState.phoneVerified ? ' ✓' : ''), true);
        if (chatState.phoneVerified) bubble(body, '📱 Phone verified — you’re fully verified!');
        else { scrollBottom(body); return; }
      } else {
        bubble(body, 'Skipped', true);
      }
    } else {
      var phoneWrap = el('div', 'chat-text-input-wrap');
      var phoneRow  = el('div', 'chat-text-input-row');
      var phoneInp  = inp('tel', '(555) 000-0000', 'tel');
      var phoneBtn  = btn('Verify →', 'chat-text-submit');
      var skipBtn   = btn('Skip →', 'chat-skip-btn');
      phoneBtn.addEventListener('click', function () {
        var val = phoneInp.value.trim();
        if (!val) { phoneInp.focus(); return; }
        chatState.phone = val;
        phoneBtn.disabled = true; phoneBtn.textContent = '⏳…';
        mockVerify('sms').then(function () {
          chatState.phoneVerified = true; renderChat();
        });
      });
      skipBtn.addEventListener('click', function () {
        chatState.phone = null; renderChat();
      });
      phoneRow.appendChild(phoneInp);
      phoneRow.appendChild(phoneBtn);
      phoneWrap.appendChild(phoneRow);
      phoneWrap.appendChild(skipBtn);
      body.appendChild(phoneWrap);
      requestAnimationFrame(function () { phoneInp.focus(); });
      scrollBottom(body); return;
    }

    // ── Step 7: Success ──────────────────────────────────────────────────────
    var verBadge = chatState.emailVerified
      ? (chatState.phoneVerified ? '⚡ Fully verified — fastest responses' : '✅ Email verified')
      : '';
    bubble(body, 'All set, ' + escHtml(chatState.firstName) + '! We’re connecting you with verified providers now. ' + verBadge);
    appendChatSuccess(body);
    scrollBottom(body);
  }

  function appendChatSuccess(container) {
    var loc = currentLocation();
    var urgLabel = (URGENCY_OPTIONS.find(function (u) { return u.key === chatState.urgency; }) || {}).label || '';
    var card = el('div', 'intake-complete');
    card.innerHTML = ''
      + '<div class="intake-complete-icon">✓</div>'
      + '<h4>Request submitted!</h4>'
      + '<div class="intake-summary">'
      + '<div><strong>Need:</strong> ' + escHtml(careTypeLabel(chatState.careType)) + '</div>'
      + '<div><strong>Location:</strong> ' + escHtml(loc.city) + (loc.state ? ', ' + escHtml(loc.state) : '') + '</div>'
      + '<div><strong>Urgency:</strong> ' + escHtml(urgLabel) + '</div>'
      + '<div><strong>Contact:</strong> ' + escHtml(chatState.email) + (chatState.phone ? ' · ' + escHtml(chatState.phone) : '') + '</div>'
      + '</div>'
      + '<p style="margin:0.75rem 0 0.5rem;font-size:0.85rem;color:var(--muted);">Providers will reach out within 24 hours.</p>'
      + crossSellHtml(chatState.careType)
      + '<button class="form-submit" type="button" data-chat-restart style="margin-top:14px;">New request</button>';
    container.appendChild(card);
    card.querySelector('[data-chat-restart]').addEventListener('click', function () {
      resetChatState(); renderChat();
    });
  }

  // chat DOM helpers
  function bubble(container, html, isUser) {
    var d = el('div', 'chat-bubble' + (isUser ? ' chat-bubble-user' : ''));
    d.innerHTML = html;
    container.appendChild(d);
    return d;
  }
  function options(container, list, onPick, row) {
    var wrap = el('div', row ? 'chat-option-row' : 'chat-options');
    list.forEach(function (o) {
      var b = el('button', 'chat-option');
      b.type = 'button'; b.textContent = o.label;
      b.addEventListener('click', function () { onPick(o.value); });
      wrap.appendChild(b);
    });
    container.appendChild(wrap);
  }
  function scrollBottom(body) {
    requestAnimationFrame(function () { body.scrollTop = body.scrollHeight; });
  }

  // ── FORM MODAL ───────────────────────────────────────────────────────────────

  var formStep = 1;
  var fState = {
    careType: null, firstName: '', lastName: '',
    urgency: null, email: '', emailVerified: false,
    phone: '', phoneVerified: false, phoneSkipped: false, description: ''
  };

  function resetFormState() {
    fState = {
      careType: null, firstName: '', lastName: '',
      urgency: null, email: '', emailVerified: false,
      phone: '', phoneVerified: false, phoneSkipped: false, description: ''
    };
    formStep = 1;
  }

  function renderFormStep(modal) {
    var content = modal.querySelector('#intakeStepContent');
    if (!content) return;

    // progress bar
    var fill  = modal.querySelector('#stepBarFill');
    var label = modal.querySelector('#stepLabel');
    if (fill)  fill.style.width = Math.round((formStep / FORM_TOTAL) * 100) + '%';
    if (label) label.textContent = 'Step ' + formStep + ' of ' + FORM_TOTAL;

    content.innerHTML = '';
    var loc = currentLocation();

    // ── Step 1: Care type ──────────────────────────────────────────────────
    if (formStep === 1) {
      var t1 = el('h4', 'step-title'); t1.textContent = 'What kind of help do you need?';
      content.appendChild(t1);
      var grid = el('div', 'care-type-grid');
      CARE_TYPES.forEach(function (c) {
        var b = el('button', 'care-type-btn' + (fState.careType === c.key ? ' selected' : ''));
        b.type = 'button';
        b.innerHTML = '<span class="care-type-icon">' + c.icon + '</span><span>' + escHtml(c.label) + '</span>';
        b.addEventListener('click', function () {
          fState.careType = c.key; formStep = 2; renderFormStep(modal);
        });
        grid.appendChild(b);
      });
      content.appendChild(grid);

    // ── Step 2: Name ───────────────────────────────────────────────────────
    } else if (formStep === 2) {
      addStepTitle(content, 'What’s your name?');
      var nameRow = el('div', 'name-field-row');
      var fInp = inp('text', 'First name', 'given-name'); fInp.id = 'sf-first'; fInp.value = fState.firstName;
      var lInp = inp('text', 'Last name',  'family-name'); lInp.id = 'sf-last';  lInp.value = fState.lastName;
      nameRow.appendChild(fInp);
      nameRow.appendChild(lInp);
      content.appendChild(nameRow);
      addStepNav(content, modal, function () {
        var first = content.querySelector('#sf-first').value.trim();
        if (!first) { content.querySelector('#sf-first').focus(); return; }
        fState.firstName = first;
        fState.lastName  = content.querySelector('#sf-last').value.trim();
        formStep = 3; renderFormStep(modal);
      });
      setTimeout(function () { var f = content.querySelector('#sf-first'); if (f) f.focus(); }, 30);

    // ── Step 3: Location ───────────────────────────────────────────────────
    } else if (formStep === 3) {
      addStepTitle(content, 'Where do you need care?');
      var locBox = el('div', 'location-confirm-box');
      locBox.innerHTML = '<span class="loc-pin">📍</span>'
        + '<span class="loc-text">' + escHtml(loc.city) + (loc.state ? ', ' + escHtml(loc.state) : '') + '</span>'
        + '<button type="button" class="location-change-link" data-open-location-modal>Change</button>';
      content.appendChild(locBox);
      var hint3 = el('p', 'step-hint');
      hint3.textContent = 'Your location was auto-detected. Change it if you’re searching for a parent in a different city.';
      content.appendChild(hint3);
      addStepNav(content, modal, function () { formStep = 4; renderFormStep(modal); });

    // ── Step 4: Urgency ────────────────────────────────────────────────────
    } else if (formStep === 4) {
      addStepTitle(content, 'How soon do you need help?');
      var chips = el('div', 'urgency-chips');
      URGENCY_OPTIONS.forEach(function (u) {
        var b = el('button', 'urgency-chip' + (fState.urgency === u.key ? ' selected' : ''));
        b.type = 'button'; b.textContent = u.label;
        b.setAttribute('data-value', u.key);
        b.addEventListener('click', function () {
          fState.urgency = u.key;
          chips.querySelectorAll('.urgency-chip').forEach(function (c) { c.classList.remove('selected'); });
          b.classList.add('selected');
        });
        chips.appendChild(b);
      });
      content.appendChild(chips);
      addStepNav(content, modal, function () {
        if (!fState.urgency) {
          chips.querySelectorAll('.urgency-chip').forEach(function (c) {
            c.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'translateX(0)' }], { duration: 240 });
          });
          return;
        }
        formStep = 5; renderFormStep(modal);
      });

    // ── Step 5: Email ──────────────────────────────────────────────────────
    } else if (formStep === 5) {
      addStepTitle(content, 'Your email address');
      var hint5 = el('div', 'verify-hint');
      hint5.innerHTML = '🔐 <strong>Get verified for faster responses</strong> — verified contacts hear from providers 3\xD7 faster.';
      content.appendChild(hint5);
      var emailInp5 = inp('email', 'your@email.com', 'email');
      emailInp5.value = fState.email;
      content.appendChild(emailInp5);
      var vs5 = el('div', 'verify-status' + (fState.emailVerified ? ' verified' : ''));
      if (fState.emailVerified) { vs5.textContent = '✅ Email verified'; vs5.style.display = ''; }
      else { vs5.style.display = 'none'; }
      content.appendChild(vs5);
      var nextBtn5 = btn(fState.emailVerified ? 'Next →' : 'Verify & Continue →', 'form-submit');
      content.appendChild(nextBtn5);
      nextBtn5.addEventListener('click', function () {
        var val = emailInp5.value.trim();
        if (!val || val.indexOf('@') < 0) { emailInp5.focus(); return; }
        fState.email = val;
        if (fState.emailVerified) { formStep = 6; renderFormStep(modal); return; }
        nextBtn5.disabled = true; nextBtn5.textContent = 'Verifying…';
        vs5.style.display = ''; vs5.className = 'verify-status loading'; vs5.textContent = '⏳ Verifying email…';
        mockVerify('email').then(function () {
          fState.emailVerified = true;
          vs5.className = 'verify-status verified'; vs5.textContent = '✅ Email verified!';
          nextBtn5.disabled = false; nextBtn5.textContent = 'Next →';
        });
      });
      addStepNav(content, modal, null); // back only
      setTimeout(function () { emailInp5.focus(); }, 30);

    // ── Step 6: Phone (optional) ───────────────────────────────────────────
    } else if (formStep === 6) {
      addStepTitle(content, 'Phone number (optional)');
      var hint6 = el('div', 'verify-hint');
      hint6.innerHTML = '📱 Add your phone and get verified for <strong>even faster</strong> provider responses via text.';
      content.appendChild(hint6);
      var phoneInp6 = inp('tel', '(555) 000-0000', 'tel');
      phoneInp6.value = fState.phone;
      content.appendChild(phoneInp6);
      var vs6 = el('div', 'verify-status' + (fState.phoneVerified ? ' verified' : ''));
      if (fState.phoneVerified) { vs6.textContent = '📱 Phone verified'; vs6.style.display = ''; }
      else { vs6.style.display = 'none'; }
      content.appendChild(vs6);
      var btnRow6 = el('div', 'form-btn-row');
      var verBtn6 = btn(fState.phoneVerified ? 'Next →' : 'Verify Phone →', 'form-submit');
      var skipBtn6 = btn('Skip →', 'btn-outline-skip');
      btnRow6.appendChild(verBtn6);
      btnRow6.appendChild(skipBtn6);
      content.appendChild(btnRow6);
      verBtn6.addEventListener('click', function () {
        if (fState.phoneVerified) { formStep = 7; renderFormStep(modal); return; }
        var val = phoneInp6.value.trim();
        if (!val) { phoneInp6.focus(); return; }
        fState.phone = val;
        verBtn6.disabled = true; verBtn6.textContent = 'Verifying…';
        vs6.style.display = ''; vs6.className = 'verify-status loading'; vs6.textContent = '⏳ Verifying phone…';
        mockVerify('sms').then(function () {
          fState.phoneVerified = true;
          vs6.className = 'verify-status verified'; vs6.textContent = '📱 Phone verified!';
          verBtn6.disabled = false; verBtn6.textContent = 'Next →';
        });
      });
      skipBtn6.addEventListener('click', function () {
        fState.phoneSkipped = true; formStep = 7; renderFormStep(modal);
      });
      addStepNav(content, modal, null); // back only

    // ── Step 7: Description + submit ───────────────────────────────────────
    } else if (formStep === 7) {
      addStepTitle(content, 'Anything else? (optional)');
      var ta7 = document.createElement('textarea');
      ta7.className = 'form-input'; ta7.rows = 3;
      ta7.placeholder = 'E.g. dementia patient, need overnight care, specific budget concerns…';
      ta7.value = fState.description;
      content.appendChild(ta7);
      // Verification summary badges
      var badges = [];
      if (fState.emailVerified)  badges.push('✅ Email verified');
      if (fState.phoneVerified)  badges.push('📱 Phone verified');
      if (badges.length) {
        var summ = el('div', 'submit-summary');
        summ.textContent = badges.join(' · ');
        content.appendChild(summ);
      }
      var submitBtn7 = btn('Submit Request →', 'form-submit');
      content.appendChild(submitBtn7);
      addStepNav(content, modal, null);
      submitBtn7.addEventListener('click', function () {
        fState.description = ta7.value.trim();
        submitBtn7.disabled = true; submitBtn7.textContent = 'Submitting…';
        mockSubmitLead().then(function () { showFormSuccess(modal); });
      });
    }
  }

  function addStepTitle(container, text) {
    var h = el('h4', 'step-title'); h.textContent = text;
    container.appendChild(h);
  }

  function addStepNav(container, modal, onNext) {
    var nav = el('div', 'step-nav');
    if (formStep > 1) {
      var back = btn('← Back', 'step-back');
      back.addEventListener('click', function () { formStep--; renderFormStep(modal); });
      nav.appendChild(back);
    }
    if (onNext) {
      var next = btn('Next →', 'form-submit step-next');
      next.addEventListener('click', onNext);
      nav.appendChild(next);
    }
    if (nav.children.length) container.appendChild(nav);
  }

  function showFormSuccess(modal) {
    var content = modal.querySelector('#intakeStepContent');
    var prog    = modal.querySelector('.intake-stepper-progress');
    if (prog) prog.style.display = 'none';
    var loc = currentLocation();
    var urgLabel = (URGENCY_OPTIONS.find(function (u) { return u.key === fState.urgency; }) || {}).label || '';
    content.innerHTML = '<div class="intake-complete">'
      + '<div class="intake-complete-icon">✓</div>'
      + '<h4>Request submitted, ' + escHtml(fState.firstName) + '!</h4>'
      + '<p>Verified providers in ' + escHtml(loc.city) + ' will reach out within 24 hours.</p>'
      + '<div class="intake-summary">'
      + '<div><strong>Need:</strong> ' + escHtml(careTypeLabel(fState.careType)) + '</div>'
      + '<div><strong>Location:</strong> ' + escHtml(loc.city) + (loc.state ? ', ' + escHtml(loc.state) : '') + '</div>'
      + '<div><strong>Urgency:</strong> ' + escHtml(urgLabel) + '</div>'
      + (fState.emailVerified ? '<div>✅ Email verified</div>' : '')
      + (fState.phoneVerified ? '<div>📱 Phone verified</div>' : '')
      + '</div>'
      + crossSellHtml(fState.careType)
      + '<button class="form-submit" type="button" id="formNewReq" style="margin-top:16px;">New request</button>'
      + '</div>';
    content.querySelector('#formNewReq').addEventListener('click', function () {
      resetFormState();
      if (prog) prog.style.display = '';
      renderFormStep(modal);
    });
  }

  // ── Shared DOM helpers ───────────────────────────────────────────────────────

  function el(tag, cls) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    return e;
  }
  function inp(type, placeholder, autocomplete) {
    var i = el('input', 'chat-text-input');
    i.type = type; i.placeholder = placeholder;
    if (autocomplete) i.autocomplete = autocomplete;
    return i;
  }
  function btn(text, cls) {
    var b = el('button', cls);
    b.type = 'button'; b.textContent = text;
    return b;
  }

  // ── Chat FAB wiring ──────────────────────────────────────────────────────────

  function wireChatFab() {
    var fab   = document.getElementById('chatFab');
    var popup = document.getElementById('chatPopup');
    if (!fab || !popup) return;

    function open()   { fab.classList.add('open'); popup.classList.add('open'); }
    function close()  { fab.classList.remove('open'); popup.classList.remove('open'); }
    function toggle() { if (popup.classList.contains('open')) close(); else open(); }

    fab.addEventListener('click', toggle);
    var closeBtn = popup.querySelector('[data-close-chat]');
    if (closeBtn) closeBtn.addEventListener('click', close);
    document.querySelectorAll('[data-open-chat]').forEach(function (b) {
      b.addEventListener('click', open);
    });

    // Re-render chat location step when location modal closes
    var lm = document.getElementById('locationModal');
    if (lm) {
      new MutationObserver(function () {
        if (!lm.classList.contains('active') && popup.classList.contains('open') && chatStep() === 3) {
          renderChat();
        }
      }).observe(lm, { attributes: true, attributeFilter: ['class'] });
    }
  }

  // ── Form modal wiring ────────────────────────────────────────────────────────

  function wireFormModal() {
    var modal = document.getElementById('formModal');
    if (!modal) return;

    function openModal() {
      resetFormState();
      modal.classList.add('active');
      var prog = modal.querySelector('.intake-stepper-progress');
      if (prog) prog.style.display = '';
      renderFormStep(modal);
    }
    function closeModal() { modal.classList.remove('active'); }

    document.querySelectorAll('[data-open-form-modal]').forEach(function (b) {
      b.addEventListener('click', openModal);
    });
    var closeBtn = modal.querySelector('[data-close-form-modal]');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    // Re-render location step when location modal closes while form is open
    var lm = document.getElementById('locationModal');
    if (lm) {
      new MutationObserver(function () {
        if (!lm.classList.contains('active') && modal.classList.contains('active') && formStep === 3) {
          renderFormStep(modal);
        }
      }).observe(lm, { attributes: true, attributeFilter: ['class'] });
    }
  }

  // ── Boot ─────────────────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    wireChatFab();
    wireFormModal();
    renderChat();
  });

})();
