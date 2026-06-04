<?php
/**
 * Template Name: ECM — Search / Find Care
 *
 * Global provider search. The search bar dropdowns, category chips, results
 * grid, and result count are rendered by search.js from data.js.
 */

get_header();

$title_prefix = ecm_get_field( 'srch_title_prefix', 'Find the right care in' );
$sub          = ecm_get_field( 'srch_sub', 'Search verified elder care providers by need, city, or name.' );
$placeholder  = ecm_get_field( 'srch_placeholder', "Try 'memory care' or 'Sunrise'…" );
$cb_title     = ecm_get_field( 'srch_cb_title', "Can't find the right fit? <em>We'll match you.</em>" );
$cb_sub       = ecm_get_field( 'srch_cb_sub', "Tell us what you need, we'll hand-pick up to 3 verified providers." );
$cb_primary   = ecm_get_field( 'srch_cb_primary', '📋 Start Your Free Match →' );
$cb_secondary = ecm_get_field( 'srch_cb_secondary', '💬 Chat with advisor' );
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current">Find Care</span>
</div>

<!-- Search Hero -->
<section class="search-hero">
    <h1><?php echo esc_html( $title_prefix ); ?> <span style="white-space:nowrap"><em class="js-location-city">your area</em><button type="button" class="location-edit-pin" data-open-location-modal>📍</button></span></h1>
    <p><?php echo esc_html( $sub ); ?></p>
    <form class="search-bar" onsubmit="return false;" role="search">
        <input type="text" id="search-q" name="q" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
        <select id="search-category" name="category">
            <option value="">All categories</option>
        </select>
        <select id="search-city" name="city">
            <option value="">All cities</option>
        </select>
        <button type="submit" class="search-bar-btn" id="search-btn">Search</button>
    </form>
</section>

<!-- Quick Category Chips + Results -->
<section class="inner-section inner-section--warm" style="padding-top:28px">
    <div class="filter-row" id="category-chip-row"></div>

    <div class="search-meta">
        <div class="search-meta-count"><strong id="result-count">0</strong> providers matching your search</div>
        <div>
            <select id="sort-select" class="filter-chip" style="padding:6px 14px;background:var(--warm-white);appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%228%22 viewBox=%220 0 12 8%22%3E%3Cpath d=%22M1 1l5 5 5-5%22 stroke=%22%238A8A8A%22 stroke-width=%221.5%22 fill=%22none%22 stroke-linecap=%22round%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px center;padding-right:28px;cursor:pointer">
                <option value="tier">Sort: Recommended</option>
                <option value="rating">Sort: Highest rated</option>
                <option value="reviews">Sort: Most reviewed</option>
                <option value="price">Sort: Lowest price</option>
            </select>
        </div>
    </div>

    <div class="provider-grid" id="search-grid"></div>

    <div class="empty-state" id="empty-state" style="display:none">
        <div class="empty-state-icon">🔍</div>
        <h3>No providers match that search</h3>
        <p>Try a different category or let our care advisor help you find what you need.</p>
        <button type="button" class="btn-primary-lg" data-open-form-modal>📋 Request a match</button>
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
