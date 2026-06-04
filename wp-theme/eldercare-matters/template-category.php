<?php
/**
 * Template Name: ECM — Category Page
 *
 * Location-specific category results. The breadcrumb, hero title, category
 * name/icon/description, provider grid, nearby cities, and cross-sell are all
 * rendered by category.js (reads ?type= & ?city= from the URL + data.js).
 * Editable static content comes from ACF.
 */

get_header();

$cta_text     = ecm_get_field( 'catp_cta_text', '📋 Find Providers' );
$chat_text    = ecm_get_field( 'catp_chat_text', '💬 Talk to Carrie' );
$view_all_text = ecm_get_field( 'catp_view_all_text', 'View All Providers ↓' );
$meta_items   = get_field( 'catp_meta' );
$providers_sub = ecm_get_field( 'catp_providers_subtitle', 'Sorted by tier and rating. Click any card to see full details.' );
$guides_sub   = ecm_get_field( 'catp_guides_subtitle', 'Short, practical reads from our advisors and partner providers.' );
$guides       = get_field( 'catp_guides' );
$cb_title     = ecm_get_field( 'catp_ctaband_title', "Still deciding? <em>We'll guide you.</em>" );
$cb_sub       = ecm_get_field( 'catp_ctaband_sub', 'Our free care advisor will walk you through options in under 2 minutes.' );
$cb_primary   = ecm_get_field( 'catp_ctaband_primary', '📋 Start Your Free Match →' );
$cb_secondary = ecm_get_field( 'catp_ctaband_secondary', '💬 Chat with advisor' );
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
    <span class="breadcrumb-sep">›</span>
    <a href="<?php echo esc_url( home_url( '/find-care/' ) ); ?>">Categories</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current" id="breadcrumb-current">Loading…</span>
</div>

<!-- Category Hero -->
<section class="inner-hero">
    <div class="inner-hero-text">
        <div class="inner-hero-eyebrow">
            <span class="inner-hero-eyebrow-icon" id="category-icon">🏠</span>
            <span id="category-name">Category</span>
        </div>
        <h1 class="inner-hero-title">Find <em id="category-inline">home care</em> in <span style="white-space:nowrap"><em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button></span></h1>
        <p class="inner-hero-sub" id="category-blurb">Loading description…</p>
        <div class="inner-hero-ctas">
            <button type="button" class="btn-primary-lg" id="hero-cta-btn" data-open-form-modal>
                <?php echo esc_html( $cta_text ); ?>
                <span class="btn-arrow">→</span>
            </button>
        </div>
        <div class="hero-text-links">
            <button type="button" class="hero-text-link" data-open-chat><?php echo esc_html( $chat_text ); ?></button>
            <span class="hero-text-link-sep">·</span>
            <a href="#all-providers-grid" class="hero-text-link"><?php echo esc_html( $view_all_text ); ?></a>
        </div>
        <div class="inner-hero-meta">
            <?php if ( $meta_items ) : ?>
                <?php foreach ( $meta_items as $m ) : ?>
                <div class="inner-hero-meta-item">
                    <span class="inner-hero-meta-icon"><?php echo esc_html( $m['icon'] ?? '' ); ?></span>
                    <?php echo esc_html( $m['text'] ?? '' ); ?>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">✓</span> <span id="hero-meta-count">… providers verified</span></div>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">🔒</span> Free for families</div>
                <div class="inner-hero-meta-item"><span class="inner-hero-meta-icon">⭐</span> 4.9 avg rating</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="inner-hero-image">
        <img id="city-image" src="" alt="">
        <div class="inner-hero-image-badge">
            <span class="dot"></span>
            <span><strong id="city-badge-name">Dallas</strong> · <span id="city-badge-tagline">loading…</span></span>
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
        <input type="search" id="providers-search" class="providers-search-input" placeholder="Search by name or specialty…" autocomplete="off">
        <div class="sort-row">
            <span class="sort-label">Sort by</span>
            <select id="providers-sort" class="sort-select">
                <option value="recommended">Recommended</option>
                <option value="rating">Highest Rating</option>
                <option value="price">Lowest Price</option>
                <option value="distance">Nearest First</option>
            </select>
        </div>
    </div>
    <div class="filter-row" id="subcategory-row" style="display:none"></div>
    <div class="filter-row" id="filter-row">
        <button class="filter-chip active" data-filter="all">All</button>
        <button class="filter-chip" data-filter="featured">⭐ Featured</button>
        <button class="filter-chip" data-filter="premium">Premium</button>
        <button class="filter-chip" data-filter="basic">Basic</button>
        <button class="filter-chip filter-chip--free" data-filter="free">Free Listings</button>
    </div>
    <div class="provider-grid" id="all-providers-grid"></div>
    <div id="no-providers-msg" class="empty-state" style="display:none">
        <div class="empty-state-icon">🌱</div>
        <h3>We're still growing in this area</h3>
        <p>No direct matches in your city yet, but the providers below in nearby cities also serve <span class="js-location-city">your area</span>.</p>
        <button type="button" class="btn-primary-lg" data-open-form-modal>📋 Request a match</button>
    </div>
