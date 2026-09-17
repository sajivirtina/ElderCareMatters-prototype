<?php
/**
 * Provider Dashboard controller — standalone chrome (no marketing nav).
 * Routed here for any /provider-dashboard/{section}/ request. Auth-gated.
 */

defined( 'ABSPATH' ) || exit;

$pd_section = pd_current_section();
$pd_ready   = is_user_logged_in() && pd_is_provider();
$pd_dir     = get_theme_file_path( 'provider-dashboard' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pd-body' ); ?>>

<header class="prov-topbar">
	<a href="<?php echo esc_url( home_url( '/provider-dashboard/' ) ); ?>" class="prov-topbar-logo">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.svg' ); ?>" alt="ElderCareMatters" class="prov-topbar-logo-image">
	</a>
	<div class="prov-topbar-right">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="prov-topbar-link">← Back to site</a>
		<?php if ( $pd_ready ) :
			$pd_user = wp_get_current_user(); ?>
			<span class="prov-topbar-link" style="color:rgba(255,255,255,0.55)"><?php echo esc_html( $pd_user->display_name ); ?></span>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/provider-dashboard/' ) ) ); ?>" class="prov-topbar-link">Log out</a>
		<?php endif; ?>
	</div>
</header>

<?php if ( ! $pd_ready ) :
	$pd_auth_view = in_array( $pd_section, [ 'register', 'lost-password' ], true ) ? $pd_section : 'login';
	require $pd_dir . '/partials/' . $pd_auth_view . '.php';
?>

<?php else : ?>

	<div class="dash-layout">
		<?php require $pd_dir . '/partials/sidebar.php'; ?>
		<main class="dash-main">
			<?php
			$pd_section_file = $pd_dir . '/sections/' . $pd_section . '.php';
			if ( file_exists( $pd_section_file ) ) {
				require $pd_section_file;
			} else {
				require $pd_dir . '/sections/placeholder.php';
			}
			?>
		</main>
	</div>

<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
