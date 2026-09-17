<?php
/**
 * Provider Dashboard — controller, routing, auth gate, and read-only data helpers.
 *
 * A standalone, custom dashboard at /provider-dashboard/{section}/ that READS data
 * from WP Job Manager (job_listing posts/meta) and WooCommerce (orders). It does NOT
 * override or modify any Job Manager / WooCommerce template — those stay the data layer.
 *
 * Security: every request is gated on is_user_logged_in() + provider ownership; all
 * record access is scoped to the current user (no cross-account access / IDOR).
 */

defined( 'ABSPATH' ) || exit;

const PD_REWRITE_VERSION = '1';
const PD_CACHE_TTL       = 600; // 10 min

/** Sections the dashboard knows about. Unknown sections fall back to home. */
function pd_allowed_sections() : array {
	return [ 'home', 'listings', 'profile', 'stats', 'reviews', 'billing', 'leads', 'content', 'settings', 'register', 'lost-password' ];
}

/** The current, validated dashboard section (defaults to 'home'). */
function pd_current_section() : string {
	$section = sanitize_key( (string) get_query_var( 'pd_section' ) );
	return in_array( $section, pd_allowed_sections(), true ) ? $section : 'home';
}

/** True when the dashboard route is active for this request. */
function pd_is_dashboard_request() : bool {
	return '' !== (string) get_query_var( 'pd_section' );
}

// ── Routing ───────────────────────────────────────────────────────────────────

add_action( 'init', function () {
	add_rewrite_rule( '^provider-dashboard/?$', 'index.php?pd_section=home', 'top' );
	add_rewrite_rule( '^provider-dashboard/([^/]+)/?$', 'index.php?pd_section=$matches[1]', 'top' );

	if ( PD_REWRITE_VERSION !== get_option( 'pd_rewrite_version' ) ) {
		flush_rewrite_rules( false );
		update_option( 'pd_rewrite_version', PD_REWRITE_VERSION );
	}

	// Clean up _wp_old_slug meta on the conflicting page once (one-time, idempotent).
	if ( get_option( 'pd_old_slug_cleaned' ) !== '1' ) {
		$pages = get_posts( [ 'post_type' => 'page', 'meta_key' => '_wp_old_slug', 'meta_value' => 'provider-dashboard', 'posts_per_page' => 5, 'fields' => 'ids' ] );
		foreach ( $pages as $pid ) {
			delete_post_meta( $pid, '_wp_old_slug', 'provider-dashboard' );
		}
		update_option( 'pd_old_slug_cleaned', '1' );
	}
} );

// Prevent WordPress redirect_canonical from hijacking /provider-dashboard/* URLs
// (happens when a WP page previously had this slug and left a _wp_old_slug entry).
add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
	if ( preg_match( '#/provider-dashboard(/|$)#', $requested_url ) ) {
		return false;
	}
	return $redirect_url;
}, 1, 2 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'pd_section';
	return $vars;
} );

// Load the dashboard controller template for dashboard requests.
add_filter( 'template_include', function ( $template ) {
	if ( ! pd_is_dashboard_request() ) {
		return $template;
	}
	$controller = get_theme_file_path( 'provider-dashboard/controller.php' );
	return file_exists( $controller ) ? $controller : $template;
} );

// ── Assets (only on the dashboard route) ──────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
	if ( ! pd_is_dashboard_request() ) {
		return;
	}
	$v  = wp_get_theme()->get( 'Version' );
	$tu = get_template_directory_uri();

	wp_enqueue_style( 'ecm-google-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap', [], null );
	wp_enqueue_style( 'ecm-provider', $tu . '/assets/css/provider.css', [ 'ecm-google-fonts' ], $v );

	wp_enqueue_script( 'ecm-provider', $tu . '/assets/js/provider-script.js', [], $v, true );
}, 20 );

/**
 * The dashboard is self-contained chrome. Strip all other stylesheets (marketing
 * theme CSS + WooCommerce CSS) on the dashboard route so they can't conflict with
 * provider.css — e.g. main.css's site-header `nav {}` rule was collapsing the
 * sidebar. Mirrors the prototype, which loaded only provider.css.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! pd_is_dashboard_request() ) {
		return;
	}
	$keep = [ 'ecm-provider', 'ecm-google-fonts' ];
	foreach ( (array) wp_styles()->queue as $handle ) {
		if ( ! in_array( $handle, $keep, true ) ) {
			wp_dequeue_style( $handle );
		}
	}
}, 100 );

// Render native (text) emoji on the dashboard — the prototype uses text emoji, and
// WordPress's twemoji conversion turns them into oversized <img class="emoji"> blocks.
add_action( 'template_redirect', function () {
	if ( ! pd_is_dashboard_request() ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content', 'convert_smilies' );
	add_filter( 'emoji_svg_url', '__return_false' );

	// Hide the WP admin toolbar — the dashboard is a standalone provider UI, and we
	// strip admin CSS on this route so the bar would otherwise render unstyled.
	add_filter( 'show_admin_bar', '__return_false' );
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
} );

// ── Auth / provider identity ──────────────────────────────────────────────────

/**
 * Is the given (or current) user a provider? Providers own ≥1 job_listing.
 * Admins are allowed through for preview/support.
 */
