<?php
/**
 * Logged-out / non-provider state: provider login + register prompt.
 * Uses the existing custom-ajax-login plugin shortcode; no custom auth.
 */

defined( 'ABSPATH' ) || exit;

$pd_redirect    = home_url( '/provider-dashboard/' );
$pd_register_url = home_url( '/add-your-listing/' ); // WP Job Manager submit flow (handles registration)
$pd_logged_in   = is_user_logged_in();
?>
<div class="pd-auth">
	<div class="pd-auth-card">
		<h1 class="pd-auth-title">Provider Login</h1>
		<p class="pd-auth-sub">Sign in to manage your listings, profile, and billing.</p>

		<?php if ( $pd_logged_in ) : ?>
			<div class="pd-auth-notice">
				You're signed in, but this account isn't linked to any provider listings yet.
				<a href="<?php echo esc_url( $pd_register_url ); ?>">Create your listing</a> to get started.
			</div>
		<?php else : ?>
			<?php echo do_shortcode( '[custom_login_form redirect="' . esc_url( $pd_redirect ) . '"]' ); ?>

			<div class="pd-auth-links">
				<a href="<?php echo esc_url( home_url( '/provider-dashboard/lost-password/' ) ); ?>">Lost your password?</a>
				<span>Don't have an account? <a href="<?php echo esc_url( home_url( '/provider-dashboard/register/' ) ); ?>">Register</a></span>
			</div>
			<p class="pd-auth-foot">
				New provider? <a href="<?php echo esc_url( $pd_register_url ); ?>">Create your free listing →</a>
			</p>
		<?php endif; ?>
	</div>
</div>
