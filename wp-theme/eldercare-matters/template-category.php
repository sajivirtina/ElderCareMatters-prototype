<?php
/**
 * Template Name: ECM — Category Page
 *
 * Location-specific category results page.
 * Hero, provider grid, nearby, and cross-sell are rendered by category.js
 * using the live REST API (/wp-json/ecm/v1/category).
 * The Guides section is pulled from real WordPress Posts.
 */

get_header();

// URL params — used server-side for the Guides WP_Query only.
$type_slug = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : 'home-care';

// ACF-editable strings with sensible defaults.
$cta_text      = ecm_get_field( 'catp_cta_text',        '📋 Find Providers' );
$chat_text     = ecm_get_field( 'catp_chat_text',        '💬 Talk to Carrie' );
$view_all_text = ecm_get_field( 'catp_view_all_text',    'View All Providers ↓' );
$meta_items    = get_field( 'catp_meta' );
$providers_sub = ecm_get_field( 'catp_providers_subtitle', 'Sorted by tier and rating. Click any card to see full details.' );
$guides_sub    = ecm_get_field( 'catp_guides_subtitle',  'Short, practical reads from our advisors and partner providers.' );
$cb_title      = ecm_get_field( 'catp_ctaband_title',    "Still deciding? <em>We'll guide you.</em>" );
$cb_sub        = ecm_get_field( 'catp_ctaband_sub',      'Our free care advisor will walk you through options in under 2 minutes.' );
$cb_primary    = ecm_get_field( 'catp_ctaband_primary',  '📋 Start Your Free Match →' );
$cb_secondary  = ecm_get_field( 'catp_ctaband_secondary','💬 Chat with advisor' );

// ── Real blog guides for this category ───────────────────────────────────────
// Try to match WP post category by slug, then fall back to any recent posts.
$guide_posts = [];
$cat_term = get_term_by( 'slug', $type_slug, 'category' );
if ( $cat_term && ! is_wp_error( $cat_term ) ) {
    $guide_q = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'cat'            => $cat_term->term_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
    $guide_posts = $guide_q->posts;
    wp_reset_postdata();
}
// Pad with recent posts from any category if fewer than 3.
if ( count( $guide_posts ) < 3 ) {
    $exclude_ids = array_map( fn( $p ) => $p->ID, $guide_posts );
    $pad_q = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3 - count( $guide_posts ),
        'post__not_in'   => $exclude_ids ?: [ 0 ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
    $guide_posts = array_merge( $guide_posts, $pad_q->posts );
    wp_reset_postdata();
}

// Helper: map blog post to guide card data.
function ecm_guide_card_data( WP_Post $post ) : array {
    $cats = get_the_terms( $post->ID, 'category' );
    $tag  = '';
    if ( $cats && ! is_wp_error( $cats ) ) {
        foreach ( $cats as $c ) {
            if ( 'uncategorized' !== $c->slug ) { $tag = $c->name; break; }
        }
    }
    $words    = str_word_count( wp_strip_all_tags( $post->post_content ) );
    $read_min = max( 1, (int) round( $words / 200 ) );
    $thumb    = get_the_post_thumbnail_url( $post->ID, 'medium_large' ) ?: 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=600&auto=format&fit=crop';
    $excerpt  = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '…' );
    return [
        'image'     => $thumb,
        'tag'       => $tag ?: 'Elder Care',
        'title'     => $post->post_title,
        'excerpt'   => $excerpt,
        'read_time' => $read_min . ' min read',
        'link'      => get_permalink( $post ),
    ];
}

$em_kses = [ 'em' => [] ];
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
    <span class="breadcrumb-sep">›</span>
    <a href="<?php echo esc_url( home_url( '/find-care/' ) ); ?>">Categories</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current" id="breadcrumb-current">Loading…</span>
</div>

