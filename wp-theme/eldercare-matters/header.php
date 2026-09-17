<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$trust_enabled = get_field( 'trust_bar_enabled' );
$trust_text    = ecm_get_field( 'trust_bar_text', '✦ Trusted Care Matching · Free for Families - America\'s oldest and most respected Elder Care Directory' );
?>

<?php if ( $trust_enabled !== false ) : ?>
<!-- Trust Bar -->
<div class="trust-bar"><?php echo esc_html( $trust_text ); ?></div>
<?php endif; ?>

<?php
// Logo
$logo     = get_field( 'nav_logo' );
$logo_alt = ecm_get_field( 'nav_logo_alt', 'ElderCareMatters' );
$logo_url = $logo['url'] ?? get_template_directory_uri() . '/assets/images/logo.svg';

// Nav links — split standard links from CTA buttons (rendered in .nav-right)
$nav_links = get_field( 'nav_links' );
$nav_std   = [];
$nav_cta   = [];
if ( $nav_links ) {
    foreach ( $nav_links as $item ) {
        if ( ! empty( $item['is_cta'] ) ) {
            $nav_cta[] = $item;
        } else {
            $nav_std[] = $item;
        }
    }
}

$loc_detecting = ecm_get_field( 'location_badge_detecting', 'Detecting…' );
$loc_change    = ecm_get_field( 'location_badge_change', 'Change' );
?>

