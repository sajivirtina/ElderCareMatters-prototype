<?php
/**
 * M1 — My Listings. Real job_listing posts owned by the current user.
 * Links to JM edit screen (no template override).
 */

defined( 'ABSPATH' ) || exit;

$uid  = get_current_user_id();
$ids  = pd_get_my_listing_ids( $uid );

// Status label map
$status_labels = [
    'publish'         => [ 'Active',          'pd-badge--green'  ],
    'expired'         => [ 'Expired',         'pd-badge--red'    ],
    'pending'         => [ 'Pending Review',  'pd-badge--amber'  ],
    'pending_payment' => [ 'Pending Payment', 'pd-badge--amber'  ],
    'draft'           => [ 'Draft',           'pd-badge--gray'   ],
];

// Pagination
$per_page    = 10;
$total       = count( $ids );
$total_pages = max( 1, (int) ceil( $total / $per_page ) );
$cur_page    = max( 1, min( $total_pages, (int) ( $_GET['lpg'] ?? 1 ) ) );
$offset      = ( $cur_page - 1 ) * $per_page;
$page_ids    = array_slice( $ids, $offset, $per_page );
?>

<div class="dash-main__body">
<div class="pd-section-header">
	<h1 class="pd-section-title">My Listings</h1>
	<p class="pd-section-sub"><?php echo esc_html( $total ); ?> listing<?php echo $total !== 1 ? 's' : ''; ?> found</p>
</div>

<?php if ( ! $ids ) : ?>
	<div class="pd-empty-state">
		<div class="pd-empty-icon">🏢</div>
		<h3>No listings yet</h3>
		<p>You haven't created any provider listings. Get started by adding your first listing.</p>
		<a href="<?php echo esc_url( home_url( '/add-your-listing/' ) ); ?>" class="pd-btn pd-btn--primary">Add your listing →</a>
	</div>
<?php else : ?>

<div class="pd-listings-table-wrap">
	<table class="pd-listings-table">
		<thead>
			<tr>
				<th>Listing</th>
				<th>Location</th>
				<th>Category</th>
				<th>Views</th>
				<th>Status</th>
				<th>Expires</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $page_ids as $pid ) :
			$post     = get_post( $pid );
			$status   = get_post_status( $pid );
			$s_label  = $status_labels[ $status ] ?? [ ucfirst( $status ), 'pd-badge--gray' ];

			// Logo
			$logo_url = '';
			if ( has_post_thumbnail( $pid ) ) {
				$logo_url = get_the_post_thumbnail_url( $pid, [ 48, 48 ] );
			} elseif ( $av = get_post_meta( $pid, '_company_avatar', true ) ) {
				$logo_url = $av;
			}

			// Location
			$city     = get_post_meta( $pid, 'geolocation_city', true );
			$state    = get_post_meta( $pid, 'geolocation_state_short', true );
			$location = $city ? trim( $city . ( $state ? ', ' . $state : '' ) ) : get_post_meta( $pid, '_job_location', true );

			// Category
			$cats     = get_the_terms( $pid, 'job_listing_category' );
			$cat_name = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '—';

			// Views (Stats add-on)
			$views    = (int) get_post_meta( $pid, '_wpjms_visits_total', true );
			if ( ! $views ) {
				$views = (int) get_post_meta( $pid, '_count-views_all', true );
			}

			// Expiry
			$expires  = get_post_meta( $pid, '_job_expires', true );
			$exp_str  = $expires ? date_i18n( 'M j, Y', strtotime( $expires ) ) : '—';
			$exp_cls  = ( $expires && strtotime( $expires ) < time() ) ? ' pd-expired-date' : '';

			// Edit URL (JM native)
			$edit_url = admin_url( 'post.php?post=' . $pid . '&action=edit' );
			$view_url = get_permalink( $pid );
		?>
			<tr>
				<td class="pd-listing-name-cell">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="pd-listing-logo">
					<?php else :
						$initials = strtoupper( substr( $post->post_title, 0, 1 ) ); ?>
						<span class="pd-listing-initials"><?php echo esc_html( $initials ); ?></span>
					<?php endif; ?>
					<span class="pd-listing-title"><?php echo esc_html( $post->post_title ); ?></span>
				</td>
				<td><?php echo esc_html( $location ?: '—' ); ?></td>
				<td><?php echo esc_html( $cat_name ); ?></td>
				<td><?php echo esc_html( number_format( $views ) ); ?></td>
				<td><span class="pd-badge <?php echo esc_attr( $s_label[1] ); ?>"><?php echo esc_html( $s_label[0] ); ?></span></td>
				<td class="<?php echo esc_attr( $exp_cls ); ?>"><?php echo esc_html( $exp_str ); ?></td>
				<td class="pd-listing-actions">
					<a href="<?php echo esc_url( $edit_url ); ?>" class="pd-action-link" title="Edit">✏️ Edit</a>
					<?php if ( $view_url && 'publish' === $status ) : ?>
					<a href="<?php echo esc_url( $view_url ); ?>" class="pd-action-link" target="_blank" title="View">👁 View</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php if ( $total_pages > 1 ) : ?>
<div class="pd-pagination">
	<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'lpg', $p ) ); ?>"
		   class="pd-page-link<?php echo $p === $cur_page ? ' active' : ''; ?>">
			<?php echo esc_html( $p ); ?>
		</a>
	<?php endfor; ?>
</div>
<?php endif; ?>

<div class="pd-listings-footer">
	<a href="<?php echo esc_url( home_url( '/add-your-listing/' ) ); ?>" class="pd-btn pd-btn--primary">+ Add new listing</a>
</div>

<?php endif; ?>
</div><!-- /.dash-main__body -->
