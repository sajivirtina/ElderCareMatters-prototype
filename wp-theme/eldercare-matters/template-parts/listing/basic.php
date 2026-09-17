<?php
/**
 * Basic tier listing — template-part.
 *
 * Rebuilt against the confirmed reference design. New vs. the old
 * single-file version: a "Services" section with locked/teaser copy
 * (category chips only, no pricing — pricing detail is Standard/Premium),
 * and a gallery caption calling out the photo limit + upgrade link. Note:
 * "Starting from $X/hr" from the reference design has no backing field yet
 * (lp_starting_price doesn't exist) — the line is guarded to simply not
 * render until that field is added; ask if you want it built.
 *
 * All variables come from the router (single-job_listing.php) via
 * `require` — this file adds no data-fetching of its own.
 *
 * @package ElderCareMatters
 */

defined( 'ABSPATH' ) || exit;

$lp_starting_price = get_field( 'lp_starting_price', $post->ID ); // Not yet a real field — see docblock above.
?>

<!-- Provider Hero — Basic -->
<section class="provider-hero">
    <div class="provider-hero-main">

        <span class="plan-hero-badge plan-hero-badge--basic" style="margin-bottom:16px;display:inline-block;">BASIC PLAN</span>

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
                    <?php if ( $avg_rating > 0 ) : ?>
                    <span class="provider-hero-meta-item">
                        <span style="color:var(--gold)">★</span>
                        <strong><?php echo number_format( $avg_rating, 1 ); ?></strong>
                        <?php if ( $review_count ) : ?>
                        <span style="color:var(--muted)">(<?php echo esc_html( $review_count ); ?> reviews)</span>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ( $location ) : ?>
                    <span class="provider-hero-meta-item">📍 <?php echo esc_html( $location ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.provider-hero-top -->

        <?php if ( $cats ) : ?>
        <div class="provider-specialties" style="margin-bottom:16px">
            <?php foreach ( $cats as $cat ) :
                $icon = $cat_icon_map[ $cat->slug ] ?? '';
            ?>
            <span class="specialty-chip"><?php echo $icon ? esc_html( $icon ) . ' ' : ''; ?><?php echo esc_html( $cat->name ); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $tagline ) : ?>
        <p class="provider-hero-tagline"><?php echo esc_html( $tagline ); ?></p>
        <?php endif; ?>

        <?php if ( $lp_starting_price ) : ?>
        <p class="provider-hero-about">Starting from <strong><?php echo esc_html( $lp_starting_price ); ?></strong></p>
        <?php endif; ?>

    </div><!-- /.provider-hero-main -->

    <div class="provider-side-card">
        <h4>Contact <em><?php echo esc_html( $title ); ?></em></h4>
        <p>Submit a free request — no fees, no obligation.</p>
        <a href="<?php echo esc_url( home_url( '/contact-elder-care-matters/' ) ); ?>" class="btn-primary-lg" style="text-decoration:none;">
            📋 Request Help <span class="btn-arrow">→</span>
        </a>
    </div><!-- /.provider-side-card -->
</section><!-- /.provider-hero -->

<?php if ( $content ) : ?>
<section class="inner-section">
    <div class="inner-section-header">
        <div><h2>About <?php echo esc_html( $title ); ?></h2></div>
    </div>
    <div class="provider-hero-about">
        <?php echo wp_kses_post( wpautop( $content ) ); ?>
    </div>
</section>
<?php endif; ?>

<?php if ( $cats ) : ?>
<section class="inner-section inner-section--warm">
    <div class="inner-section-header">
        <div>
            <h2>Services</h2>
            <p>This provider offers the following care types in <?php echo esc_html( $city ?: 'your area' ); ?>.</p>
        </div>
    </div>
    <div>
        <div class="provider-specialties" style="margin-bottom:16px">
            <?php foreach ( $cats as $cat ) : ?>
            <span class="specialty-chip"><?php echo esc_html( $cat->name ); ?></span>
            <?php endforeach; ?>
        </div>
        <p class="provider-hero-about">
            Detailed service descriptions and pricing are available once this provider claims their full profile.
            <a href="<?php echo esc_url( pd_get_public_add_listing_url() ); ?>" style="color:var(--sage);font-weight:600;">Upgrade plan →</a>
        </p>
    </div>
</section>
<?php endif; ?>

<?php if ( $lp_gallery ) :
    $gallery_count = count( $lp_gallery );
    $grid_class    = $gallery_count >= 2 ? 'gallery-grid--2' : 'gallery-grid--1';
?>
<section class="provider-gallery">
    <div class="inner-section-header">
        <div><h2>Photo Gallery</h2></div>
    </div>
    <div class="gallery-grid <?php echo esc_attr( $grid_class ); ?>">
        <?php foreach ( $lp_gallery as $img ) : ?>
        <div class="gallery-grid-item">
            <img src="<?php echo esc_url( $img['sizes']['medium_large'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ?? $title ); ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <p style="font-size:0.85rem;color:var(--muted);margin-top:10px;">
        🖼️ <?php echo esc_html( count( $lp_gallery ) ); ?> photo<?php echo 1 === count( $lp_gallery ) ? '' : 's'; ?> included on the Basic plan.
        <a href="<?php echo esc_url( pd_get_public_add_listing_url() ); ?>" style="color:var(--sage);font-weight:600;">Upgrade for more →</a>
    </p>
</section>
<?php endif; ?>
