<?php
/**
 * Free tier listing — template-part.
 *
 * Rebuilt against the confirmed reference design: dashed "FREE LISTING"
 * badge, no sidebar (full-width single column), a "Request Help"
 * button linked to the contact page, and a
 * separate "About [Name]" section below the compact hero — NOT bio/mission
 * crammed inside the hero card the way Standard/Premium still are.
 *
 * All variables ($title, $tier, $location, $cats, $content, etc.) come
 * from the router (single-job_listing.php) via `require` — this file adds
 * no data-fetching of its own.
 *
 * @package ElderCareMatters
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- Provider Hero — Free (no sidebar) -->
<section class="provider-hero provider-hero--no-sidebar">
    <div class="provider-hero-main">

        <span class="plan-hero-badge plan-hero-badge--free" style="margin-bottom:16px;display:inline-block;">FREE LISTING</span>

        <div class="provider-hero-top">
            <?php if ( $logo_url ) : ?>
            <div class="provider-hero-logo" style="background:#fff;overflow:hidden;padding:0">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $title ); ?>"
                     style="width:100%;height:100%;object-fit:cover;border-radius:18px">
            </div>
            <?php else : ?>
            <div class="provider-hero-logo" style="background:<?php echo esc_attr( $logo_bg ); ?>;color:#fff;font-weight:700">
                <?php echo esc_html( $initials ); ?>
            </div>
            <?php endif; ?>

            <div>
                <h1 class="provider-hero-title"><?php echo esc_html( $title ); ?></h1>
                <div class="provider-hero-meta">
                    <?php // No rating badge on Free — star ratings/reviews start at Basic. ?>
                    <?php if ( $location ) : ?>
                    <span class="provider-hero-meta-item">📍 <?php echo esc_html( $location ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.provider-hero-top -->

        <?php if ( $cats ) : ?>
        <div class="provider-specialties" style="margin-bottom:16px">
            <?php foreach ( $cats as $cat ) : ?>
            <span class="specialty-chip"><?php echo esc_html( $cat->name ); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $tagline ) : ?>
        <p class="provider-hero-tagline"><?php echo esc_html( $tagline ); ?></p>
        <?php endif; ?>

        <a href="<?php echo esc_url( home_url( '/contact-elder-care-matters/' ) ); ?>" class="btn-primary-lg" style="margin-top:6px;text-decoration:none;">
            📋 Request Help <span class="btn-arrow">→</span>
        </a>

    </div><!-- /.provider-hero-main -->
</section><!-- /.provider-hero -->

<?php if ( $content ) : ?>
<section class="inner-section">
    <div class="inner-section-header">
        <div><h2>About</h2></div>
    </div>
    <div class="provider-hero-about" style="max-width:900px;">
        <?php echo wp_kses_post( wpautop( $content ) ); ?>
    </div>
</section>
<?php endif; ?>
