# ElderCareMatters WordPress Theme — Setup Guide

## 1. Install the Theme

Copy the `eldercare-matters/` folder into your WordPress installation:

```
wp-content/themes/eldercare-matters/
```

In WP Admin → Appearance → Themes → Activate **ElderCareMatters**.

## 2. Copy Prototype Assets

From the prototype (`E:\Virtina-work\2026\AI\ECM\prototype\assets\`) copy into the theme:

```
prototype/assets/css/  →  wp-theme/eldercare-matters/assets/css/
prototype/assets/js/   →  wp-theme/eldercare-matters/assets/js/
prototype/assets/logo.png       →  wp-theme/eldercare-matters/assets/images/logo.png
prototype/assets/logo-icon.png  →  wp-theme/eldercare-matters/assets/images/logo-icon.png
```

**CSS files to copy:**
- `main.css`
- `homepagev3.css`
- `homepage-enhanced.css`
- `homepage-v2.css`
- `intake-v2.css`

**JS files to copy:**
- `data.js`
- `location.js`
- `intake.js`
- `main.js`

## 3. Set Homepage in WordPress

WP Admin → Settings → Reading:
- "Your homepage displays" → **A static page**
- Homepage: select your homepage page (e.g. "Home")

## 4. ACF Requirements

- **ACF PRO** must be installed and activated.
- Fields are registered programmatically — no JSON import needed.
- After activating the theme, go to the homepage in WP Admin → Edit.
- You will see a "Homepage Content" meta box with tabs for each section.

## 5. Populate ACF Fields

Navigate to: **Pages → Home → Edit**

Fill in each tab:

| Tab | What to fill |
|-----|-------------|
| Trust Bar | Banner text, show/hide toggle |
| Navigation | Logo upload, nav links repeater |
| Hero Section | Headline, subtitle, CTAs, badges, quick cats, hero image |
| Hero Floating Cards | Rating score/label/avatars, verified card, time card |
| Stats Bar | 4 stat repeater items (number + label) |
| Care Categories | 7 category cards (name, slug, icon, image, description, CTA) |
| How It Works | 3 steps (number, title, description) |
| Why ECM | 4 why-cards (icon, title, description) |
| Testimonials | 3 testimonials (stars, quote, name, location, avatar) |
| FAQ | 6 FAQ items (question, answer) + browse link |
| Provider CTA | Title, subtitle, 2 buttons, 3 stats |
| Footer | Logo, tagline, trust badges, nav columns, copyright |
| Modals & Chat | Advisor name, status, avatar emoji, Dotiq toggle |

## 6. Navigation Fallback

If ACF navigation repeater is empty, the theme renders a hard-coded fallback menu.
To use WordPress menus instead: WP Admin → Appearance → Menus → create a "Primary Navigation" menu.

## 7. Category Page Links

Care category cards link to `/category/?type={slug}`. Create a WordPress page
at slug `category` and add a template `page-category.php` to handle these routes,
or configure your rewrite rules accordingly.

## 8. Prototype Parity

Every section uses a **fallback** when ACF fields are empty — the fallback matches
the prototype exactly. This means the homepage renders correctly out-of-the-box
without filling in any ACF fields.