<!-- Nav -->
<nav>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo">
        <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" class="nav-logo-image">
    </a>

    <?php if ( $nav_std ) : ?>
    <ul class="nav-links">
        <?php foreach ( $nav_std as $item ) :
            $label   = esc_html( $item['label'] ?? '' );
            $url     = esc_url( $item['url'] ?? '#' );
            $new_tab = ! empty( $item['new_tab'] );
        ?>
        <li>
            <a href="<?php echo $url; ?>"<?php if ( $new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
                <?php echo $label; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else : ?>
    <!-- Fallback nav — replace via ACF "Navigation" tab -->
    <ul class="nav-links">
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="<?php echo esc_url( home_url( '/find-care/' ) ); ?>">Find Care</a></li>
        <li><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources</a></li>
        <li><a href="<?php echo esc_url( home_url( '/for-providers/' ) ); ?>">For Providers</a></li>
    </ul>
    <?php endif; ?>

    <div class="nav-right">
        <div class="nav-location-badge" data-open-location-modal>
            📍 <span class="js-location-full"><?php echo esc_html( $loc_detecting ); ?></span><span class="nav-location-change"><?php echo esc_html( $loc_change ); ?></span>
        </div>
        <?php if ( $nav_cta ) : ?>
            <?php foreach ( $nav_cta as $item ) :
                $label   = esc_html( $item['label'] ?? '' );
                $url     = esc_url( $item['url'] ?? '#' );
                $new_tab = ! empty( $item['new_tab'] );
                $is_pd_btn = strpos( $url, 'provider-dashboard' ) !== false;
                if ( $is_pd_btn ) {
                    $label = is_user_logged_in() ? 'Provider Dashboard' : 'Provider Login';
                }
            ?>
            <a href="<?php echo $url; ?>" class="nav-cta<?php echo $is_pd_btn ? ' ecm-provider-btn' : ''; ?>"<?php if ( $new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo $label; ?></a>
            <?php endforeach; ?>
        <?php elseif ( ! $nav_links ) : ?>
            <a href="<?php echo esc_url( home_url( '/provider-dashboard/' ) ); ?>" class="nav-cta ecm-provider-btn"><?php echo is_user_logged_in() ? 'Provider Dashboard' : 'Provider Login'; ?></a>
        <?php endif; ?>
    </div>
</nav>
<script>
(function () {
    function ecmUpdateProviderBtn() {
        var btn = document.querySelector('a.ecm-provider-btn');
        if (!btn) return;
        var loggedIn = document.body.classList.contains('logged-in');
        btn.textContent = loggedIn ? 'Provider Dashboard' : 'Provider Login';
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ecmUpdateProviderBtn);
    } else {
        ecmUpdateProviderBtn();
    }
})();
</script>

<?php
$loc_modal_title       = ecm_get_field( 'location_modal_title', 'Set your location' );
$loc_modal_sub         = ecm_get_field( 'location_modal_sub', 'Searching for a parent in a different city? Enter their location.' );
$loc_modal_city_ph     = ecm_get_field( 'location_modal_city_placeholder', 'City or ZIP code…' );
$loc_modal_divider     = ecm_get_field( 'location_modal_divider', 'or' );
$loc_modal_cancel      = ecm_get_field( 'location_modal_cancel', 'Cancel' );
$loc_modal_submit      = ecm_get_field( 'location_modal_submit', 'Update Location' );
?>

<!-- Location Modal -->
<div class="modal-overlay" id="locationModal">
    <div class="modal">
        <h3><?php echo esc_html( $loc_modal_title ); ?></h3>
        <p><?php echo esc_html( $loc_modal_sub ); ?></p>
        <select name="state">
            <option value="">Select state…</option>
            <option>Alabama</option><option>Alaska</option><option>Arizona</option>
            <option>Arkansas</option><option>California</option><option>Colorado</option>
            <option>Connecticut</option><option>Delaware</option><option>Florida</option>
            <option>Georgia</option><option>Hawaii</option><option>Idaho</option>
            <option>Illinois</option><option>Indiana</option><option>Iowa</option>
            <option>Kansas</option><option>Kentucky</option><option>Louisiana</option>
            <option>Maine</option><option>Maryland</option><option>Massachusetts</option>
            <option>Michigan</option><option>Minnesota</option><option>Mississippi</option>
            <option>Missouri</option><option>Montana</option><option>Nebraska</option>
            <option>Nevada</option><option>New Hampshire</option><option>New Jersey</option>
            <option>New Mexico</option><option>New York</option><option>North Carolina</option>
            <option>North Dakota</option><option>Ohio</option><option>Oklahoma</option>
            <option>Oregon</option><option>Pennsylvania</option><option>Rhode Island</option>
            <option>South Carolina</option><option>South Dakota</option><option>Tennessee</option>
            <option>Texas</option><option>Utah</option><option>Vermont</option>
            <option>Virginia</option><option>Washington</option><option>West Virginia</option>
            <option>Wisconsin</option><option>Wyoming</option>
        </select>
        <div class="modal-divider"><?php echo esc_html( $loc_modal_divider ); ?></div>
        <input type="text" name="zip" placeholder="<?php echo esc_attr( $loc_modal_city_ph ); ?>">
        <div class="modal-actions">
            <button class="btn-outline" type="button" data-modal-cancel><?php echo esc_html( $loc_modal_cancel ); ?></button>
            <button class="btn-sage" type="button" data-modal-submit><?php echo esc_html( $loc_modal_submit ); ?></button>
        </div>
    </div>
</div>

<!-- Quick Form Modal (7-step intake) -->
<?php
$form_close_icon = ecm_get_field( 'form_modal_close_icon', '×' );
$steps_total     = ecm_get_field( 'chat_steps_total', '7' );
?>
<div class="modal-overlay form-modal" id="formModal">
    <div class="modal modal-lg">
        <button class="modal-close" type="button" data-close-form-modal aria-label="Close"><?php echo esc_html( $form_close_icon ); ?></button>
        <div class="intake-stepper">
            <div class="intake-stepper-progress">
                <div class="step-bar"><div class="step-bar-fill" id="stepBarFill"></div></div>
                <span class="step-label" id="stepLabel">Step 1 of <?php echo esc_html( $steps_total ); ?></span>
            </div>
            <div id="intakeStepContent"><!-- rendered by intake.js --></div>
        </div>
    </div>
</div>

<!-- Floating Chat FAB + Popup -->
<?php
$advisor_name   = ecm_get_field( 'chat_advisor_name', 'ECM Care Advisor' );
$advisor_status = ecm_get_field( 'chat_advisor_status', 'Online · Avg reply < 1 min' );
$avatar_emoji   = ecm_get_field( 'chat_avatar_emoji', '👩‍⚕️' );
$fab_icon       = ecm_get_field( 'chat_fab_icon', '💬' );
$fab_close_icon = ecm_get_field( 'chat_fab_close_icon', '×' );
?>
<button class="chat-fab" id="chatFab" type="button" aria-label="Chat with our care advisor">
    <span class="chat-fab-icon chat-fab-icon--chat"><?php echo esc_html( $fab_icon ); ?></span>
    <span class="chat-fab-icon chat-fab-icon--close"><?php echo esc_html( $fab_close_icon ); ?></span>
    <span class="chat-fab-pulse"></span>
</button>

<div class="chat-popup" id="chatPopup">
    <div class="chat-popup-header">
        <div class="chat-avatar-sm"><?php echo esc_html( $avatar_emoji ); ?></div>
        <div class="chat-meta">
            <div class="chat-meta-name"><?php echo esc_html( $advisor_name ); ?></div>
            <div class="chat-meta-status"><span class="status-dot"></span> <?php echo esc_html( $advisor_status ); ?></div>
        </div>
        <div class="chat-step-badge">Step 1 of <?php echo esc_html( $steps_total ); ?></div>
        <button class="chat-popup-close" type="button" data-close-chat aria-label="Close chat"><?php echo esc_html( $fab_close_icon ); ?></button>
    </div>
    <div class="chat-body"><!-- built by intake.js --></div>
</div>
