<?php
/**
 * ElderCareMatters Theme Functions
 *
 * Sets up theme, enqueues assets, loads ACF field definitions.
 */

defined( 'ABSPATH' ) || exit;

/**
 * TEMPORARY — package/tier debug panel toggle. Shows a small floating panel
 * on Add/Edit Listing (dashboard) and the single listing page, dumping raw
 * post meta + what the package-tier system detected from it — added to
 * diagnose why field gating isn't matching a listing's actual package.
 * Flip to false (or delete this block + the panel calls in listings.php /
 * single-job_listing.php) once package detection is confirmed working.
 */
define( 'ECM_PACKAGE_DEBUG', true );

// ── Theme Setup ───────────────────────────────────────────────────────────────

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ] );
    // Enables WP Job Manager front-end templates + makes job_listing taxonomies publicly queryable
    // (required for the job_listing_category archive pages to render).
    add_theme_support( 'job-manager-templates' );
    add_theme_support( 'custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    register_nav_menus( [
        'primary' => __( 'Primary Navigation', 'eldercare-matters' ),
        'footer'  => __( 'Footer Navigation', 'eldercare-matters' ),
    ] );
} );

// Show six rows of four posts on desktop blog listings.
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() || $query->is_feed() ) {
        return;
    }

    if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() ) {
        $query->set( 'posts_per_page', 24 );
        $query->set( 'orderby', [ 'date' => 'DESC', 'ID' => 'DESC' ] );
        $query->set( 'ignore_sticky_posts', true );
    }
} );

// ── Enqueue Styles & Scripts ──────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
    $v  = wp_get_theme()->get( 'Version' );
    $tu = get_template_directory_uri();
    $data_version = filemtime( get_template_directory() . '/assets/js/data.js' );
    $category_version = filemtime( get_template_directory() . '/assets/js/category.js' );
    $search_version = filemtime( get_template_directory() . '/assets/js/search.js' );
    // inner.css is shared across several page types below and gets edited
    // often (review UI, search loading state, etc.) — filemtime like the
    // JS files above, not the static theme $v, so a change here can't get
    // stuck behind a stale cached copy at the same versioned URL.
    $inner_version = filemtime( get_template_directory() . '/assets/css/inner.css' );

    // Google Fonts
    wp_enqueue_style(
        'ecm-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap',
        [],
        null
    );

    // Prototype CSS (global)
    wp_enqueue_style( 'ecm-main',      $tu . '/assets/css/main.css',      [ 'ecm-google-fonts' ], $v );
    wp_enqueue_style( 'ecm-homepage',  $tu . '/assets/css/homepagev3.css',  [ 'ecm-main' ], $v );
    wp_enqueue_style( 'ecm-intake-v2', $tu . '/assets/css/intake-v2.css', [ 'ecm-homepage' ], $v );

    // Inner-page CSS — loaded on category/search/blog/blog-detail templates, single posts, and Job Manager category archive
    // Detect /category/?type=X — template_redirect bypasses is_page_template()
    $is_category_page = isset( $_GET['type'] ) &&
        'category' === trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );

    if ( is_page_template( [ 'template-category.php', 'template-search.php', 'template-blog.php', 'template-blog-detail.php' ] )
        || $is_category_page
        || is_singular( 'post' )
        || is_singular( 'job_listing' )
        || is_tax( 'job_listing_category' )
        || is_page() 
        || ( function_exists( 'is_shop' ) && is_shop() ) ) { // Standard Pages + WooCommerce shop archive
        wp_enqueue_style( 'ecm-inner', $tu . '/assets/css/inner.css', [ 'ecm-homepage' ], $inner_version );
    }

    if ( is_404() ) {
        wp_enqueue_style( 'ecm-404', $tu . '/assets/css/404.css', [ 'ecm-homepage' ], filemtime( get_template_directory() . '/assets/css/404.css' ) );
    }

    // Blog listings styled with the current theme's design tokens.
    if ( is_page_template( 'template-blog.php' ) || is_home() || is_category()
        || is_tag() || is_author() || is_post_type_archive( 'provider_spotlight' ) ) {
        wp_enqueue_style( 'ecm-inner', $tu . '/assets/css/inner.css', [ 'ecm-homepage' ], $inner_version );
        wp_enqueue_style(
            'ecm-blog-listing',
            $tu . '/assets/css/blog-listing.css',
            [ 'ecm-inner' ],
            filemtime( get_template_directory() . '/assets/css/blog-listing.css' )
        );
    }


    // Single listing page — reuses inner.css (breadcrumb, article header/body)
    // plus provider-pages.css (badges, contact/coverage components).
    if ( is_singular( 'job_listing' ) ) {
        wp_enqueue_style( 'ecm-inner', $tu . '/assets/css/inner.css', [ 'ecm-homepage' ], $inner_version );
        wp_enqueue_style( 'ecm-provider-pages', $tu . '/assets/css/provider-pages.css', [ 'ecm-inner' ], $v );
    }

    // For-providers CSS — only on that template
    if ( is_page_template( 'template-for-providers.php' ) ) {
        wp_enqueue_style( 'ecm-for-providers', $tu . '/assets/css/for-providers.css', [ 'ecm-homepage' ], $v );
    }

    // Plans & Pricing — reuses inner.css (breadcrumb) + provider-pages.css
    // (.plan-card, .plans-table, .plans-faq components).
    if ( is_page_template( 'template-plans-pricing.php' ) ) {
        wp_enqueue_style( 'ecm-inner', $tu . '/assets/css/inner.css', [ 'ecm-homepage' ], $inner_version );
        wp_enqueue_style( 'ecm-provider-pages', $tu . '/assets/css/provider-pages.css', [ 'ecm-inner' ], $v );
    }

    // Core scripts — load in footer, everywhere
    wp_enqueue_script( 'ecm-data',     $tu . '/assets/js/data.js',     [],           $data_version, true );
    wp_localize_script( 'ecm-data', 'ECM_CONFIG', [
        'gsv_key' => defined( 'ECM_GOOGLE_MAPS_KEY' ) ? ECM_GOOGLE_MAPS_KEY : get_option( 'ecm_google_maps_key', '' ),
    ] );
    wp_enqueue_script( 'ecm-location', $tu . '/assets/js/location.js', [ 'ecm-data' ], $v, true );
    wp_enqueue_script( 'ecm-intake',   $tu . '/assets/js/intake.js',   [ 'ecm-location' ], $v, true );
    wp_enqueue_script( 'ecm-main',     $tu . '/assets/js/main.js',     [ 'ecm-intake' ], $v, true );

    // Category page logic
    if ( is_page_template( 'template-category.php' ) || $is_category_page ) {
        wp_enqueue_script( 'ecm-category', $tu . '/assets/js/category.js', [ 'ecm-main' ], $category_version, true );
        wp_localize_script( 'ecm-category', 'ECM_SEARCH_API', [
            'root' => esc_url_raw( rest_url( 'ecm/v1' ) ),
            'home' => esc_url_raw( home_url( '/' ) ),
        ] );
    }
    // Search page logic
    if ( is_page_template( 'template-search.php' ) ) {
        wp_enqueue_script( 'ecm-search', $tu . '/assets/js/search.js', [ 'ecm-main' ], $search_version, true );
        wp_localize_script( 'ecm-search', 'ECM_SEARCH_API', [
            'root' => esc_url_raw( rest_url( 'ecm/v1' ) ),
            'home' => esc_url_raw( home_url( '/' ) ),
        ] );
    }

    // Single listing page — track "Request a match" / "Chat with advisor"
    // clicks via WP Job Manager Stats' existing AJAX endpoint (see the
    // script's own doc comment for why: this template's real contact
    // buttons aren't the WPJM Apply button WPJM Stats listens for).
    if ( is_singular( 'job_listing' ) ) {
        wp_enqueue_script( 'ecm-contact-tracking', $tu . '/assets/js/contact-tracking.js', [ 'wp-util', 'ecm-intake' ], $v, true );
    }
} );

