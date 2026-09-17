<?php
/**
 * Care categories section.
 *
 * Priority order for category data:
 *   1. ACF "categories" repeater (admin can fully override per-category)
 *   2. Live job_listing_category taxonomy terms from the database
 *   3. Hardcoded fallback (only if DB has no published categories)
 */

$section_tag      = ecm_get_field( 'cats_section_tag',      'One need at a time' );
$section_title    = ecm_get_field( 'cats_section_title',    'What kind of help do you need in' );
$section_subtitle = ecm_get_field( 'cats_section_subtitle', "Select one category — we'll instantly show verified local providers and guide you to a free, no-obligation match." );
$verified_strip   = ecm_get_field( 'cats_verified_strip',   'All providers ECM Verified — credential-reviewed & locally licensed since 2002' );

// ── Category icon / description map (shared by live + fallback) ───────────────
$cat_meta_map = [
    'home-care'       => [ 'icon' => '🏠', 'img' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=800&auto=format&fit=crop', 'desc' => 'Daily support — bathing, meals, medication reminders — from verified caregivers in', 'cta' => 'See providers in' ],
    'assisted-living' => [ 'icon' => '🏡', 'img' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&auto=format&fit=crop', 'desc' => 'Community living with on-site staff for meals, activities, and medical support.', 'cta' => 'See providers in' ],
    'memory-care'     => [ 'icon' => '🧠', 'img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=800&auto=format&fit=crop', 'desc' => 'Specialized support for Alzheimer\'s, dementia, and other memory conditions.', 'cta' => 'See providers in' ],
    'elder-law'       => [ 'icon' => '⚖️', 'img' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&auto=format&fit=crop', 'desc' => 'Guardianship, estate planning, Medicaid, and power of attorney — attorneys licensed locally.', 'cta' => 'See attorneys in' ],
    'care-management' => [ 'icon' => '📋', 'img' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=800&auto=format&fit=crop', 'desc' => 'A care manager coordinates every service — one family point of contact.', 'cta' => 'See managers in' ],
    'hospice'         => [ 'icon' => '🤝', 'img' => 'https://images.unsplash.com/photo-1516574187841-cb9cc2ca948b?w=800&auto=format&fit=crop', 'desc' => 'Compassionate end-of-life care at home or in a dedicated facility, with family support.', 'cta' => 'See providers in' ],
    'grief-counselors'=> [ 'icon' => '💜', 'img' => 'https://images.unsplash.com/photo-1573497491765-dccce02b29df?w=800&auto=format&fit=crop', 'desc' => 'Professional grief and bereavement support for families and seniors navigating loss.', 'cta' => 'See counselors in' ],
];

/**
 * Best-match icon/img/desc for a term slug — checks slug keywords in order.
 */
function ecm_home_cat_meta( string $slug, array $map ) : array {
    // Exact match first.
    if ( isset( $map[ $slug ] ) ) return $map[ $slug ];
    // Partial match on slug keywords.
    foreach ( $map as $key => $val ) {
        if ( str_contains( $slug, $key ) || str_contains( $key, $slug ) ) return $val;
    }
    // Generic fallback.
    return [ 'icon' => '🏥', 'img' => '', 'desc' => 'Verified providers near you.', 'cta' => 'See providers in' ];
}

// ── 1. ACF repeater (admin override) ─────────────────────────────────────────
$acf_categories = get_field( 'categories' );

// ── 2. Live job_listing_category terms ───────────────────────────────────────
$live_categories = [];
if ( ! $acf_categories ) {
    $terms = get_terms( [
        'taxonomy'   => 'job_listing_category',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 8,
    ] );

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $meta = ecm_home_cat_meta( $term->slug, $cat_meta_map );
            $live_categories[] = [
                'name'        => $term->name,
                'slug'        => $term->slug,
                'icon'        => $meta['icon'],
                'image'       => [ 'url' => $meta['img'], 'alt' => $term->name ],
                'description' => $meta['desc'],
                'cta_text'    => $meta['cta'],
                'link_url'    => home_url( '/find-care/?category=' . $term->slug ),
            ];
        }
    }
}

// ── 3. Final hardcoded fallback (only if DB is empty) ────────────────────────
$hardcoded_categories = [];
if ( ! $acf_categories && empty( $live_categories ) ) {
    foreach ( $cat_meta_map as $slug => $meta ) {
        $hardcoded_categories[] = [
            'name'        => ucwords( str_replace( '-', ' ', $slug ) ),
            'slug'        => $slug,
            'icon'        => $meta['icon'],
            'image'       => [ 'url' => $meta['img'], 'alt' => ucwords( str_replace( '-', ' ', $slug ) ) ],
            'description' => $meta['desc'],
            'cta_text'    => $meta['cta'],
            'link_url'    => home_url( '/find-care/?category=' . $slug ),
        ];
    }
}

// Resolved category list.
$categories = $acf_categories ?: ( $live_categories ?: $hardcoded_categories );

// ── Pass live category slugs/names to JS (for intake form) ───────────────────
// Emit a small inline JSON blob so intake.js can use live terms instead of its
// own hardcoded CARE_TYPES array. Only emit once (guard with static flag).
static $ecm_cats_emitted = false;
if ( ! $ecm_cats_emitted ) {
    $ecm_cats_emitted = true;
    $js_cats = [];
    foreach ( $categories as $c ) {
        $js_cats[] = [
            'key'  => $c['slug'] ?? sanitize_title( $c['name'] ?? '' ),
            'icon' => $c['icon'] ?? '🏥',
            'label'=> $c['name'] ?? '',
        ];
    }
    echo '<script>window.ECM_CARE_TYPES=' . wp_json_encode( $js_cats ) . ';</script>' . "\n";
}
?>

<!-- Category cards -->
<section class="care-types" id="care-types">
    <div class="section-header reveal">
        <div class="care-types-header-main">
            <div class="section-tag"><?php echo esc_html( $section_tag ); ?></div>
            <h2 class="section-title">
                <?php echo esc_html( $section_title ); ?>
                <em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button>?
            </h2>
            <p class="section-sub"><?php echo esc_html( $section_subtitle ); ?></p>
            <div class="section-divider"></div>
        </div>
    </div>

    <div class="care-grid">
        <?php foreach ( $categories as $i => $cat ) :
            $name    = esc_html( $cat['name'] ?? '' );
            $slug    = sanitize_title( $cat['slug'] ?? $cat['name'] ?? '' );
            $icon    = $cat['icon'] ?? '';
            $img     = $cat['image'] ?? [];
            $img_url = $img['url'] ?? '';
            $img_alt = $img['alt'] ?? $name;
            $desc    = esc_html( $cat['description'] ?? '' );
            $cta_pfx = esc_html( $cat['cta_text'] ?? 'See providers in' );
            $link    = $cat['link_url'] ?? home_url( '/find-care/?category=' . $slug );
            $delay   = $i * 0.05;
        ?>
        <a class="care-card reveal js-category-card"
           data-category="<?php echo esc_attr( $slug ); ?>"
           href="<?php echo esc_url( $link ); ?>"
           <?php if ( $delay > 0 ) : ?>style="transition-delay:<?php echo esc_attr( $delay ); ?>s"<?php endif; ?>>
            <div class="care-card-img">
                <?php if ( $icon ) : ?>
                <span class="care-icon-chip"><?php echo esc_html( $icon ); ?></span>
                <?php endif; ?>
                <?php if ( $img_url ) : ?>
                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>">
                <?php endif; ?>
            </div>
            <div class="care-card-body">
                <div class="care-name"><?php echo $name; ?></div>
                <p class="care-desc"><?php echo $desc; ?></p>
                <span class="care-cta">
                    <?php echo $cta_pfx; ?> <span class="js-location-city">your area</span> →
                </span>
            </div>
        </a>
        <?php endforeach; ?>

        <?php
        $cta_kicker = ecm_get_field( 'cats_cta_kicker', 'Need help deciding?' );
        $cta_title  = ecm_get_field( 'cats_cta_title',  'Not sure which care type fits best?' );
        $cta_desc   = ecm_get_field( 'cats_cta_desc',   'Tell us what is happening, and we will guide you to the right kind of support in' );
        $cta_button = ecm_get_field( 'cats_cta_button', 'Start your free match' );
        ?>
        <div class="care-card care-card--cta reveal">
            <div class="care-card-body care-card-body--cta">
                <div class="care-cta-kicker"><?php echo esc_html( $cta_kicker ); ?></div>
                <div class="care-name care-name--cta"><?php echo esc_html( $cta_title ); ?></div>
                <p class="care-desc care-desc--cta"><?php echo esc_html( $cta_desc ); ?> <span class="js-location-city">your area</span>.</p>
                <button type="button" class="care-cta-button" data-open-form-modal><?php echo esc_html( $cta_button ); ?></button>
            </div>
        </div>
    </div><!-- /.care-grid -->

    <div class="verified-strip verified-strip--below">
        <span class="verified-strip-dot"></span>
        <?php echo esc_html( $verified_strip ); ?>
    </div>
</section>
