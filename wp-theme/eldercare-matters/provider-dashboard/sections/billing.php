<?php
/**
 * M6 — Billing. Real WooCommerce order history for the current provider.
 * Uses wc_get_orders() — no dummy data.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_orders' ) ) : ?>
<div class="dash-main__body">
	<div class="pd-section-header">
		<h1 class="pd-section-title">Billing</h1>
	</div>
	<div class="pd-empty-state">
		<div class="pd-empty-icon">💳</div>
		<h3>WooCommerce is not active</h3>
		<p>Billing information requires WooCommerce to be installed and active.</p>
	</div>
</div><!-- /.dash-main__body -->
<?php return; endif;

$uid    = get_current_user_id();
$orders = wc_get_orders( [
	'customer_id' => $uid,
	'limit'       => 50,
	'orderby'     => 'date',
	'order'       => 'DESC',
	'status'      => [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-cancelled', 'wc-refunded' ],
] );

// Aggregate KPIs
$total_spend  = 0.0;
$total_orders = count( $orders );
foreach ( $orders as $order ) {
	if ( in_array( $order->get_status(), [ 'completed', 'processing' ], true ) ) {
		$total_spend += (float) $order->get_total();
	}
}

// Active subscriptions (WC Subscriptions add-on)
$active_subs = 0;
if ( function_exists( 'wcs_get_subscriptions' ) ) {
	$subs = wcs_get_subscriptions( [
		'customer_id'        => $uid,
		'subscriptions_per_page' => -1,
		'subscription_status'    => [ 'active' ],
	] );
	$active_subs = count( $subs );
}
?>

<div class="dash-main__body">
<div class="pd-section-header">
	<h1 class="pd-section-title">Billing</h1>
	<p class="pd-section-sub">Your order history and spend with ElderCareMatters.</p>
</div>

<!-- KPI row -->
<div class="kpi-row" style="margin-bottom:28px">
	<div class="kpi-card">
		<div class="kpi-label">Total Orders</div>
		<div class="kpi-value"><?php echo esc_html( number_format_i18n( $total_orders ) ); ?></div>
		<div class="kpi-sub">All time</div>
	</div>
	<div class="kpi-card">
		<div class="kpi-label">Total Spend</div>
		<div class="kpi-value"><?php echo $total_spend > 0 ? esc_html( strip_tags( wc_price( $total_spend ) ) ) : '$0'; ?></div>
		<div class="kpi-sub">Completed &amp; processing orders</div>
	</div>
	<?php if ( function_exists( 'wcs_get_subscriptions' ) ) : ?>
	<div class="kpi-card">
		<div class="kpi-label">Active Subscriptions</div>
		<div class="kpi-value"><?php echo esc_html( number_format_i18n( $active_subs ) ); ?></div>
		<div class="kpi-sub">WooCommerce Subscriptions</div>
	</div>
	<?php endif; ?>
</div>

<?php if ( ! $orders ) : ?>
	<div class="pd-empty-state">
		<div class="pd-empty-icon">💳</div>
		<h3>No orders yet</h3>
		<p>Your purchase history will appear here once you have placed an order.</p>
		<a href="<?php echo esc_url( home_url( '/for-providers/' ) ); ?>" class="pd-btn pd-btn--primary">View Plans →</a>
	</div>
<?php else : ?>

<div class="pd-card" style="padding:0;overflow:hidden">
	<div style="padding:20px 24px 12px;border-bottom:1px solid var(--gray-100,#f3f4f6)">
		<h3 style="margin:0;font-size:1rem;font-weight:600">Order History</h3>
	</div>
	<table class="pd-listings-table">
		<thead>
			<tr>
				<th>Order</th>
				<th>Date</th>
				<th>Items</th>
				<th>Total</th>
				<th>Status</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $orders as $order ) :
			$status     = $order->get_status();
			$badge_cls  = match( $status ) {
				'completed'  => 'pd-badge--green',
				'processing' => 'pd-badge--teal',
				'on-hold'    => 'pd-badge--amber',
				'cancelled', 'refunded' => 'pd-badge--red',
				default      => 'pd-badge--gray',
			};
			$items = $order->get_items();
			$item_names = array_map( fn( $i ) => $i->get_name(), array_values( $items ) );
		?>
			<tr>
				<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="pd-table-link">#<?php echo esc_html( $order->get_id() ); ?></a></td>
				<td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '—' ); ?></td>
				<td style="max-width:240px">
					<?php if ( $item_names ) : ?>
						<span title="<?php echo esc_attr( implode( ', ', $item_names ) ); ?>">
							<?php echo esc_html( $item_names[0] ); ?>
							<?php if ( count( $item_names ) > 1 ) : ?>
								<span style="color:var(--gray-400);font-size:0.8rem"> +<?php echo count( $item_names ) - 1; ?> more</span>
							<?php endif; ?>
						</span>
					<?php else : ?>
						<span style="color:var(--gray-400)">—</span>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( strip_tags( wc_price( $order->get_total() ) ) ); ?></td>
				<td><span class="pd-badge <?php echo esc_attr( $badge_cls ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span></td>
				<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="pd-table-link" style="white-space:nowrap">View →</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php endif; ?>
</div><!-- /.dash-main__body -->
