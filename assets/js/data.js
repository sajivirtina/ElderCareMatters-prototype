(function () {
  'use strict';

  const CITIES = {
    'dallas':      { key: 'dallas',      name: 'Dallas',      state: 'TX', lat: 32.7767, lon: -96.7970, nearby: ['fort-worth', 'plano', 'arlington'], image: 'https://images.unsplash.com/photo-1545194445-dddb8f4487c6?w=1200&auto=format&fit=crop', tagline: 'The Dallas–Fort Worth Metroplex is home to 500+ licensed elder care providers.' },
    'fort-worth':  { key: 'fort-worth',  name: 'Fort Worth',  state: 'TX', lat: 32.7555, lon: -97.3308, nearby: ['dallas', 'arlington', 'plano'],      image: 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=1200&auto=format&fit=crop', tagline: 'Fort Worth blends Texas heritage with a growing network of senior care services.' },
    'plano':       { key: 'plano',       name: 'Plano',       state: 'TX', lat: 33.0198, lon: -96.6989, nearby: ['dallas', 'fort-worth', 'arlington'], image: 'https://images.unsplash.com/photo-1519331379826-f10be5486c6f?w=1200&auto=format&fit=crop', tagline: 'Plano is consistently ranked among the best Texas cities for senior living.' },
    'arlington':   { key: 'arlington',   name: 'Arlington',   state: 'TX', lat: 32.7357, lon: -97.1081, nearby: ['fort-worth', 'dallas', 'plano'],     image: 'https://images.unsplash.com/photo-1502005229762-cf1b2da7c5d6?w=1200&auto=format&fit=crop', tagline: 'Arlington sits at the heart of the Metroplex with dedicated memory care communities.' },
    'austin':      { key: 'austin',      name: 'Austin',      state: 'TX', lat: 30.2672, lon: -97.7431, nearby: ['round-rock', 'cedar-park'],          image: 'https://images.unsplash.com/photo-1531218150217-54595bc2b934?w=1200&auto=format&fit=crop', tagline: 'Austin\u2019s senior care scene has grown 40% in the last five years.' },
    'houston':     { key: 'houston',     name: 'Houston',     state: 'TX', lat: 29.7604, lon: -95.3698, nearby: ['sugar-land', 'katy'],                image: 'https://images.unsplash.com/photo-1572205796404-90d62d8d7d45?w=1200&auto=format&fit=crop', tagline: 'Houston has the largest concentration of hospice and palliative care providers in Texas.' }
  };

  const CATEGORIES = {
    'home-care':       { key: 'home-care',       name: 'Home Care',            icon: '🏠', blurb: 'Daily non-medical support delivered in the comfort of home — bathing, meals, medication reminders, companionship.', cross: ['care-management', 'memory-care', 'elder-law'] },
    'assisted-living': { key: 'assisted-living', name: 'Assisted Living',      icon: '🏡', blurb: 'Community living with on-site staff, meals, activities, and medical support.',                                       cross: ['memory-care', 'care-management', 'home-care'] },
    'memory-care':     { key: 'memory-care',     name: 'Memory Care',          icon: '🧠', blurb: 'Specialized support for Alzheimer\u2019s, dementia, and other memory conditions in a secure setting.',                  cross: ['assisted-living', 'home-care', 'hospice'] },
    'elder-law':       { key: 'elder-law',       name: 'Elder Law Attorney',   icon: '⚖️', blurb: 'Guardianship, estate planning, Medicaid, and power of attorney from locally licensed attorneys.',                     cross: ['care-management', 'home-care', 'hospice'] },
    'care-management': { key: 'care-management', name: 'Care Management',      icon: '📋', blurb: 'A dedicated care manager coordinates every service — one family point of contact for the whole plan.',                cross: ['home-care', 'elder-law', 'assisted-living'] },
    'hospice':         { key: 'hospice',         name: 'Hospice',              icon: '🤝', blurb: 'Compassionate end-of-life care at home or in a dedicated facility, with full family support.',                        cross: ['care-management', 'elder-law', 'memory-care'] }
  };

  // Reusable package templates per category so we don't repeat boilerplate for each provider.
  const PACKAGE_TEMPLATES = {
    'home-care': [
      { tier: 'basic',    name: 'Essential Care',       unit: '/hr', basePrice: 28, features: ['Up to 4 hrs/day', 'Meal prep', 'Medication reminders', 'Light housekeeping'] },
      { tier: 'standard', name: 'Comprehensive Care',   unit: '/hr', basePrice: 38, popular: true, features: ['Up to 12 hrs/day', 'Personal care (bathing, dressing)', 'Medication reminders', 'Meal prep & housekeeping', 'Transportation'] },
      { tier: 'premium',  name: 'Premium 24/7',         unit: '/hr', basePrice: 52, features: ['Round-the-clock care', 'Dedicated caregiver team', 'Medical coordination', 'Weekly family reports', 'Priority scheduling'] }
    ],
    'assisted-living': [
      { tier: 'basic',    name: 'Studio Suite',         unit: '/mo', basePrice: 3200, features: ['Private studio', 'Daily meals', 'Weekly housekeeping', 'Community activities'] },
      { tier: 'standard', name: 'One-Bedroom Care',     unit: '/mo', basePrice: 4500, popular: true, features: ['1-bedroom apartment', 'All meals + snacks', 'Personal care assistance', 'Transportation', 'Wellness programs'] },
      { tier: 'premium',  name: 'Deluxe Suite',         unit: '/mo', basePrice: 6100, features: ['Two-bedroom suite', 'Chef-prepared meals', '24/7 medical staff', 'Private transportation', 'Concierge service'] }
    ],
    'memory-care': [
      { tier: 'basic',    name: 'Day Program',          unit: '/mo', basePrice: 2400, features: ['5 days/week, 8 hrs/day', 'Cognitive activities', 'Lunch & snacks', 'Caregiver respite'] },
      { tier: 'standard', name: 'Residential Care',     unit: '/mo', basePrice: 5200, popular: true, features: ['24/7 secure residence', 'Specialized dementia staff', 'Personal care', 'Family support program'] },
      { tier: 'premium',  name: 'Premier Memory Suite', unit: '/mo', basePrice: 7400, features: ['Private suite', '1:3 caregiver ratio', 'Cognitive therapy program', 'Weekly family updates', 'On-site geriatric MD'] }
    ],
    'elder-law': [
      { tier: 'basic',    name: 'Consultation',         unit: 'flat', basePrice: 250, features: ['1-hour attorney consult', 'Document review', 'Written summary'] },
      { tier: 'standard', name: 'Estate Planning',      unit: 'flat', basePrice: 1800, popular: true, features: ['Will + trust preparation', 'Power of attorney', 'Healthcare directives', 'Two revision rounds'] },
      { tier: 'premium',  name: 'Full Guardianship',    unit: 'flat', basePrice: 4500, features: ['Court petition & representation', 'Guardianship setup', 'Estate plan', 'Annual compliance review'] }
    ],
    'care-management': [
      { tier: 'basic',    name: 'Care Assessment',      unit: 'flat', basePrice: 400, features: ['In-home assessment', 'Written care plan', '30-day follow-up'] },
      { tier: 'standard', name: 'Ongoing Management',   unit: '/mo', basePrice: 950, popular: true, features: ['Monthly visits', 'Provider coordination', 'Family progress reports', 'Emergency support line'] },
      { tier: 'premium',  name: 'Executive Care',       unit: '/mo', basePrice: 1800, features: ['Weekly visits', 'Full provider coordination', '24/7 family hotline', 'Quarterly medical review', 'Tax & financial liaison'] }
    ],
    'hospice': [
      { tier: 'basic',    name: 'Routine Home Care',    unit: 'covered', basePrice: 0, features: ['Nurse visits 2x/week', 'Aide support', 'Bereavement counseling', 'Medicare covered'] },
      { tier: 'standard', name: 'Continuous Care',      unit: 'covered', basePrice: 0, popular: true, features: ['Daily nurse visits', 'Aide support 5 days/week', 'Social worker', 'Chaplain', 'Medicare covered'] },
      { tier: 'premium',  name: 'Inpatient Facility',   unit: '/day',    basePrice: 320, features: ['Private room in facility', '24/7 nursing', 'Full family accommodations', 'Music & pet therapy', 'Concierge support'] }
    ]
  };

  // Build packages for a provider by taking templates and adjusting with a tier multiplier.
  function packagesFor(category, providerBoost) {
    const tmpl = PACKAGE_TEMPLATES[category] || [];
    return tmpl.map(pkg => ({
      tier: pkg.tier,
      name: pkg.name,
      unit: pkg.unit,
      popular: !!pkg.popular,
      price: pkg.basePrice ? Math.round(pkg.basePrice * (providerBoost || 1)) : 0,
      features: pkg.features.slice()
    }));
  }

  // Providers — compact definitions, packages generated from template.
  const PROVIDER_SEEDS = [
    // Home Care
    { id: 'sunrise-home-care',     name: 'Sunrise Home Care',           category: 'home-care',       city: 'dallas',     tier: 'featured', rating: 4.9, reviews: 128, boost: 1.00, color: '#C4933A', tagline: 'Compassionate in-home caregivers since 2008', specialties: ['Dementia care', 'Post-surgery', 'Companion care'], about: 'Sunrise has served DFW families for 17 years with fully vetted, bonded caregivers. Our care plans are built around your loved one\u2019s routine — not the other way around.' },
    { id: 'carefirst-dallas',      name: 'CareFirst Dallas',            category: 'home-care',       city: 'dallas',     tier: 'premium',  rating: 4.7, reviews: 92,  boost: 0.95, color: '#7A9E7E', tagline: 'Family-owned, locally run home care',                   specialties: ['Mobility support', 'Chronic illness'],                           about: 'A family-owned agency matching caregivers to clients by personality and care needs. Clear hourly rates, no long contracts.' },
    { id: 'lone-star-aides',       name: 'Lone Star Home Aides',        category: 'home-care',       city: 'fort-worth', tier: 'premium',  rating: 4.8, reviews: 74,  boost: 0.98, color: '#8B5CF6', tagline: 'Fort Worth\u2019s trusted caregiver network',           specialties: ['Overnight care', 'Diabetes management'],                         about: 'Covering all of Tarrant County with 40+ certified caregivers on staff. Same-day starts available.' },
    { id: 'comfortcare-plano',     name: 'ComfortCare Plano',           category: 'home-care',       city: 'plano',      tier: 'basic',    rating: 4.5, reviews: 41,  boost: 0.85, color: '#3B82F6', tagline: 'Hourly and live-in care in North Dallas',               specialties: ['Companion care', 'Meal preparation'],                            about: 'Plano\u2019s neighborhood home care agency. Simple pricing, flexible hours.' },

    // Assisted Living
    { id: 'lakewood-assisted',     name: 'Lakewood Assisted Living',    category: 'assisted-living', city: 'dallas',     tier: 'featured', rating: 4.8, reviews: 156, boost: 1.00, color: '#0EA5E9', tagline: 'Boutique community in East Dallas',                     specialties: ['Chef dining', 'Art programs'],                                   about: 'A 48-unit boutique community with private gardens and chef-led dining. Staff-to-resident ratio 1:4.' },
    { id: 'heritage-village-fw',   name: 'Heritage Village',            category: 'assisted-living', city: 'fort-worth', tier: 'premium',  rating: 4.7, reviews: 98,  boost: 0.95, color: '#F59E0B', tagline: 'Texas-style hospitality meets senior living',           specialties: ['Outdoor activities', 'Pet-friendly'],                            about: 'A full-service community with Texas-style warmth. On-site physical therapy, chapel, and dog park.' },
    { id: 'plano-senior-living',   name: 'Plano Senior Living',         category: 'assisted-living', city: 'plano',      tier: 'basic',    rating: 4.4, reviews: 52,  boost: 0.88, color: '#6366F1', tagline: 'Affordable assisted living in Collin County',            specialties: ['Budget-friendly', 'Social events'],                              about: 'A 60-unit community focused on affordability without sacrificing safety or care quality.' },

    // Memory Care
    { id: 'memory-haven-dallas',   name: 'Memory Haven Dallas',         category: 'memory-care',     city: 'dallas',     tier: 'featured', rating: 4.9, reviews: 87,  boost: 1.00, color: '#EC4899', tagline: 'Secure memory care with 1:3 staff ratio',               specialties: ['Dementia', 'Alzheimer\u2019s'],                                   about: 'Purpose-built memory care residence with sensory gardens and a specialty dementia program.' },
    { id: 'clear-days-arlington',  name: 'Clear Days Memory Care',      category: 'memory-care',     city: 'arlington',  tier: 'premium',  rating: 4.6, reviews: 63,  boost: 0.95, color: '#A855F7', tagline: 'A calmer day. A brighter tomorrow.',                    specialties: ['Early-stage dementia', 'Music therapy'],                         about: 'Programs designed around residents\u2019 remaining abilities, not their diagnoses. Family support groups included.' },

    // Elder Law
    { id: 'bradford-elder-law',    name: 'Bradford Elder Law',          category: 'elder-law',       city: 'dallas',     tier: 'featured', rating: 5.0, reviews: 54,  boost: 1.00, color: '#1E40AF', tagline: '30 years of Texas elder law practice',                 specialties: ['Medicaid planning', 'Guardianship', 'Estate'],                   about: 'Founded in 1995, Bradford has handled 1,200+ guardianship and Medicaid cases across Texas courts.' },
    { id: 'fw-elder-law-group',    name: 'Fort Worth Elder Law Group',  category: 'elder-law',       city: 'fort-worth', tier: 'premium',  rating: 4.8, reviews: 38,  boost: 0.95, color: '#0F766E', tagline: 'Estate planning & guardianship attorneys',              specialties: ['Estate planning', 'Probate'],                                    about: 'Small team of four attorneys focused exclusively on elder law. Personal attention on every case.' },

    // Care Management
    { id: 'guided-care-mgmt',      name: 'Guided Care Management',      category: 'care-management', city: 'dallas',     tier: 'featured', rating: 4.9, reviews: 66,  boost: 1.00, color: '#7A9E7E', tagline: 'One point of contact for everything',                   specialties: ['Complex care coordination'],                                     about: 'Board-certified geriatric care managers who coordinate every doctor, therapist, and caregiver so families can breathe.' },
    { id: 'plano-care-advisors',   name: 'Plano Care Advisors',         category: 'care-management', city: 'plano',      tier: 'premium',  rating: 4.7, reviews: 42,  boost: 0.95, color: '#D97706', tagline: 'Senior care navigation in North Dallas',                specialties: ['Transition planning', 'Family mediation'],                       about: 'We guide families through the toughest transitions — from hospital discharge to long-term care placement.' },

    // Hospice
    { id: 'compassion-hospice',    name: 'Compassion Hospice',          category: 'hospice',         city: 'dallas',     tier: 'featured', rating: 4.9, reviews: 109, boost: 1.00, color: '#7A9E7E', tagline: 'Medicare-certified hospice with music & pet therapy',   specialties: ['Pain management', 'Bereavement'],                                about: 'Full interdisciplinary team including chaplains, social workers, and volunteer musicians. 24/7 on-call nursing.' },
    { id: 'serenity-hospice',      name: 'Serenity Hospice TX',         category: 'hospice',         city: 'arlington',  tier: 'premium',  rating: 4.8, reviews: 81,  boost: 0.95, color: '#0891B2', tagline: 'Patient-first hospice serving Tarrant County',          specialties: ['Comfort care', 'Family counseling'],                             about: 'A non-profit hospice agency — every dollar goes back into patient and family support.' },
    { id: 'peaceful-transitions',  name: 'Peaceful Transitions',        category: 'hospice',         city: 'fort-worth', tier: 'basic',    rating: 4.5, reviews: 35,  boost: 0.90, color: '#9333EA', tagline: 'Compassionate end-of-life care at home',                 specialties: ['In-home hospice'],                                               about: 'Small hospice team serving Fort Worth families in the comfort of their own homes.' },

    // Free (unclaimed) listings
    { id: 'allcare-home-dallas',  name: 'AllCare Home Services',        category: 'home-care',       city: 'dallas',     tier: 'free',     rating: 3.9, reviews: 5,   boost: 0.80, color: '#A0A0A0', tagline: 'Home care services in the Dallas area.',               specialties: ['Companion care'],                                                about: 'Unclaimed listing.', claimed: false },
    { id: 'sunridge-assisted',    name: 'Sunridge Assisted Care',       category: 'assisted-living', city: 'dallas',     tier: 'free',     rating: 4.0, reviews: 8,   boost: 0.80, color: '#B0B0B0', tagline: 'Assisted living community in Dallas.',                 specialties: ['Daily assistance'],                                              about: 'Unclaimed listing.', claimed: false },
    { id: 'maplewood-memory',     name: 'Maplewood Memory Unit',        category: 'memory-care',     city: 'dallas',     tier: 'free',     rating: 3.8, reviews: 3,   boost: 0.80, color: '#C0C0C0', tagline: 'Memory care services in Dallas.',                      specialties: ['Dementia'],                                                      about: 'Unclaimed listing.', claimed: false }
  ];

  // Reusable review blurbs (recycled across providers for prototype data).
  const REVIEW_POOL = [
    { author: 'Jennifer L.', rating: 5, date: 'Mar 2026', text: 'Absolutely wonderful team. Within a day we had a caregiver lined up for my father post-surgery — they really listened.' },
    { author: 'Robert M.',   rating: 5, date: 'Feb 2026', text: 'They matched us with a caregiver who felt like family within a week. Highly recommend.' },
    { author: 'Anita G.',    rating: 4, date: 'Feb 2026', text: 'Professional team, clear pricing. We needed more help on short notice and they made it work.' },
    { author: 'Diego R.',    rating: 5, date: 'Jan 2026', text: 'The coordinator checked in every week. Made a stressful time so much easier for our family.' },
    { author: 'Sandra K.',   rating: 5, date: 'Jan 2026', text: 'Everyone we worked with was kind, patient, and deeply skilled. Mom felt safe, which is what mattered most.' }
  ];

  const PROVIDERS = PROVIDER_SEEDS.map((seed, idx) => ({
    ...seed,
    initials: seed.name.split(' ').slice(0, 2).map(w => w[0]).join(''),
    verified: true,
    response: seed.tier === 'featured' ? 'Responds within 1 hour' : (seed.tier === 'premium' ? 'Responds within 4 hours' : 'Responds within 24 hours'),
    packages: seed.tier === 'free' ? [] : packagesFor(seed.category, seed.boost),
    reviews_list: [REVIEW_POOL[idx % REVIEW_POOL.length], REVIEW_POOL[(idx + 2) % REVIEW_POOL.length]]
  }));

  // ── API ──
  const API = {
    cities: CITIES,
    categories: CATEGORIES,
    providers: PROVIDERS,

    normalizeCityKey(str) {
      return String(str || '').toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    },

    getCity(keyOrName) {
      if (!keyOrName) return null;
      const key = this.normalizeCityKey(keyOrName);
      return this.cities[key] || null;
    },

    getCategory(key) {
      return this.categories[key] || null;
    },

    getProvider(id) {
      return this.providers.find(p => p.id === id) || null;
    },

    // Providers in a category for a specific city, sorted: featured > premium > basic, then by rating.
    getProvidersByCategoryCity(categoryKey, cityKey) {
      const tierOrder = { featured: 0, premium: 1, basic: 2 };
      return this.providers
        .filter(p => p.category === categoryKey && (!cityKey || p.city === cityKey))
        .sort((a, b) => (tierOrder[a.tier] - tierOrder[b.tier]) || (b.rating - a.rating));
    },

    // Providers in nearby cities (for "nearby city packages" strip).
    getProvidersInNearbyCities(categoryKey, cityKey, limit) {
      const nearby = ((this.cities[cityKey] || {}).nearby) || [];
      const results = [];
      for (const n of nearby) {
        const matches = this.providers.filter(p => p.category === categoryKey && p.city === n);
        results.push(...matches);
      }
      return results.slice(0, limit || 6);
    },

    // Cross-sell: related categories for a given category.
    getCrossSell(categoryKey) {
      const cat = this.categories[categoryKey];
      if (!cat) return [];
      return (cat.cross || []).map(k => this.categories[k]).filter(Boolean);
    },

    // Free-text search across name + tagline + specialties + category + city.
    search(query, opts) {
      opts = opts || {};
      const q = String(query || '').toLowerCase().trim();
      return this.providers.filter(p => {
        if (opts.category && p.category !== opts.category) return false;
        if (opts.city && p.city !== opts.city) return false;
        if (!q) return true;
        const hay = [
          p.name, p.tagline, (p.specialties || []).join(' '),
          (this.categories[p.category] || {}).name || '',
          (this.cities[p.city] || {}).name || ''
        ].join(' ').toLowerCase();
        return hay.includes(q);
      });
    }
  };

  window.ECM_DATA = API;
})();