// ── ACF Field Groups ──────────────────────────────────────────────────────────

$inc = get_template_directory() . '/inc';

if ( function_exists( 'acf_add_local_field_group' ) ) {
    require_once $inc . '/acf-fields.php';        // Homepage
    require_once $inc . '/acf-category.php';       // Category page
    require_once $inc . '/acf-search.php';         // Search page
    require_once $inc . '/acf-blog.php';           // Blog listing
    require_once $inc . '/acf-blog-detail.php';    // Blog detail
    require_once $inc . '/acf-for-providers.php';  // For providers
    require_once $inc . '/acf-plans-pricing.php';  // Plans & pricing
    // acf-provider-listing-fields.php intentionally NOT loaded — superseded by
    // the native WPJM fields (inc/wpjm-native-fields.php + wpjm-native-repeater-fields.php).
    // Confirmed OK to remove: staging only, no live data on the old ACF fields.
}
require_once $inc . '/package-capabilities.php'; // Package tier config — needed outside the ACF guard too
require_once $inc . '/listing-articles.php'; // Provider-authored articles linked to a listing (real posts, not ACF rows)
require_once $inc . '/wpjm-native-fields.php'; // Tier limits applied to WPJM/Field Editor's own native fields (gallery, description)
require_once $inc . '/wpjm-native-repeater-fields.php'; // FAQ/testimonials/team/credentials/cities as native WPJM repeater fields
require_once $inc . '/wpjm-admin-native-fields.php'; // Same native fields, visible/editable on the wp-admin job_listing edit screen too
require_once $inc . '/wpjm-manual-field-save.php'; // Direct save fallback — WPJM's own generic save loop was confirmed (via debug panel) to silently skip these fields despite correct $_POST data
require_once $inc . '/job-listing-reviews.php'; // Review submission form (WP Job Manager Reviews add-on integration) for single-job_listing.php

