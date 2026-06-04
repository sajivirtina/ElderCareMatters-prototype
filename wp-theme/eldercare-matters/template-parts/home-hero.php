<?php
/**
 * Hero section — all content from ACF "Hero Section" tab.
 */

$stamp_top    = ecm_get_field( 'hero_stamp_arc_top',    '· ALL 50 STATES ·' );
$stamp_bottom = ecm_get_field( 'hero_stamp_arc_bottom', '· TRUSTED CARE MATCHING ·' );
$stamp_year   = ecm_get_field( 'hero_stamp_year',       '2002' );

$title_prefix     = ecm_get_field( 'hero_title_prefix',    'Find Trusted Elder Care in' );
$subtitle         = ecm_get_field( 'hero_subtitle',        'One need. One location. Matched in under 2 minutes. Our free care advisor connects you with verified local providers — no spam, no obligation.' );

$cta1_icon  = ecm_get_field( 'hero_cta_primary_icon',    '📋' );
$cta1_text  = ecm_get_field( 'hero_cta_primary_text',    'Start Your Free Match' );
$cta2_icon  = ecm_get_field( 'hero_cta_secondary_icon',  '💬' );
$cta2_text  = ecm_get_field( 'hero_cta_secondary_text',  'Prefer to chat? Talk to Carrie' );

$badges           = get_field( 'hero_badges' );
$quick_cats_label = ecm_get_field( 'hero_quick_cats_label', 'Quick start:' );
$quick_cats       = get_field( 'hero_quick_cats' );

$trust_year = ecm_get_field( 'hero_trust_year', '2002' );
$trust_text = ecm_get_field( 'hero_trust_text', "America's oldest & most respected elder care directory · Operating in all 50 states" );

$hero_img    = get_field( 'hero_image' );
$hero_img_url = $hero_img['url'] ?? 'https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?w=1000&auto=format&fit=crop&q=80';
$hero_img_alt = $hero_img['alt'] ?? 'Compassionate caregiver with elderly patient';

$fc_score     = ecm_get_field( 'hero_fc_rating_score', '4.9' );
$fc_label     = ecm_get_field( 'hero_fc_rating_label', '12k+ families matched' );
$fc_avatars   = get_field( 'hero_fc_avatars' );
$fc_count     = ecm_get_field( 'hero_fc_avatar_count', '+12k' );

$fc_ver_title = ecm_get_field( 'hero_fc_verified_title', '500+ Verified Providers' );
$fc_ver_sub   = ecm_get_field( 'hero_fc_verified_sub',   'Pre-screened & trusted' );

$fc_time_val  = ecm_get_field( 'hero_fc_time_value', '< 2 min' );
$fc_time_lbl  = ecm_get_field( 'hero_fc_time_label', 'to your first match · always free' );

