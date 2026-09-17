<?php
/**
 * Footer template — all content from ACF "Footer" tab.
 */

$footer_logo   = get_field( 'footer_logo' );
$logo_url      = $footer_logo['url'] ?? get_template_directory_uri() . '/assets/images/logo.svg';
$logo_alt      = $footer_logo['alt'] ?? 'ElderCareMatters';
$brand_name    = ecm_get_field( 'footer_brand_name', 'ElderCareMatters.com' );
$tagline       = ecm_get_field( 'footer_tagline', 'Connecting families with trusted elder care providers across the United States.' );
$trust_badges  = get_field( 'footer_trust_badges' );
$columns       = get_field( 'footer_columns' );
$copyright     = ecm_get_field( 'footer_copyright', '© ' . date( 'Y' ) . ' ElderCareMatters. All rights reserved.' );
$bottom_links  = get_field( 'footer_bottom_links' );

// Dotiq widget
$dotiq_enabled = get_field( 'dotiq_enabled' );
$dotiq_app_id  = ecm_get_field( 'dotiq_app_id', 'ecm' );
?>

<footer>
    <div class="footer-top">
        <!-- Brand column -->
        <div class="footer-brand">
            <div class="footer-logo">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" class="footer-logo-image">
            </div>
            <p class="footer-tagline"><?php echo esc_html( $tagline ); ?></p>

            <?php if ( $trust_badges ) : ?>
            <div class="footer-trust">
                <?php foreach ( $trust_badges as $badge ) : ?>
                <span class="trust-item">
                    <?php echo esc_html( $badge['icon'] ?? '' ); ?>
                    <?php echo esc_html( $badge['text'] ?? '' ); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <!-- Fallback trust badges -->
            <div class="footer-trust">
                <span class="trust-item">✓ Verified Providers</span>
                <span class="trust-item">🔒 Privacy Protected</span>
                <span class="trust-item">⭐ Free for Families</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Navigation columns -->
        <?php if ( $columns ) : ?>
        <?php foreach ( $columns as $col ) :
            $col_title = esc_html( $col['title'] ?? '' );
            $col_links = $col['links'] ?? [];
        ?>
        <div class="footer-col">
            <div class="footer-col-title"><?php echo $col_title; ?></div>
            <?php if ( $col_links ) : ?>
            <ul class="footer-links">
                <?php foreach ( $col_links as $lnk ) : ?>
                <li>
                    <a href="<?php echo esc_url( $lnk['url'] ?? '#' ); ?>">
                        <?php echo esc_html( $lnk['text'] ?? '' ); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php else : ?>
        <!-- Fallback footer columns -->
        <div class="footer-col">
            <div class="footer-col-title">For Families</div>
            <ul class="footer-links">
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#care-types">Find Providers</a></li>
                <li><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Guides &amp; Resources</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <div class="footer-col-title">For Providers</div>
            <ul class="footer-links">
                <li><a href="<?php echo esc_url( home_url( '/for-providers/' ) ); ?>">Provider Overview</a></li>
                <li><a href="<?php echo esc_url( home_url( '/provider-plans/' ) ); ?>">Pricing &amp; Plans</a></li>
                <li><a href="<?php echo esc_url( home_url( '/provider-dashboard/' ) ); ?>">Provider Login</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <div class="footer-col-title">Browse Care</div>
            <ul class="footer-links">
                <li><a href="#care-types">All Categories</a></li>
                <li><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Elder Care Guides</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div><!-- /.footer-top -->

    <div class="footer-bottom">
        <span class="footer-copy"><?php echo esc_html( $copyright ); ?></span>
        <?php if ( $bottom_links ) : ?>
        <div class="footer-bottom-right">
            <?php foreach ( $bottom_links as $bl ) : ?>
            <a href="<?php echo esc_url( $bl['url'] ?? '#' ); ?>">
                <?php echo esc_html( $bl['text'] ?? '' ); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="footer-bottom-right">
            <a href="#">Free for families</a>
            <a href="#">Verified providers</a>
            <a href="#">No spam</a>
        </div>
        <?php endif; ?>
    </div>
</footer>

<?php if ( $dotiq_enabled !== false ) : ?>
<script src="https://app.dotiq.io/widget/feedback-widget.js"
        data-app="<?php echo esc_attr( $dotiq_app_id ); ?>"></script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