// One-time homepage content migration — seeds ACF values onto the front page.
// Idempotent + self-guarding (option flag); safe to keep loaded in production.
require_once get_template_directory() . '/inc/migrate-homepage-content.php';

// Provider Dashboard — custom modules over Job Manager + WooCommerce (read-only).
require_once get_template_directory() . '/inc/provider-dashboard.php';

/**
 * TinyMCE needs its assets enqueued early on the Edit Listing screen for
 * the Article content editor (Profile Details → Your Articles) to render.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( pd_is_listing_edit_request() ) {
        wp_enqueue_editor();
    }
} );

// Find Care search REST API — serves real WP Job Manager data to the search page.
require_once get_template_directory() . '/inc/search-api.php';
// Intake form REST API — receives popup form submissions, saves leads, emails admin.
require_once get_template_directory() . '/inc/intake-api.php';

// Intake form REST API — receives popup form submissions, saves leads, emails admin.
require_once get_template_directory() . '/inc/intake-api.php';

// ── ACF Options: make get_field() work for non-post contexts ─────────────────

add_action( 'acf/init', function () {
    // Uncomment if you want global Options Page for site-wide settings
    // acf_add_options_page( [ 'page_title' => 'Site Settings', 'menu_slug' => 'ecm-settings' ] );
} );

// ── Helper: safe field output ─────────────────────────────────────────────────

/**
 * Echo an ACF field with esc_html, or echo $fallback.
 */
function ecm_field( string $key, string $fallback = '', $post_id = false ): void {
    $val = get_field( $key, $post_id );
    echo $val ? esc_html( $val ) : esc_html( $fallback );
}

/**
 * Return an ACF field, or $fallback.
 */
function ecm_get_field( string $key, string $fallback = '', $post_id = false ): string {
    $val = get_field( $key, $post_id );
    return $val ?: $fallback;
}

/**
 * Output an ACF image field as <img>.
 * $field_key: ACF image field (returns array with url, alt, etc.)
 * $fallback_url: URL to use if field is empty
 */
function ecm_img( string $key, string $fallback_url = '', string $fallback_alt = '', string $class = '', $post_id = false ): void {
    $img = get_field( $key, $post_id );
    $url = $img['url'] ?? $fallback_url;
    $alt = $img['alt'] ?? $fallback_alt;
    printf(
        '<img src="%s" alt="%s"%s>',
        esc_url( $url ),
        esc_attr( $alt ),
        $class ? ' class="' . esc_attr( $class ) . '"' : ''
    );
}

// ── Find Care page auto-create ────────────────────────────────────────────────
// Creates /find-care/ with the ECM Search template if it doesn't exist.
add_action( 'init', function () {
    if ( get_transient( 'ecm_findcare_page_ok' ) ) return;

    $existing = get_page_by_path( 'find-care', OBJECT, 'page' );
    if ( $existing && 'publish' === $existing->post_status ) {
        // Make sure the template is set correctly.
        if ( get_page_template_slug( $existing->ID ) !== 'template-search.php' ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'template-search.php' );
        }
        set_transient( 'ecm_findcare_page_ok', 1, WEEK_IN_SECONDS );
        return;
    }

    $new_id = wp_insert_post( [
        'post_title'   => 'Find Care',
        'post_name'    => 'find-care',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => 1,
    ] );

    if ( $new_id && ! is_wp_error( $new_id ) ) {
        update_post_meta( $new_id, '_wp_page_template', 'template-search.php' );
        flush_rewrite_rules( false );
        set_transient( 'ecm_findcare_page_ok', 1, WEEK_IN_SECONDS );
    }
}, 20 );

// ── Resources page auto-create ────────────────────────────────────────────────
// Creates /resources/ with the ECM Blog template if it doesn't exist.
add_action( 'init', function () {
    if ( get_transient( 'ecm_resources_page_ok' ) ) return;

    $existing = get_page_by_path( 'resources', OBJECT, 'page' );
    if ( $existing && 'publish' === $existing->post_status ) {
        if ( get_page_template_slug( $existing->ID ) !== 'template-blog.php' ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'template-blog.php' );
        }
        set_transient( 'ecm_resources_page_ok', 1, WEEK_IN_SECONDS );
        return;
    }

    $new_id = wp_insert_post( [
        'post_title'   => 'Resources & Guides',
        'post_name'    => 'resources',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => 1,
    ] );

    if ( $new_id && ! is_wp_error( $new_id ) ) {
        update_post_meta( $new_id, '_wp_page_template', 'template-blog.php' );
        flush_rewrite_rules( false );
        set_transient( 'ecm_resources_page_ok', 1, WEEK_IN_SECONDS );
    }
}, 20 );

