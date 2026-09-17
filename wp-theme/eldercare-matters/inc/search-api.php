<?php
/**
 * ECM Search REST API
 *
 * Endpoints:
 *   GET /wp-json/ecm/v1/search          — provider search (Find Care page)
 *   GET /wp-json/ecm/v1/search/meta     — categories + cities metadata
 *   GET /wp-json/ecm/v1/category        — category page: category meta + providers
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {

	register_rest_route( 'ecm/v1', '/search', [
		'methods'             => 'GET',
		'callback'            => 'ecm_search_endpoint',
		'permission_callback' => '__return_true',
		'args'                => [
			'q'        => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'category' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'city'     => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'sort'     => [ 'sanitize_callback' => 'sanitize_key',        'default' => 'recommended' ],
			'per_page' => [ 'sanitize_callback' => 'absint',              'default' => 24 ],
			'page'     => [ 'sanitize_callback' => 'absint',              'default' => 1 ],
		],
	] );

	register_rest_route( 'ecm/v1', '/search/meta', [
		'methods'             => 'GET',
		'callback'            => 'ecm_search_meta_endpoint',
		'permission_callback' => '__return_true',
	] );

	// Category page endpoint — full category meta + providers for a city.
	register_rest_route( 'ecm/v1', '/category', [
		'methods'             => 'GET',
		'callback'            => 'ecm_category_endpoint',
		'permission_callback' => '__return_true',
		'args'                => [
			'type'     => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'city'     => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'sort'     => [ 'sanitize_callback' => 'sanitize_key',        'default' => 'recommended' ],
			'per_page' => [ 'sanitize_callback' => 'absint',              'default' => 50 ],
			'page'     => [ 'sanitize_callback' => 'absint',              'default' => 1 ],
		],
	] );
} );

// ── /search ───────────────────────────────────────────────────────────────────

function ecm_search_endpoint( WP_REST_Request $req ) : WP_REST_Response {
	$q        = $req->get_param( 'q' );
	$category = $req->get_param( 'category' );
	$city     = $req->get_param( 'city' );
	$sort     = $req->get_param( 'sort' );
	$per_page = min( 100, max( 1, (int) $req->get_param( 'per_page' ) ) );
	$page     = max( 1, (int) $req->get_param( 'page' ) );

	$args = [
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'no_found_rows'  => false,
	];

	if ( '' !== $q ) $args['s'] = $q;

	if ( '' !== $category ) {
		$args['tax_query'] = [ [
			'taxonomy'         => 'job_listing_category',
			'field'            => 'slug',
			'terms'            => $category,
			'include_children' => true,
		] ];
	}

	if ( '' !== $city ) {
		$args['meta_query'] = [ [ 'relation' => 'OR',
			[ 'key' => 'geolocation_city',              'value' => $city, 'compare' => 'LIKE' ],
			[ 'key' => 'geolocation_formatted_address', 'value' => $city, 'compare' => 'LIKE' ],
			[ 'key' => '_job_location',                 'value' => $city, 'compare' => 'LIKE' ],
		] ];
	}

	switch ( $sort ) {
		case 'newest':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
		default:
			$args['orderby'] = [ 'menu_order' => 'ASC', 'date' => 'DESC' ];
	}

	$args['update_post_meta_cache'] = true;
	$args['update_post_term_cache'] = true;

	$query     = new WP_Query( $args );
	$providers = [];
	foreach ( $query->posts as $post ) {
		$providers[] = ecm_format_listing( $post );
	}
	wp_reset_postdata();

	return new WP_REST_Response( [
		'total'     => (int) $query->found_posts,
		'pages'     => (int) $query->max_num_pages,
		'providers' => $providers,
	], 200 );
}

// ── /search/meta ──────────────────────────────────────────────────────────────

function ecm_search_meta_endpoint() : WP_REST_Response {
	$cache_key = 'ecm_search_meta_v3';
	$cached    = get_transient( $cache_key );
	if ( $cached ) return new WP_REST_Response( $cached, 200 );

	$terms = get_terms( [
		'taxonomy'   => 'job_listing_category',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	] );

	$categories = [];
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$categories[] = [
				'key'        => $term->slug,
				'name'       => $term->name,
				'icon'       => ecm_category_icon( $term->slug, $term->name ),
				'blurb'      => $term->description ?: ecm_category_blurb( $term->slug ),
				'count'      => (int) $term->count,
				'cross_sell' => ecm_cross_sell( $term->slug ),
			];
		}
	}

	global $wpdb;
	$cities_raw = $wpdb->get_results(
		"SELECT pm.meta_value AS city, COUNT(*) AS cnt
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = 'geolocation_city'
		   AND pm.meta_value != ''
		   AND p.post_type = 'job_listing'
		   AND p.post_status = 'publish'
		 GROUP BY pm.meta_value
		 HAVING cnt >= 3
		 ORDER BY cnt DESC
		 LIMIT 50"
	);

	$cities = [];
	foreach ( $cities_raw as $row ) {
		$cities[] = [ 'city' => $row->city, 'state' => '', 'count' => (int) $row->cnt ];
	}

	$data = compact( 'categories', 'cities' );
	set_transient( $cache_key, $data, 15 * MINUTE_IN_SECONDS );
	return new WP_REST_Response( $data, 200 );
}

// ── /category ─────────────────────────────────────────────────────────────────

function ecm_category_endpoint( WP_REST_Request $req ) : WP_REST_Response {
	$type     = $req->get_param( 'type' );
	$city     = $req->get_param( 'city' );
	$sort     = $req->get_param( 'sort' );
	$per_page = min( 100, max( 1, (int) $req->get_param( 'per_page' ) ) );
	$page     = max( 1, (int) $req->get_param( 'page' ) );

	// Category metadata from taxonomy term.
	$term = $type ? get_term_by( 'slug', $type, 'job_listing_category' ) : null;
	$category = [
		'key'        => $type,
		'name'       => $term ? $term->name : ucwords( str_replace( '-', ' ', $type ) ),
		'icon'       => ecm_category_icon( $type, $term ? $term->name : '' ),
		'blurb'      => ( $term && $term->description ) ? $term->description : ecm_category_blurb( $type ),
		'count'      => $term ? (int) $term->count : 0,
		'cross_sell' => ecm_cross_sell( $type ),
	];

	// Providers in city.
	$args = [
		'post_type'              => 'job_listing',
		'post_status'            => 'publish',
		'posts_per_page'         => $per_page,
		'paged'                  => $page,
		'no_found_rows'          => false,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	];

	if ( $type ) {
		$args['tax_query'] = [ [
			'taxonomy'         => 'job_listing_category',
			'field'            => 'slug',
			'terms'            => $type,
			'include_children' => true,
		] ];
	}

	if ( $city ) {
		$args['meta_query'] = [ [ 'relation' => 'OR',
			[ 'key' => 'geolocation_city',              'value' => $city, 'compare' => 'LIKE' ],
			[ 'key' => 'geolocation_formatted_address', 'value' => $city, 'compare' => 'LIKE' ],
			[ 'key' => '_job_location',                 'value' => $city, 'compare' => 'LIKE' ],
		] ];
	}

	$args['orderby'] = ( $sort === 'newest' )
		? [ 'date' => 'DESC' ]
		: [ 'menu_order' => 'ASC', 'date' => 'DESC' ];

	$query     = new WP_Query( $args );
	$providers = [];
	foreach ( $query->posts as $post ) {
		$providers[] = ecm_format_listing( $post );
	}
	wp_reset_postdata();

	return new WP_REST_Response( [
		'category'  => $category,
		'providers' => $providers,
		'total'     => (int) $query->found_posts,
		'pages'     => (int) $query->max_num_pages,
	], 200 );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function ecm_format_listing( WP_Post $post ) : array {
	$title = $post->post_title;

	preg_match_all( '/\b\w/u', $title, $m );
	$initials = strtoupper( implode( '', array_slice( $m[0], 0, 2 ) ) );
	if ( ! $initials ) $initials = strtoupper( mb_substr( $title, 0, 2 ) );

	$palette = [ '#C4933A', '#4f6b53', '#7a5a1e', '#5b6864', '#A0703A', '#3f7a6a', '#8a6d3b', '#6a5acd' ];
	$color   = $palette[ $post->ID % count( $palette ) ];

	$logo_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '';
	if ( ! $logo_url ) $logo_url = (string) get_post_meta( $post->ID, '_company_avatar', true );

	$city  = (string) get_post_meta( $post->ID, 'geolocation_city',        true );
	$state = (string) get_post_meta( $post->ID, 'geolocation_state_short', true );
	if ( ! $city ) $city = (string) get_post_meta( $post->ID, '_job_location', true );
	$city_label = $city ? ( $state ? $city . ', ' . $state : $city ) : '';

	$is_featured = function_exists( 'is_position_featured' ) && is_position_featured( $post );
	$plan_label  = (string) get_post_meta( $post->ID, '_listing_plan_label', true );
	if ( $is_featured ) {
		$tier = 'featured';
	} elseif ( $plan_label ) {
		$t    = strtolower( $plan_label );
		$tier = str_contains( $t, 'premium' ) ? 'premium' : ( str_contains( $t, 'basic' ) ? 'basic' : 'standard' );
	} else {
		$tier = 'basic';
	}

	$tagline = (string) get_post_meta( $post->ID, '_company_tagline', true );
	if ( ! $tagline ) $tagline = wp_trim_words( wp_strip_all_tags( $post->post_content ), 15, '…' );

	$terms       = get_the_terms( $post->ID, 'job_listing_category' ) ?: [];
	$specialties = array_map( fn( $t ) => $t->name, array_slice( $terms, 0, 3 ) );
	$cat_slug    = $terms ? $terms[0]->slug : '';
	$cat_name    = $terms ? $terms[0]->name : '';

	$avg_rating   = (float) get_post_meta( $post->ID, '_average_rating', true );
	$review_count = (int)   get_post_meta( $post->ID, '_review_count',   true );
	if ( ! $avg_rating ) $avg_rating = (float) get_post_meta( $post->ID, '_job_rating', true );
	$avg_rating = $avg_rating ? round( $avg_rating, 1 ) : 0.0;

	return [
		'id'          => $post->ID,
		'name'        => $title,
		'initials'    => $initials,
		'color'       => $color,
		'logo_url'    => $logo_url,
		'category'    => $cat_slug,
		'cat_name'    => $cat_name,
		'city'        => $city,
		'state'       => $state,
		'city_label'  => $city_label,
		'tier'        => $tier,
		'tagline'     => $tagline,
		'specialties' => $specialties,
		'rating'      => $avg_rating,
		'reviews'     => $review_count,
		'nonprofit'   => (bool) get_post_meta( $post->ID, '_company_nonprofit', true ),
		'permalink'   => get_permalink( $post ),
	];
}

function ecm_category_icon( string $slug, string $name ) : string {
	$map = [
		'home-care'       => '🏠',
		'assisted-living' => '🏡',
		'memory-care'     => '🧠',
		'elder-law'       => '⚖️',
		'care-management' => '📋',
		'hospice'         => '🤝',
		'grief'           => '💜',
		'nursing'         => '🏥',
		'adult-day'       => '☀️',
		'independent'     => '🏘️',
		'rehab'           => '💪',
		'dementia'        => '🧠',
		'transportation'  => '🚗',
	];
	foreach ( $map as $key => $icon ) {
		if ( str_contains( $slug, $key ) || str_contains( strtolower( $name ), $key ) ) return $icon;
	}
	return '🏥';
}

function ecm_category_blurb( string $slug ) : string {
	$map = [
		'home-care'       => 'Daily non-medical support delivered in the comfort of home — bathing, meals, medication reminders, companionship.',
		'assisted-living' => 'Community living with on-site staff, meals, activities, and medical support.',
		'memory-care'     => 'Specialized support for Alzheimer\'s, dementia, and other memory conditions in a secure setting.',
		'elder-law'       => 'Guardianship, estate planning, Medicaid, and power of attorney from locally licensed attorneys.',
		'care-management' => 'A dedicated care manager coordinates every service — one family point of contact for the whole plan.',
		'hospice'         => 'Compassionate end-of-life care at home or in a dedicated facility, with full family support.',
		'grief-counselors'=> 'Professional grief and bereavement support for families and seniors navigating loss.',
	];
	foreach ( $map as $key => $blurb ) {
		if ( str_contains( $slug, $key ) ) return $blurb;
	}
	return 'Find verified providers near you.';
}

function ecm_cross_sell( string $slug ) : array {
	$cross_map = [
		'home-care'       => [ 'care-management', 'elder-law' ],
		'assisted-living' => [ 'care-management', 'elder-law' ],
		'memory-care'     => [ 'home-care', 'hospice' ],
		'elder-law'       => [ 'home-care', 'care-management' ],
		'care-management' => [ 'home-care', 'elder-law' ],
		'hospice'         => [ 'grief-counselors', 'care-management' ],
		'grief-counselors'=> [ 'hospice', 'care-management' ],
	];
	$keys = $cross_map[ $slug ] ?? [];
	$result = [];
	foreach ( $keys as $key ) {
		$term = get_term_by( 'slug', $key, 'job_listing_category' );
		$result[] = [
			'key'  => $key,
			'name' => $term ? $term->name : ucwords( str_replace( '-', ' ', $key ) ),
			'icon' => ecm_category_icon( $key, '' ),
		];
	}
	return $result;
}

// Bust caches when a listing is saved.
add_action( 'save_post_job_listing', function () {
	delete_transient( 'ecm_search_meta_v2' );
	delete_transient( 'ecm_search_meta_v3' );
} );