<!-- Category Hero — text populated by category.js -->
<section class="inner-hero">
    <div class="inner-hero-text">
        <div class="inner-hero-eyebrow">
            <span class="inner-hero-eyebrow-icon" id="category-icon">🏥</span>
            <span id="category-name">Loading…</span>
        </div>
        <h1 class="inner-hero-title">Find <em id="category-inline">care</em> in
            <span style="white-space:nowrap">
                <em class="js-location-city">your area</em>
                <button type="button" class="location-edit-pin" data-open-location-modal>📍</button>
            </span>
        </h1>
        <p class="inner-hero-sub" id="category-blurb"></p>
        <div class="inner-hero-ctas">
            <button type="button" class="btn-primary-lg" id="hero-cta-btn" data-open-form-modal>
                <?php echo esc_html( $cta_text ); ?> <span class="btn-arrow">→</span>
            </button>
        </div>
        <div class="hero-text-links">
            <button type="button" class="hero-text-link" data-open-chat><?php echo esc_html( $chat_text ); ?></button>
            <span class="hero-text-link-sep">·</span>
            <a href="#all-providers-grid" class="hero-text-link"><?php echo esc_html( $view_all_text ); ?></a>
        </div>
        <div class="inner-hero-meta">
            <?php if ( $meta_items ) :
                foreach ( $meta_items as $m ) : ?>
                <div class="inner-hero-meta-item">
                    <span class="inner-hero-meta-icon"><?php echo esc_html( $m['icon'] ?? '' ); ?></span>
                    <?php echo esc_html( $m['text'] ?? '' ); ?>
                </div>
            <?php endforeach;
            else : ?>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">✓</span> <span id="hero-meta-count">Verified providers</span></div>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">🔒</span> Free for families</div>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">⭐</span> 4.9 avg rating</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="inner-hero-image">
        <img id="city-image" src="" alt="" style="min-height:220px;background:var(--sage-pale)">
        <div class="inner-hero-image-badge">
            <span class="dot"></span>
            <span><strong id="city-badge-name">Your City</strong> · <span id="city-badge-tagline"></span></span>
        </div>
    </div>
</section>

<!-- All Providers in [City] -->
<section class="inner-section inner-section--warm">
    <div class="inner-section-header">
        <div>
            <h2>All providers in <em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button></h2>
            <p><?php echo esc_html( $providers_sub ); ?></p>
        </div>
    </div>
    <div class="providers-toolbar">
        <input type="search" id="providers-search" class="providers-search-input"
               placeholder="Search by name or specialty…" autocomplete="off">
        <div class="sort-row">
            <span class="sort-label">Sort by</span>
            <select id="providers-sort" class="sort-select">
                <option value="recommended">Recommended</option>
                <option value="rating">Highest Rating</option>
                <option value="newest">Newest</option>
            </select>
        </div>
    </div>
    <div class="filter-row" id="subcategory-row" style="display:none"></div>
    <div class="filter-row" id="filter-row">
        <button class="filter-chip active" data-filter="all">All</button>
        <button class="filter-chip" data-filter="featured">⭐ Featured</button>
        <button class="filter-chip" data-filter="premium">Premium</button>
        <button class="filter-chip" data-filter="basic">Basic</button>
    </div>
    <div class="provider-grid" id="all-providers-grid">
        <!-- Skeleton loader shown until category.js populates -->
        <div class="provider-grid-loading" id="provider-skeleton">
            <?php for ( $i = 0; $i < 6; $i++ ) : ?>
            <div class="provider-card provider-card--skeleton">
                <div class="skeleton skeleton-logo"></div>
                <div class="skeleton-lines">
                    <div class="skeleton skeleton-line skeleton-line--wide"></div>
                    <div class="skeleton skeleton-line skeleton-line--mid"></div>
                    <div class="skeleton skeleton-line skeleton-line--short"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <div id="no-providers-msg" class="empty-state" style="display:none">
        <div class="empty-state-icon">🌱</div>
        <h3>We're still growing in this area</h3>
        <p>No direct matches in your city yet — the providers below in nearby cities also serve <span class="js-location-city">your area</span>.</p>
        <button type="button" class="btn-primary-lg" data-open-form-modal>📋 Request a match</button>
    </div>