// ── Provider Dashboard ────────────────────────────────────────────────────────

// Stop WordPress old-slug redirect hijacking /provider-dashboard/ after page rename.
add_filter( 'old_slug_redirect_url', function( $link ) {
    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/provider-dashboard' ) !== false ) {
        return false;
    }
    return $link;
} );

add_filter( 'redirect_canonical', function( $redirect_url, $requested_url ) {
    if ( strpos( $requested_url, '/provider-dashboard' ) !== false ) {
        return false;
    }
    return $redirect_url;
}, 1, 2 );

// Load provider dashboard — guarded so it can only load once.
if ( ! defined( 'PD_REWRITE_VERSION' ) ) {
    require_once get_theme_file_path( 'inc/provider-dashboard.php' );
}

// ONE-TIME: create physical /srv/htdocs/provider-dashboard/index.php so nginx
// can serve it directly without redirect-looping. This entry point boots WP
// core via wp-load.php, then runs wp() + template-loader.php so rewrite rules
// still set pd_section and template_include still loads the controller —
// same effect as wp-blog-header.php, built from files that actually exist
// on this server.
//
// Guarded by a transient (like the other auto-create hooks below) so this
// only writes to disk once instead of on every single request.
add_action( 'init', function () {
    if ( get_transient( 'ecm_pd_entry_file_ok' ) ) return;

    $pd_dir  = '/srv/htdocs/provider-dashboard';
    $pd_file = $pd_dir . '/index.php';

    if ( ! is_dir( $pd_dir ) ) {
        mkdir( $pd_dir, 0755, true );
    }

    $lines = array(
        '<?php',
        '/** Provider Dashboard entry point.',
        ' * nginx serves this file directly, so we boot WP manually:',
        ' * wp-load.php boots core, wp() runs routing/rewrite rules,',
        ' * template-loader.php lets template_include load the controller.',
        ' */',
        'define( "WP_USE_THEMES", true );',
        'require_once "/srv/htdocs/wp-load.php";',
        'wp();',
        'require_once ABSPATH . WPINC . \'/template-loader.php\';',
    );
    file_put_contents( $pd_file, implode( "\n", $lines ) . "\n" );

    set_transient( 'ecm_pd_entry_file_ok', 1, WEEK_IN_SECONDS );
}, 1 );

// ── WooCommerce My Account page auto-create (local dev helper) ────────────────
add_action( 'init', function () {

    if ( get_transient( 'ecm_myaccount_page_ok' ) ) return;
    if ( ! function_exists( 'wc_get_page_id' ) ) return;

    $page_id = (int) get_option( 'woocommerce_myaccount_page_id' );
    if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
        set_transient( 'ecm_myaccount_page_ok', 1, WEEK_IN_SECONDS );
        return;
    }

    // Page missing or unpublished — create it
    $new_id = wp_insert_post( [
        'post_title'   => 'My Account',
        'post_name'    => 'myaccount',
        'post_content' => '[woocommerce_my_account]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => 1,
    ] );

    if ( $new_id && ! is_wp_error( $new_id ) ) {
        update_option( 'woocommerce_myaccount_page_id', $new_id );
        flush_rewrite_rules( false );
        set_transient( 'ecm_myaccount_page_ok', 1, WEEK_IN_SECONDS );
    }
}, 20 );

// ── Category page: intercept /category/?type=X ───────────────────────────────
// WordPress reserves /category/ as its taxonomy archive base, so a WP Page
// with slug "category" cannot exist. Instead we hook template_redirect early:
// when the path is /category/ AND ?type= is present we serve our template
// directly, bypassing the "nothing found" archive fallback.
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['type'] ) ) return;
    $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
    if ( 'category' !== $path ) return;
    status_header( 200 );
    include get_template_directory() . '/template-category.php';
    exit;
}, 1 );

// ── WooCommerce shop page hero ────────────────────────────────────────────────
// Inject the .ecm-shop-hero banner above the product grid on the shop archive.
add_action( 'woocommerce_before_shop_loop', function () {
    if ( ! function_exists( 'is_shop' ) || ! is_shop() ) return;
    ?>
    <div class="ecm-shop-hero">
        <div class="ecm-shop-hero-eyebrow">Provider Plans</div>
        <h1>Choose <em>Your Plan</em></h1>
        <p>Select the right listing package to connect with families seeking elder care in your area.</p>
    </div>
    <?php
}, 5 );

// Remove default WooCommerce breadcrumb on shop page (ECM uses its own).
add_action( 'init', function () {
    if ( function_exists( 'is_shop' ) ) {
        remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    }
} );