</section>

<!-- Nearby City Packages -->
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
<div class="cross-sell" id="cross-sell-section">
    <div class="cross-sell-header">
        <div class="cross-sell-eyebrow">Often needed together</div>
        <div class="cross-sell-title">Families arranging <em id="category-inline-3">home care</em> often also need…</div>
    </div>
    <div class="cross-sell-grid" id="cross-sell-grid"></div>
</div>

<!-- Guides / Content -->
<section class="inner-section inner-section--warm">
    <div class="inner-section-header">
        <div>
            <h2>Guides for families in <em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button></h2>
            <p><?php echo esc_html( $guides_sub ); ?></p>
        </div>
        <div class="inner-section-aside"><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">All guides →</a></div>
    </div>
    <div class="content-grid">
        <?php
        $guide_list = $guides;
        if ( ! $guide_list ) {
            $guide_list = [
                [ 'image' => [ 'url' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=600&auto=format&fit=crop' ], 'tag' => 'Getting Started', 'title' => 'How to choose the right level of elder care', 'excerpt' => 'A quick framework for deciding between home care, assisted living, and memory care.', 'read_time' => '5 min read', 'link' => '#' ],
                [ 'image' => [ 'url' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&auto=format&fit=crop' ], 'tag' => 'Costs', 'title' => 'What home care really costs in Texas (2026)', 'excerpt' => "Hourly rates, insurance, and what Medicaid waivers do and don't cover.", 'read_time' => '7 min read', 'link' => '#' ],
                [ 'image' => [ 'url' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&auto=format&fit=crop' ], 'tag' => 'Family', 'title' => 'Talking to a parent about accepting help', 'excerpt' => 'Scripts and framing for the conversation no one wants to have, from care managers who do it daily.', 'read_time' => '6 min read', 'link' => '#' ],
            ];
        }
        foreach ( $guide_list as $g ) :
            $g_img = $g['image']['url'] ?? '';
            $g_link = $g['link'] ?: '#';
        ?>
        <a class="content-card" href="<?php echo esc_url( $g_link ); ?>">
            <div class="content-card-img"><img src="<?php echo esc_url( $g_img ); ?>" alt=""></div>
            <div class="content-card-body">
                <div class="content-tag"><?php echo esc_html( $g['tag'] ?? '' ); ?></div>
                <div class="content-title"><?php echo esc_html( $g['title'] ?? '' ); ?></div>
                <p class="content-excerpt"><?php echo esc_html( $g['excerpt'] ?? '' ); ?></p>
                <div class="content-read-time"><?php echo esc_html( $g['read_time'] ?? '' ); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA band -->
<div class="cta-band">
    <div>
        <h2><?php echo wp_kses( $cb_title, [ 'em' => [] ] ); ?></h2>
        <p><?php echo esc_html( $cb_sub ); ?></p>
    </div>
    <div class="cta-band-actions">
        <button type="button" class="btn-primary-lg" data-open-form-modal><?php echo esc_html( $cb_primary ); ?></button>
        <button type="button" class="btn-ghost" data-open-chat style="color:#fff;border-color:rgba(255,255,255,0.3);"><?php echo esc_html( $cb_secondary ); ?></button>
    </div>
</div>

<?php get_footer(); ?>