</section>

<!-- Nearby providers (shown when city has 0 results) -->
<section class="inner-section inner-section--cream" id="nearby-section" style="display:none">
    <div class="inner-section-header">
        <div>
            <h2>Available in nearby cities</h2>
            <p>Many of these providers also serve families in <span class="js-location-city">your area</span>.</p>
        </div>
    </div>
    <div class="nearby-grid" id="nearby-grid"></div>
</section>

<!-- Cross-sell -->
<div class="cross-sell" id="cross-sell-section" style="display:none">
    <div class="cross-sell-header">
        <div class="cross-sell-eyebrow">Often needed together</div>
        <div class="cross-sell-title">Families arranging <em id="category-inline-3">care</em> often also need…</div>
    </div>
    <div class="cross-sell-grid" id="cross-sell-grid"></div>
</div>

<!-- Guides — real WP posts -->
<section class="inner-section inner-section--warm">
    <div class="inner-section-header">
        <div>
            <h2>Guides for families in <em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button></h2>
            <p><?php echo esc_html( $guides_sub ); ?></p>
        </div>
        <div class="inner-section-aside"><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">All guides →</a></div>
    </div>
    <div class="content-grid">
        <?php if ( $guide_posts ) :
            foreach ( $guide_posts as $gp ) :
                $g = ecm_guide_card_data( $gp );
        ?>
        <a class="content-card" href="<?php echo esc_url( $g['link'] ); ?>">
            <div class="content-card-img">
                <img src="<?php echo esc_url( $g['image'] ); ?>" alt="<?php echo esc_attr( $g['title'] ); ?>" loading="lazy">
            </div>
            <div class="content-card-body">
                <div class="content-tag"><?php echo esc_html( $g['tag'] ); ?></div>
                <div class="content-title"><?php echo esc_html( $g['title'] ); ?></div>
                <p class="content-excerpt"><?php echo esc_html( $g['excerpt'] ); ?></p>
                <div class="content-read-time"><?php echo esc_html( $g['read_time'] ); ?></div>
            </div>
        </a>
        <?php endforeach;
        else :
            // Static fallback if no WP posts exist yet.
            $fallback_guides = [
                [ 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=600&auto=format&fit=crop', 'Getting Started', 'How to choose the right level of elder care', 'A quick framework for deciding between home care, assisted living, and memory care.', '5 min read' ],
                [ 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&auto=format&fit=crop', 'Costs', 'What home care really costs in 2026', 'Hourly rates, insurance, and what Medicaid waivers do and don\'t cover.', '7 min read' ],
                [ 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&auto=format&fit=crop', 'Family', 'Talking to a parent about accepting help', 'Scripts and framing for the conversation no one wants to have.', '6 min read' ],
            ];
            foreach ( $fallback_guides as [ $img, $tag, $title, $excerpt, $rt ] ) : ?>
        <a class="content-card" href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">
            <div class="content-card-img"><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy"></div>
            <div class="content-card-body">
                <div class="content-tag"><?php echo esc_html( $tag ); ?></div>
                <div class="content-title"><?php echo esc_html( $title ); ?></div>
                <p class="content-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                <div class="content-read-time"><?php echo esc_html( $rt ); ?></div>
            </div>
        </a>
        <?php endforeach;
        endif; ?>
    </div>
</section>

<!-- CTA band -->
<div class="cta-band">
    <div>
        <h2><?php echo wp_kses( $cb_title, $em_kses ); ?></h2>
        <p><?php echo esc_html( $cb_sub ); ?></p>
    </div>
    <div class="cta-band-actions">
        <button type="button" class="btn-primary-lg" data-open-form-modal><?php echo esc_html( $cb_primary ); ?></button>
        <button type="button" class="btn-ghost" data-open-chat style="color:#fff;border-color:rgba(255,255,255,0.3);"><?php echo esc_html( $cb_secondary ); ?></button>
    </div>
</div>

<?php get_footer(); ?>