$logo_icon_url = get_template_directory_uri() . '/assets/images/logo-icon.png';
$footer_logo = get_field( 'footer_logo' );
if ( $footer_logo ) { $logo_icon_url = $footer_logo['url']; }
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg-shape"></div>

    <div class="hero-left">
        <!-- Stamp Badge -->
        <div class="badge-wrap">
            <svg class="stamp-svg" viewBox="0 0 220 220" width="120" height="120" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <path id="topArc2" d="M 17,110 A 93,93 0 0,0 203,110"/>
                    <path id="botArc2" d="M 27,110 A 83,83 0 0,1 193,110"/>
                </defs>
                <circle cx="110" cy="110" r="105" fill="none" stroke="#b8860b" stroke-width="3"/>
                <circle cx="110" cy="110" r="98"  fill="none" stroke="#b8860b" stroke-width="1.2"/>
                <circle cx="110" cy="110" r="65"  fill="none" stroke="#b8860b" stroke-width="2"/>
                <circle cx="110" cy="110" r="59"  fill="none" stroke="#b8860b" stroke-width="1"/>
                <text font-family="'DM Sans', Arial, sans-serif" font-size="10.5" font-weight="700"
                      letter-spacing="2.8" fill="#b8860b" text-anchor="middle">
                    <textPath href="#topArc2" startOffset="50%"><?php echo esc_html( $stamp_top ); ?></textPath>
                </text>
                <text font-family="'DM Sans', Arial, sans-serif" font-size="10.5" font-weight="700"
                      letter-spacing="3.5" fill="#b8860b" text-anchor="middle">
                    <textPath href="#botArc2" startOffset="50%"><?php echo esc_html( $stamp_bottom ); ?></textPath>
                </text>
                <text x="110" y="50" text-anchor="middle"
                      font-family="Arial" font-size="9" fill="#b8860b" letter-spacing="7">★  ★  ★</text>
                <text x="110" y="172" text-anchor="middle"
                      font-family="Arial" font-size="9" fill="#b8860b" letter-spacing="7">★  ★  ★</text>
                <line x1="73" y1="98" x2="147" y2="98" stroke="#b8860b" stroke-width="0.8" opacity="0.45"/>
                <line x1="73" y1="140" x2="147" y2="140" stroke="#b8860b" stroke-width="0.8" opacity="0.45"/>
                <text x="110" y="114" text-anchor="middle"
                      font-family="'DM Sans', Arial, sans-serif" font-size="9.5" font-weight="700"
                      letter-spacing="5" fill="#b8860b">SINCE</text>
                <text x="110" y="137" text-anchor="middle"
                      font-family="'Playfair Display', Georgia, serif" font-size="34" font-weight="700"
                      fill="#b8860b"><?php echo esc_html( $stamp_year ); ?></text>
            </svg>
        </div>

        <!-- Headline -->
        <h1 class="hero-title">
            <?php echo esc_html( $title_prefix ); ?>
            <span style="white-space:nowrap">
                <em class="js-location-city">your area</em>
                <button type="button" class="location-edit-pin" data-open-location-modal>📍</button>
            </span>
        </h1>

        <!-- Subtitle -->
        <p class="hero-sub"><?php echo esc_html( $subtitle ); ?></p>

        <!-- CTA Buttons -->
        <div class="hero-cta-row">
            <button type="button" class="btn-primary-lg" data-open-form-modal>
                <?php if ( $cta1_icon ) : ?><?php echo esc_html( $cta1_icon ); ?> <?php endif; ?>
                <?php echo esc_html( $cta1_text ); ?>
                <span class="btn-arrow">→</span>
            </button>
            <button type="button" class="btn-text" data-open-chat>
                <?php if ( $cta2_icon ) : ?><?php echo esc_html( $cta2_icon ); ?> <?php endif; ?>
                <?php echo esc_html( $cta2_text ); ?>
            </button>
        </div>

        <!-- Trust Badges -->
        <?php if ( $badges ) : ?>
        <div class="hero-badges">
            <?php foreach ( $badges as $badge ) : ?>
            <div class="hero-badge">
                <div class="hero-badge-dot"></div>
                <?php echo esc_html( $badge['text'] ?? '' ); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="hero-badges">
            <div class="hero-badge"><div class="hero-badge-dot"></div> Free for families</div>
            <div class="hero-badge"><div class="hero-badge-dot"></div> Providers pre-screened</div>
            <div class="hero-badge"><div class="hero-badge-dot"></div> No spam, ever</div>
        </div>
        <?php endif; ?>

        <!-- Quick-start category chips -->
        <div class="hero-quick-cats">
            <span class="quick-cats-label"><?php echo esc_html( $quick_cats_label ); ?></span>
            <?php if ( $quick_cats ) : ?>
                <?php foreach ( $quick_cats as $cat ) :
                    $slug  = sanitize_title( $cat['slug'] ?? '' );
                    $emoji = $cat['emoji'] ?? '';
                    $label = $cat['label'] ?? '';
                ?>
                <a class="quick-cat js-category-card"
                   data-category="<?php echo esc_attr( $slug ); ?>"
                   href="<?php echo esc_url( home_url( '/category/?type=' . $slug ) ); ?>">
                    <?php echo esc_html( $emoji . ' ' . $label ); ?>
                </a>
                <?php endforeach; ?>
            <?php else : ?>
                <a class="quick-cat js-category-card" data-category="home-care" href="<?php echo esc_url( home_url( '/category/?type=home-care' ) ); ?>">🏠 Home Care</a>
                <a class="quick-cat js-category-card" data-category="memory-care" href="<?php echo esc_url( home_url( '/category/?type=memory-care' ) ); ?>">🧠 Memory Care</a>
                <a class="quick-cat js-category-card" data-category="assisted-living" href="<?php echo esc_url( home_url( '/category/?type=assisted-living' ) ); ?>">🏡 Assisted Living</a>
                <a class="quick-cat js-category-card" data-category="elder-law" href="<?php echo esc_url( home_url( '/category/?type=elder-law' ) ); ?>">⚖️ Elder Law</a>
            <?php endif; ?>
        </div>

        <!-- Trust line -->
        <div class="hero-since">
            <img src="<?php echo esc_url( $logo_icon_url ); ?>" alt="ECM" style="height:38px;width:auto;flex-shrink:0;">
            <span style="font-size:0.8rem;color:var(--muted);line-height:1.6;">
                Trusted since <strong style="color:var(--charcoal);font-weight:600;"><?php echo esc_html( $trust_year ); ?></strong>
                &nbsp;·&nbsp; <?php echo esc_html( $trust_text ); ?>
            </span>
        </div>
    </div><!-- /.hero-left -->

    <!-- Hero right — photo + floating cards -->
    <div class="hero-right">
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-photo">
                <img src="<?php echo esc_url( $hero_img_url ); ?>" alt="<?php echo esc_attr( $hero_img_alt ); ?>">
            </div>

            <!-- Floating card 1: Rating -->
            <div class="hero-float-card hero-float-card--rating">
                <div class="hero-float-stars">★★★★★</div>
                <div class="hero-float-title">
                    <strong><?php echo esc_html( $fc_score ); ?></strong>
                    · <?php echo esc_html( $fc_label ); ?>
                </div>
                <div class="hero-float-avatars">
                    <?php if ( $fc_avatars ) : ?>
                        <?php foreach ( $fc_avatars as $av ) :
                            $av_url = $av['image']['url'] ?? '';
                        ?>
                        <?php if ( $av_url ) : ?>
                        <span class="hero-float-avatar"
                              style="background-image:url('<?php echo esc_url( $av_url ); ?>')"></span>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span class="hero-float-avatar" style="background-image:url('https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=80&auto=format&fit=crop')"></span>
                        <span class="hero-float-avatar" style="background-image:url('https://images.unsplash.com/photo-1552058544-f2b08422138a?w=80&auto=format&fit=crop')"></span>
                        <span class="hero-float-avatar" style="background-image:url('https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&auto=format&fit=crop')"></span>
                    <?php endif; ?>
                    <span class="hero-float-avatar hero-float-avatar--more">
                        <?php echo esc_html( $fc_count ); ?>
                    </span>
                </div>
            </div>

            <!-- Floating card 2: Verified -->
            <div class="hero-float-card hero-float-card--verified">
                <div class="hero-float-badge-icon">✓</div>
                <div>
                    <div class="hero-float-badge-title"><?php echo esc_html( $fc_ver_title ); ?></div>
                    <div class="hero-float-badge-sub"><?php echo esc_html( $fc_ver_sub ); ?></div>
                </div>
            </div>

            <!-- Floating card 3: Time to match -->
            <div class="hero-float-card hero-float-card--matched">
                <div class="hero-float-stat">
                    <span class="hero-float-emoji">⏱</span>
                    <strong><?php echo esc_html( $fc_time_val ); ?></strong>
                </div>
                <div class="hero-float-stat-label"><?php echo esc_html( $fc_time_lbl ); ?></div>
            </div>
        </div>
    </div><!-- /.hero-right -->
</section>
