<?php
/**
 * Settings — account details + notification/lead preferences (real, secure save).
 * Persists via admin_post_pd_save_settings (nonce + capability).
 */

defined( 'ABSPATH' ) || exit;

$uid   = get_current_user_id();
$user  = wp_get_current_user();
$prefs = wp_parse_args( (array) get_user_meta( $uid, 'pd_prefs', true ), [
	'notify_lead' => 1, 'notify_purchase' => 1, 'notify_message' => 1, 'notify_billing' => 0, 'notify_updates' => 1,
	'max_price' => 'Up to $50', 'min_lqs' => '70+', 'min_lis' => '70+',
] );

$notice = isset( $_GET['pd_notice'] ) ? sanitize_key( $_GET['pd_notice'] ) : '';

$pd_toggle = function ( $name, $label, $desc, $on ) {
	printf(
		'<div class="setting-row"><div class="setting-info"><div class="setting-label">%s</div><div class="setting-desc">%s</div></div>'
		. '<label class="toggle"><input type="checkbox" name="%s" value="1" %s><span class="toggle-slider"></span></label></div>',
		esc_html( $label ), esc_html( $desc ), esc_attr( $name ), checked( $on, 1, false )
	);
};
$pd_opts = function ( $name, $options, $current ) {
	echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
	foreach ( $options as $o ) {
		echo '<option ' . selected( $current, $o, false ) . '>' . esc_html( $o ) . '</option>';
	}
	echo '</select>';
};
?>
<div class="dash-main__header">
	<div>
		<div class="dash-main__title">Settings</div>
		<div class="dash-main__sub">Manage notifications, lead preferences, and account details</div>
	</div>
</div>

<div class="dash-main__body">

	<?php if ( 'settings_saved' === $notice ) : ?>
		<div class="pd-notice pd-notice--success">✓ Settings saved.</div>
	<?php elseif ( 'password_changed' === $notice ) : ?>
		<div class="pd-notice pd-notice--success">✓ Settings saved. Your password was changed — please sign in again.</div>
	<?php elseif ( 'password_error' === $notice ) : ?>
		<div class="pd-notice pd-notice--error">Passwords didn't match or were under 8 characters. Other settings were saved.</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pd_save_settings">
		<?php wp_nonce_field( 'pd_save_settings' ); ?>

		<!-- Notifications -->
		<div class="settings-section">
			<div class="settings-section__title">Notifications</div>
			<?php
			$pd_toggle( 'notify_lead', 'New lead available', 'Get notified when a new geo-matched lead appears in your inbox.', $prefs['notify_lead'] );
			$pd_toggle( 'notify_purchase', 'Lead purchased confirmation', 'Receive a confirmation email when you successfully purchase a lead.', $prefs['notify_purchase'] );
			$pd_toggle( 'notify_message', 'Consumer message received', "Alert when a family you've contacted sends a message through ECM.", $prefs['notify_message'] );
			$pd_toggle( 'notify_billing', 'Monthly billing summary', 'Receive a monthly email summarising your spend and ROI.', $prefs['notify_billing'] );
			$pd_toggle( 'notify_updates', 'ECM platform updates', 'News about new features, improvements, and best practices.', $prefs['notify_updates'] );
			?>
		</div>

		<!-- Lead Preferences -->
		<div class="settings-section">
			<div class="settings-section__title">Lead Preferences <span class="pd-sample-badge">Preview</span></div>
			<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:12px;">
				<div class="form-group"><label for="max_price">Max lead price</label><?php $pd_opts( 'max_price', [ 'No limit', 'Up to $25', 'Up to $50', 'Up to $75', 'Up to $100' ], $prefs['max_price'] ); ?></div>
				<div class="form-group"><label for="min_lqs">Min Lead Quality (LQS)</label><?php $pd_opts( 'min_lqs', [ 'Any', '60+', '70+', '80+', '90+' ], $prefs['min_lqs'] ); ?></div>
				<div class="form-group"><label for="min_lis">Min Intent (LIS)</label><?php $pd_opts( 'min_lis', [ 'Any', '60+', '70+', '80+', '90+' ], $prefs['min_lis'] ); ?></div>
			</div>
			<p style="font-size:0.78rem;color:var(--gray-500);">Saved now; applied once the lead system goes live.</p>
		</div>

		<!-- Account -->
		<div class="settings-section">
			<div class="settings-section__title">Account</div>
			<div class="form-grid" style="max-width:480px;">
				<div class="form-group form-full">
					<label for="display_name">Display Name</label>
					<input id="display_name" name="display_name" type="text" value="<?php echo esc_attr( $user->display_name ); ?>">
				</div>
				<div class="form-group form-full">
					<label for="acc-email">Login Email</label>
					<input id="acc-email" type="email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly style="background:var(--gray-50);color:var(--gray-500);">
				</div>
				<div class="form-group form-full">
					<label for="new_password">New Password</label>
					<input id="new_password" name="new_password" type="password" placeholder="Leave blank to keep current" autocomplete="new-password">
				</div>
				<div class="form-group form-full">
					<label for="confirm_password">Confirm Password</label>
					<input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm new password" autocomplete="new-password">
				</div>
			</div>
		</div>

		<div style="margin-top:4px;">
			<button type="submit" class="btn--primary">Save Settings</button>
		</div>
	</form>
</div>
