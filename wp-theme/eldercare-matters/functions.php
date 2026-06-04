<?php
/**
 * ElderCareMatters Theme Functions
 *
 * Sets up theme, enqueues assets, loads ACF field definitions.
 */

defined( 'ABSPATH' ) || exit;

// ── Theme Setup ───────────────────────────────────────────────────────────────

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ] );
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

// ── Enqueue Styles & Scripts ──────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
    $v  = wp_get_theme()->get( 'Version' );
    $tu = get_template_directory_uri();

    // Google Fonts
    wp_enqueue_style(
        'ecm-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap',
        [],
        null
    );

    // Prototype CSS (global)
    wp_enqueue_style( 'ecm-main',      $tu . '/assets/css/main.css',      [ 'ecm-google-fonts' ], $v );
    wp_enqueue_style( 'ecm-homepage',  $tu . '/assets/css/homepage.css',  [ 'ecm-main' ], $v );
    wp_enqueue_style( 'ecm-intake-v2', $tu . '/assets/css/intake-v2.css', [ 'ecm-homepage' ], $v );

    // Inner-page CSS — loaded on category/search/blog/blog-detail templates
    if ( is_page_template( [ 'template-category.php', 'template-search.php', 'template-blog.php', 'template-blog-detail.php' ] ) ) {
        wp_enqueue_style( 'ecm-inner', $tu . '/assets/css/inner.css', [ 'ecm-homepage' ], $v );
    }

    // For-providers CSS — only on that template
    if ( is_page_template( 'template-for-providers.php' ) ) {
        wp_enqueue_style( 'ecm-for-providers', $tu . '/assets/css/for-providers.css', [ 'ecm-homepage' ], $v );
    }

    // Core scripts — load in footer, everywhere
    wp_enqueue_script( 'ecm-data',     $tu . '/assets/js/data.js',     [],           $v, true );
    wp_enqueue_script( 'ecm-location', $tu . '/assets/js/location.js', [ 'ecm-data' ], $v, true );
    wp_enqueue_script( 'ecm-intake',   $tu . '/assets/js/intake.js',   [ 'ecm-location' ], $v, true );
    wp_enqueue_script( 'ecm-main',     $tu . '/assets/js/main.js',     [ 'ecm-intake' ], $v, true );

    // Category page logic
    if ( is_page_template( 'template-category.php' ) ) {
        wp_enqueue_script( 'ecm-category', $tu . '/assets/js/category.js', [ 'ecm-main' ], $v, true );
    }
    // Search page logic
    if ( is_page_template( 'template-search.php' ) ) {
        wp_enqueue_script( 'ecm-search', $tu . '/assets/js/search.js', [ 'ecm-main' ], $v, true );
    }
} );

// ── ACF Field Groups ──────────────────────────────────────────────────────────

if ( function_exists( 'acf_add_local_field_group' ) ) {
    $inc = get_template_directory() . '/inc';
    require_once $inc . '/acf-fields.php';        // Homepage
    require_once $inc . '/acf-category.php';       // Category page
    require_once $inc . '/acf-search.php';         // Search page
    require_once $inc . '/acf-blog.php';           // Blog listing
    require_once $inc . '/acf-blog-detail.php';    // Blog detail
    require_once $inc . '/acf-for-providers.php';  // For providers
}

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