function pd_is_provider( ?int $uid = null ) : bool {
	$uid = $uid ?: get_current_user_id();
	if ( ! $uid ) {
		return false;
	}
	if ( user_can( $uid, 'manage_options' ) ) {
		return true;
	}
	return count_user_posts( $uid, 'job_listing', true ) > 0;
}

// ── Read-only data helpers (scoped to the current user; transient-cached) ──────

/**
 * IDs of the current user's own job_listings (all relevant statuses).
 * Cached per-user; invalidated on save_post_job_listing (see below).
 */
function pd_get_my_listing_ids( int $uid ) : array {
	$key    = 'pd_listings_' . $uid;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$ids = get_posts( [
		'post_type'      => 'job_listing',
		'author'         => $uid,
		'post_status'    => [ 'publish', 'expired', 'pending', 'pending_payment', 'draft' ],
		'posts_per_page' => 500,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
	set_transient( $key, $ids, PD_CACHE_TTL );
	return $ids;
}

/** Aggregate dashboard KPIs for the user (real data; cached). */
function pd_get_kpis( int $uid ) : array {
	$key    = 'pd_kpis_' . $uid;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$ids    = pd_get_my_listing_ids( $uid );
	$active = 0;
	$views  = 0;
	foreach ( $ids as $id ) {
		if ( 'publish' === get_post_status( $id ) ) {
			$active++;
		}
		$v = (int) get_post_meta( $id, '_wpjms_visits_total', true );
		if ( ! $v ) {
			$v = (int) get_post_meta( $id, '_count-views_all', true ); // legacy fallback
		}
		$views += $v;
	}

	$kpis = [
		'total_listings' => count( $ids ),
		'active'         => $active,
		'views'          => $views,
		'spend'          => pd_get_monthly_spend( $uid ),
	];
	set_transient( $key, $kpis, PD_CACHE_TTL );
	return $kpis;
}

/** Sum of the user's WooCommerce order totals in the last 30 days (real billing). */
function pd_get_monthly_spend( int $uid ) : float {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return 0.0;
	}
	$orders = wc_get_orders( [
		'customer_id' => $uid,
		'limit'       => 100,
		'status'      => [ 'wc-completed', 'wc-processing' ],
		'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ),
		'return'      => 'objects',
	] );
	$total = 0.0;
	foreach ( $orders as $order ) {
		$total += (float) $order->get_total();
	}
	return $total;
}

// ── Cache invalidation ────────────────────────────────────────────────────────

/** The provider's "primary" listing = most recent published job_listing they own. */
function pd_get_primary_listing_id( int $uid ) : int {
	$ids = get_posts( [
		'post_type'      => 'job_listing',
		'author'         => $uid,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
	return $ids ? (int) $ids[0] : 0;
}

// ── Secure form handlers (admin-post) ─────────────────────────────────────────

/** Save provider profile → writes to the provider's primary listing. */
add_action( 'admin_post_pd_save_profile', function () {
	if ( ! is_user_logged_in() || ! pd_is_provider() ) {
		wp_die( esc_html__( 'Permission denied.', 'eldercare-matters' ), '', [ 'response' => 403 ] );
	}
	check_admin_referer( 'pd_save_profile' );

	$uid        = get_current_user_id();
	$listing_id = (int) ( $_POST['listing_id'] ?? 0 );
	$primary    = pd_get_primary_listing_id( $uid );

	// Ownership: the posted listing must be the user's own primary listing (no IDOR).
	if ( ! $listing_id || $listing_id !== $primary || (int) get_post_field( 'post_author', $listing_id ) !== $uid ) {
		wp_die( esc_html__( 'Invalid listing.', 'eldercare-matters' ), '', [ 'response' => 403 ] );
	}

	$title   = sanitize_text_field( wp_unslash( $_POST['biz_name'] ?? '' ) );
	$desc    = wp_kses_post( wp_unslash( $_POST['biz_desc'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['biz_phone'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['biz_email'] ?? '' ) );
	$website = esc_url_raw( wp_unslash( $_POST['biz_website'] ?? '' ) );

	if ( '' !== $title ) {
		wp_update_post( [ 'ID' => $listing_id, 'post_title' => $title, 'post_content' => $desc ] );
		update_post_meta( $listing_id, '_company_name', $title );
	}
	update_post_meta( $listing_id, '_phone', $phone );
	if ( $email ) {
		update_post_meta( $listing_id, '_application', $email );
	}
	update_post_meta( $listing_id, '_company_website', $website );

	pd_flush_user_cache( $uid );
	wp_safe_redirect( add_query_arg( 'pd_notice', 'profile_saved', home_url( '/provider-dashboard/profile/' ) ) );
	exit;
} );

/** Save account + notification settings → WP user + user meta. */
add_action( 'admin_post_pd_save_settings', function () {
	if ( ! is_user_logged_in() || ! pd_is_provider() ) {
		wp_die( esc_html__( 'Permission denied.', 'eldercare-matters' ), '', [ 'response' => 403 ] );
	}
	check_admin_referer( 'pd_save_settings' );

	$uid = get_current_user_id();

	$display = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
	if ( '' !== $display ) {
		wp_update_user( [ 'ID' => $uid, 'display_name' => $display ] );
	}

	// Notification + lead preferences — whitelist of known keys only.
	$prefs   = [];
	$toggles = [ 'notify_lead', 'notify_purchase', 'notify_message', 'notify_billing', 'notify_updates' ];
	foreach ( $toggles as $t ) {
		$prefs[ $t ] = ! empty( $_POST[ $t ] ) ? 1 : 0;
	}
	$prefs['max_price'] = sanitize_text_field( wp_unslash( $_POST['max_price'] ?? '' ) );
	$prefs['min_lqs']   = sanitize_text_field( wp_unslash( $_POST['min_lqs'] ?? '' ) );
	$prefs['min_lis']   = sanitize_text_field( wp_unslash( $_POST['min_lis'] ?? '' ) );
	update_user_meta( $uid, 'pd_prefs', $prefs );

	// Optional password change (validated; WP handles hashing).
	$pw  = (string) ( $_POST['new_password'] ?? '' );
	$pw2 = (string) ( $_POST['confirm_password'] ?? '' );
	$notice = 'settings_saved';
	if ( '' !== $pw || '' !== $pw2 ) {
		if ( $pw === $pw2 && strlen( $pw ) >= 8 ) {
			wp_set_password( $pw, $uid );
			$notice = 'password_changed'; // forces re-login
		} else {
			$notice = 'password_error';
		}
	}

	wp_safe_redirect( add_query_arg( 'pd_notice', $notice, home_url( '/provider-dashboard/settings/' ) ) );
	exit;
} );

/** Public provider registration (creates a subscriber; emails a set-password link). */
function pd_handle_register() {
	$redirect = home_url( '/provider-dashboard/register/' );
	check_admin_referer( 'pd_register' );

	// Basic per-IP throttle.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$key = 'pd_reg_' . md5( $ip );
	if ( (int) get_transient( $key ) >= 5 ) {
		wp_safe_redirect( add_query_arg( 'pd_err', 'rate', $redirect ) );
		exit;
	}
	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'pd_err', 'email', $redirect ) );
		exit;
	}
	if ( email_exists( $email ) ) {
		wp_safe_redirect( add_query_arg( 'pd_err', 'exists', $redirect ) );
		exit;
	}

	$username = sanitize_user( current( explode( '@', $email ) ), true );
	$base = $username ?: 'provider';
	$i = 1;
	while ( username_exists( $username ) ) {
		$username = $base . $i++;
	}

	$uid = wp_insert_user( [
		'user_login'   => $username,
		'user_email'   => $email,
		'user_pass'    => wp_generate_password( 20 ),
		'display_name' => $name ?: $username,
		'role'         => 'subscriber',
	] );

	if ( is_wp_error( $uid ) ) {
		wp_safe_redirect( add_query_arg( 'pd_err', 'fail', $redirect ) );
		exit;
	}

	wp_new_user_notification( $uid, null, 'user' ); // emails the new user a set-password link
	wp_safe_redirect( add_query_arg( 'pd_done', '1', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_pd_register', 'pd_handle_register' );
add_action( 'admin_post_pd_register', 'pd_handle_register' );

function pd_flush_user_cache( int $uid ) : void {
	delete_transient( 'pd_listings_' . $uid );
	delete_transient( 'pd_kpis_' . $uid );
}

add_action( 'save_post_job_listing', function ( $post_id, $post ) {
	pd_flush_user_cache( (int) $post->post_author );
}, 10, 2 );

add_action( 'woocommerce_order_status_changed', function ( $order_id ) {
	if ( function_exists( 'wc_get_order' ) && ( $order = wc_get_order( $order_id ) ) ) {
		pd_flush_user_cache( (int) $order->get_customer_id() );
	}
} );
