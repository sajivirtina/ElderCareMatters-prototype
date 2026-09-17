<?php
/**
 * Template Name: ECM — Blog / Resources
 *
 * Resources & Guides listing. Featured post + grid come from real WordPress
 * Posts (post_type=post). Hero, filter chips, cities, and newsletter are
 * ACF-editable with sensible fallbacks.
 */

get_header();

// ── Real WP posts ─────────────────────────────────────────────────────────────

// Featured: sticky post first, then most recent.
$sticky_ids = get_option( 'sticky_posts', [] );
$featured_post = null;

if ( $sticky_ids ) {
    $q_sticky = new WP_Query( [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'post__in'            => $sticky_ids,
        'posts_per_page'      => 1,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ] );
    if ( $q_sticky->have_posts() ) {
        $featured_post = $q_sticky->posts[0];
    }
    wp_reset_postdata();
}

if ( ! $featured_post ) {
    $q_first = new WP_Query( [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 1,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ] );
    if ( $q_first->have_posts() ) {
        $featured_post = $q_first->posts[0];
    }
    wp_reset_postdata();
}

// Grid: next 9 posts (exclude featured).
$exclude_ids = $featured_post ? [ $featured_post->ID ] : [];
$q_grid = new WP_Query( [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 9,
    'post__not_in'        => $exclude_ids,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'update_post_meta_cache' => true,
    'update_post_term_cache' => true,
] );
$grid_posts = $q_grid->posts;
wp_reset_postdata();

$has_real_posts = ( $featured_post || ! empty( $grid_posts ) );

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Map a category slug/name to [ blog-cat class, icon emoji, bg colour ].
 */
function ecm_blog_cat_meta( WP_Term $term ) : array {
    $slug = $term->slug;
    $name = strtolower( $term->name );
    $map = [
        'home-care'       => [ 'blog-cat--home-care',       '🏠', '#e6f4ea' ],
        'assisted-living' => [ 'blog-cat--assisted-living',  '🏢', '#e8f0fe' ],
        'memory-care'     => [ 'blog-cat--memory-care',      '🧠', '#fce8d5' ],
        'legal'           => [ 'blog-cat--legal',            '⚖️', '#f3e8fd' ],
        'financial'       => [ 'blog-cat--legal',            '⚖️', '#f3e8fd' ],
        'elder-law'       => [ 'blog-cat--legal',            '⚖️', '#f3e8fd' ],
        'hospice'         => [ 'blog-cat--hospice',          '🕊️', '#fde8ee' ],
        'palliative'      => [ 'blog-cat--hospice',          '🕊️', '#fde8ee' ],
        'checklist'       => [ 'blog-cat--checklist',        '✅', '#e8fdf5' ],
        'checklists'      => [ 'blog-cat--checklist',        '✅', '#e8fdf5' ],
    ];
    foreach ( $map as $key => $val ) {
        if ( str_contains( $slug, $key ) || str_contains( $name, $key ) ) {
            return $val;
        }
    }
    return [ 'blog-cat--home-care', '📰', '#e6f4ea' ];
}

function ecm_post_read_time( WP_Post $post ) : string {
    $words = str_word_count( wp_strip_all_tags( $post->post_content ) );
    $mins  = max( 1, (int) round( $words / 200 ) );
    return $mins . ' min read';
}

function ecm_post_primary_cat( int $post_id ) : ?WP_Term {
    $cats = get_the_terms( $post_id, 'category' );
    if ( is_wp_error( $cats ) || ! $cats ) return null;
    foreach ( $cats as $cat ) {
        if ( 'uncategorized' !== $cat->slug ) return $cat;
    }
    return $cats[0];
}

// ── ACF / fallback fields ─────────────────────────────────────────────────────

$eyebrow    = ecm_get_field( 'blog_eyebrow', '📚 Elder Care Resources' );
$hero_title = ecm_get_field( 'blog_title', 'Resources &amp; <em>Guides</em>' );
$hero_sub   = ecm_get_field( 'blog_sub', 'Expert-written articles to help families navigate every stage of elder care, from the first conversation to finding the right provider.' );
$stats      = get_field( 'blog_stats' );
$grid_sub   = ecm_get_field( 'blog_grid_sub', 'Practical articles written by elder care advisors and verified providers.' );
$cities     = get_field( 'blog_cities' );
$cities_sub = ecm_get_field( 'blog_cities_sub', 'Find elder care guides, provider directories, and local resources near you.' );
$n_title    = ecm_get_field( 'blog_news_title', 'Get guides delivered to your <em>inbox</em>' );
$n_sub      = ecm_get_field( 'blog_news_sub', 'Join 12,000+ families receiving our free weekly elder care digest. No spam, unsubscribe any time.' );
$n_btn      = ecm_get_field( 'blog_news_btn', 'Subscribe Free' );
$n_fine     = ecm_get_field( 'blog_news_fine', 'We respect your privacy. Unsubscribe anytime.' );
$cb_title   = ecm_get_field( 'blog_cb_title', "Ready to find care? <em>We'll match you.</em>" );
$cb_sub     = ecm_get_field( 'blog_cb_sub', "Tell us what you need, we'll hand-pick up to 3 verified providers near you." );
$cb_primary = ecm_get_field( 'blog_cb_primary', '📋 Start Your Free Match →' );
$cb_secondary = ecm_get_field( 'blog_cb_secondary', '💬 Chat with advisor' );

$em_kses = [ 'em' => [] ];

if ( ! $stats ) {
    $stats = [
        [ 'value' => '50+',  'label' => 'Expert Guides' ],
        [ 'value' => 'Free', 'label' => 'Always Free' ],
        [ 'value' => '6',    'label' => 'Care Categories' ],
        [ 'value' => '50+',  'label' => 'Cities Covered' ],
    ];
}
if ( ! $cities ) {
    $search_url = home_url( '/find-care/' );
    $cat_url = fn( $city ) => home_url( '/category/?type=home-care&city=' . $city );
    $cities = [
        [ 'label' => '📍 Dallas, TX',       'url' => $cat_url( 'dallas' ) ],
        [ 'label' => '📍 Fort Worth, TX',   'url' => $cat_url( 'fort-worth' ) ],
        [ 'label' => '📍 Plano, TX',        'url' => $cat_url( 'plano' ) ],
        [ 'label' => '📍 Arlington, TX',    'url' => $cat_url( 'arlington' ) ],
        [ 'label' => '📍 Austin, TX',       'url' => $cat_url( 'austin' ) ],
        [ 'label' => '📍 Houston, TX',      'url' => $cat_url( 'houston' ) ],
        [ 'label' => '📍 San Antonio, TX',  'url' => $search_url ],
        [ 'label' => '📍 Phoenix, AZ',      'url' => $search_url ],
        [ 'label' => '📍 Denver, CO',       'url' => $search_url ],
        [ 'label' => '📍 Chicago, IL',      'url' => $search_url ],
        [ 'label' => '📍 Miami, FL',        'url' => $search_url ],
        [ 'label' => '📍 Atlanta, GA',      'url' => $search_url ],
        [ 'label' => '📍 Seattle, WA',      'url' => $search_url ],
        [ 'label' => '📍 Boston, MA',       'url' => $search_url ],
        [ 'label' => '📍 Los Angeles, CA',  'url' => $search_url ],
        [ 'label' => '📍 New York, NY',     'url' => $search_url ],
    ];
}

// Build real category filter chips from published post categories.
$filter_terms = get_terms( [
    'taxonomy'   => 'category',
    'hide_empty' => true,
    'exclude'    => get_option( 'default_category' ), // exclude Uncategorized
    'number'     => 12,
    'orderby'    => 'count',
    'order'      => 'DESC',
] );
$filter_chips = [];
if ( ! is_wp_error( $filter_terms ) ) {
    foreach ( $filter_terms as $ft ) {
        [ $cls, $icon ] = ecm_blog_cat_meta( $ft );
        $filter_chips[] = [ 'label' => $icon . ' ' . $ft->name, 'slug' => $ft->slug ];
    }
}
// Prepend "All Guides" chip.
array_unshift( $filter_chips, [ 'label' => 'All Guides', 'slug' => 'all' ] );

// Fallback hardcoded chips if no categories exist.
if ( count( $filter_chips ) <= 1 ) {
    $filter_chips = [
        [ 'label' => 'All Guides', 'slug' => 'all' ],
        [ 'label' => '🏠 Home Care', 'slug' => 'home-care' ],
        [ 'label' => '🏢 Assisted Living', 'slug' => 'assisted-living' ],
        [ 'label' => '🧠 Memory Care', 'slug' => 'memory-care' ],
        [ 'label' => '⚖️ Legal &amp; Financial', 'slug' => 'legal' ],
        [ 'label' => '🕊️ Hospice', 'slug' => 'hospice' ],
        [ 'label' => '✅ Checklists', 'slug' => 'checklist' ],
    ];
}

// ── Build grid cards ──────────────────────────────────────────────────────────

/**
 * Build a blog-card array from a real WP_Post.
 */
function ecm_post_to_card( WP_Post $post ) : array {
    $cat      = ecm_post_primary_cat( $post->ID );
    [ $cls, $icon, $bg ] = $cat ? ecm_blog_cat_meta( $cat ) : [ 'blog-cat--home-care', '📰', '#e6f4ea' ];
    $cat_name = $cat ? $cat->name : 'Elder Care';

    // Prefer the manual excerpt; generate from content if empty.
    $excerpt = $post->post_excerpt
        ? $post->post_excerpt
        : wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '…' );

    return [
        'icon'      => $icon,
        'icon_bg'   => $bg,
        'category'  => $cat_name,
        'cat_class' => $cls,
        'title'     => $post->post_title,
        'excerpt'   => $excerpt,
        'meta'      => get_the_date( 'M j, Y', $post ) . ' · ' . ecm_post_read_time( $post ),
        'link'      => get_permalink( $post ),
    ];
}

$real_cards = [];
foreach ( $grid_posts as $p ) {
    $real_cards[] = ecm_post_to_card( $p );
}

// Fallback hardcoded grid cards when no posts exist.
$detail_url = home_url( '/resources/' );
$fallback_cards = [
    [ 'icon' => '🏠', 'icon_bg' => '#e6f4ea', 'category' => 'Home Care', 'cat_class' => 'blog-cat--home-care', 'title' => 'How to Choose a Home Care Agency', 'excerpt' => 'A step-by-step checklist for vetting agencies, asking the right questions, and avoiding common pitfalls families face.', 'meta' => 'Mar 10, 2026 · 6 min read', 'link' => $detail_url ],
    [ 'icon' => '🏢', 'icon_bg' => '#e8f0fe', 'category' => 'Assisted Living', 'cat_class' => 'blog-cat--assisted-living', 'title' => "Signs It's Time for Assisted Living", 'excerpt' => 'Recognizing the signs that a parent needs more support than home care can provide, and how to start that conversation.', 'meta' => 'Mar 8, 2026 · 5 min read', 'link' => $detail_url ],
    [ 'icon' => '🧠', 'icon_bg' => '#fce8d5', 'category' => 'Memory Care', 'cat_class' => 'blog-cat--memory-care', 'title' => 'Understanding Dementia Stages', 'excerpt' => 'A plain-language overview of the seven stages of dementia, what each looks like, and how care needs change over time.', 'meta' => 'Mar 5, 2026 · 7 min read', 'link' => $detail_url ],
    [ 'icon' => '⚖️', 'icon_bg' => '#f3e8fd', 'category' => 'Legal &amp; Financial', 'cat_class' => 'blog-cat--legal', 'title' => 'Medicare vs Medicaid Explained', 'excerpt' => 'The key differences between Medicare and Medicaid, what each covers for elder care, and how to apply for benefits.', 'meta' => 'Mar 3, 2026 · 8 min read', 'link' => $detail_url ],
    [ 'icon' => '🕊️', 'icon_bg' => '#fde8ee', 'category' => 'Hospice', 'cat_class' => 'blog-cat--hospice', 'title' => 'What Hospice Care Really Means', 'excerpt' => 'Dispelling common myths about hospice, when to consider it, and how it can improve quality of life for your loved one.', 'meta' => 'Feb 28, 2026 · 5 min read', 'link' => $detail_url ],
    [ 'icon' => '✅', 'icon_bg' => '#e8fdf5', 'category' => 'Checklist', 'cat_class' => 'blog-cat--checklist', 'title' => 'Moving Your Parent: The Ultimate Checklist', 'excerpt' => 'A 40-point checklist covering everything from sorting belongings to notifying Medicare when transitioning to a care facility.', 'meta' => 'Feb 24, 2026 · 4 min read', 'link' => $detail_url ],
    [ 'icon' => '🏠', 'icon_bg' => '#e6f4ea', 'category' => 'Home Care', 'cat_class' => 'blog-cat--home-care', 'title' => 'In-Home vs. Facility Care: Pros &amp; Cons', 'excerpt' => 'A balanced comparison to help families decide whether aging in place or transitioning to a facility is the right choice.', 'meta' => 'Feb 20, 2026 · 6 min read', 'link' => $detail_url ],
    [ 'icon' => '🧠', 'icon_bg' => '#fce8d5', 'category' => 'Memory Care', 'cat_class' => 'blog-cat--memory-care', 'title' => 'Talking to a Parent About Memory Loss', 'excerpt' => 'Scripts, timing, and framing strategies from care managers who have this difficult conversation every week.', 'meta' => 'Feb 17, 2026 · 5 min read', 'link' => $detail_url ],
    [ 'icon' => '⚖️', 'icon_bg' => '#f3e8fd', 'category' => 'Legal &amp; Financial', 'cat_class' => 'blog-cat--legal', 'title' => 'Power of Attorney: A Step-by-Step Guide', 'excerpt' => 'How to establish durable power of attorney for an aging parent, what documents you need, and common mistakes to avoid.', 'meta' => 'Feb 12, 2026 · 7 min read', 'link' => $detail_url ],
];

$cards = ! empty( $real_cards ) ? $real_cards : $fallback_cards;

?>

<!-- Blog Hero -->
<section class="blog-hero">
    <div class="blog-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
    <h1 class="blog-hero-title"><?php echo wp_kses( $hero_title, $em_kses ); ?></h1>
    <p class="blog-hero-sub"><?php echo esc_html( $hero_sub ); ?></p>
    <div class="blog-stat-bar">
        <?php foreach ( $stats as $s ) : ?>
        <div class="blog-stat">
            <span class="blog-stat-val"><?php echo esc_html( $s['value'] ?? '' ); ?></span>
            <div class="blog-stat-label"><?php echo esc_html( $s['label'] ?? '' ); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Category Filter Bar + Featured -->
<section class="inner-section inner-section--warm" style="padding-top:28px">
    <div class="blog-filter-bar" style="padding-left:0;padding-right:0">
        <?php foreach ( $filter_chips as $i => $chip ) : ?>
        <button class="filter-chip<?php echo $i === 0 ? ' active' : ''; ?>" data-blog-filter="<?php echo esc_attr( $chip['slug'] ); ?>">
            <?php echo wp_kses( $chip['label'], $em_kses ); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Featured / Editor's Pick -->
    <?php if ( $featured_post ) :
        $fp_cat = ecm_post_primary_cat( $featured_post->ID );
        [ $fp_cls, $fp_icon, $fp_bg ] = $fp_cat ? ecm_blog_cat_meta( $fp_cat ) : [ 'blog-cat--home-care', '🏠', '#e6f4ea' ];
        $fp_thumb = get_the_post_thumbnail_url( $featured_post->ID, 'large' );
        $fp_excerpt = $featured_post->post_excerpt
            ?: wp_trim_words( wp_strip_all_tags( $featured_post->post_content ), 35, '…' );
        $fp_cat_name = $fp_cat ? $fp_cat->name : 'Elder Care';
    ?>
    <a class="blog-featured" href="<?php echo esc_url( get_permalink( $featured_post ) ); ?>">
        <?php if ( $fp_thumb ) : ?>
        <div class="blog-featured-img blog-featured-img--photo">
            <img src="<?php echo esc_url( $fp_thumb ); ?>" alt="<?php echo esc_attr( $featured_post->post_title ); ?>" loading="eager">
        </div>
        <?php else : ?>
        <div class="blog-featured-img" style="background:<?php echo esc_attr( $fp_bg ); ?>"><?php echo esc_html( $fp_icon ); ?></div>
        <?php endif; ?>
        <div class="blog-featured-body">
            <div class="blog-featured-badge">⭐ Editor's Pick</div>
            <div class="blog-featured-title"><?php echo esc_html( $featured_post->post_title ); ?></div>
            <p class="blog-featured-excerpt"><?php echo esc_html( $fp_excerpt ); ?></p>
            <div class="blog-featured-meta">
                <span><?php echo esc_html( get_the_date( 'F j, Y', $featured_post ) ); ?></span>
                <span class="blog-featured-meta-sep">·</span>
                <span><?php echo esc_html( ecm_post_read_time( $featured_post ) ); ?></span>
                <span class="blog-featured-meta-sep">·</span>
                <span><?php echo esc_html( $fp_cat_name ); ?></span>
            </div>
            <div class="blog-featured-cta">Read the guide →</div>
        </div>
    </a>
    <?php else :
        // Fallback static featured card.
        $f_icon  = ecm_get_field( 'blog_feat_icon', '🏠' );
        $f_badge = ecm_get_field( 'blog_feat_badge', "⭐ Editor's Pick" );
        $f_title = ecm_get_field( 'blog_feat_title', 'The Complete Guide to Home Care in 2026' );
        $f_excerpt = ecm_get_field( 'blog_feat_excerpt', 'Everything a family needs to know before hiring a home care agency, from vetting credentials to understanding Medicare coverage and what to expect on day one.' );
        $f_meta  = ecm_get_field( 'blog_feat_meta', 'March 15, 2026 · 8 min read · Home Care' );
        $f_cta   = ecm_get_field( 'blog_feat_cta', 'Read the guide →' );
        $f_link  = ecm_get_field( 'blog_feat_link', $detail_url );
    ?>
    <a class="blog-featured" href="<?php echo esc_url( $f_link ); ?>">
        <div class="blog-featured-img"><?php echo esc_html( $f_icon ); ?></div>
        <div class="blog-featured-body">
            <div class="blog-featured-badge"><?php echo esc_html( $f_badge ); ?></div>
            <div class="blog-featured-title"><?php echo esc_html( $f_title ); ?></div>
            <p class="blog-featured-excerpt"><?php echo esc_html( $f_excerpt ); ?></p>
            <div class="blog-featured-meta">
                <?php
                $parts = array_map( 'trim', explode( '·', $f_meta ) );
                foreach ( $parts as $j => $p ) :
                    if ( $j > 0 ) echo '<span class="blog-featured-meta-sep">·</span>';
                    echo '<span>' . esc_html( $p ) . '</span>';
                endforeach;
                ?>
            </div>
            <div class="blog-featured-cta"><?php echo esc_html( $f_cta ); ?></div>
        </div>
    </a>
    <?php endif; ?>
</section>

<!-- Blog Grid -->
<section class="inner-section inner-section--cream">
    <div class="inner-section-header">
        <div>
            <h2>Latest <em>Guides</em></h2>
            <p><?php echo esc_html( $grid_sub ); ?></p>
        </div>
        <div class="inner-section-aside"><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">View all guides →</a></div>
    </div>
    <div class="blog-grid" id="blog-grid">
        <?php foreach ( $cards as $c ) : ?>
        <a class="blog-card" href="<?php echo esc_url( $c['link'] ); ?>" data-cat="<?php echo esc_attr( $c['cat_class'] ); ?>">
            <div class="blog-card-icon" style="background:<?php echo esc_attr( $c['icon_bg'] ?? '#e6f4ea' ); ?>"><?php echo esc_html( $c['icon'] ?? '' ); ?></div>
            <div class="blog-card-body">
                <span class="blog-card-cat <?php echo esc_attr( $c['cat_class'] ?? 'blog-cat--home-care' ); ?>"><?php echo wp_kses( $c['category'] ?? '', $em_kses ); ?></span>
                <div class="blog-card-title"><?php echo esc_html( $c['title'] ?? '' ); ?></div>
                <p class="blog-card-excerpt"><?php echo esc_html( $c['excerpt'] ?? '' ); ?></p>
            </div>
            <div class="blog-card-footer">
                <span><?php echo esc_html( $c['meta'] ?? '' ); ?></span>
                <span class="blog-card-footer-link">Read more →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Resources by City -->
<section class="inner-section inner-section--warm">
    <div class="inner-section-header">
        <div>
            <h2>Resources <em>by City</em></h2>
            <p><?php echo esc_html( $cities_sub ); ?></p>
        </div>
    </div>
    <div class="blog-city-grid">
        <?php foreach ( $cities as $city ) : ?>
        <a class="blog-city-link" href="<?php echo esc_url( $city['url'] ?? '#' ); ?>"><?php echo esc_html( $city['label'] ?? '' ); ?></a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Newsletter -->
<div class="blog-subscribe">
    <div>
        <h3><?php echo wp_kses( $n_title, $em_kses ); ?></h3>
        <p><?php echo esc_html( $n_sub ); ?></p>
    </div>
    <div>
        <form class="blog-subscribe-form" onsubmit="return false;">
            <input type="email" class="blog-subscribe-input" placeholder="Your email address…">
            <button type="submit" class="blog-subscribe-btn"><?php echo esc_html( $n_btn ); ?></button>
        </form>
        <p style="font-size:0.72rem;color:rgba(255,255,255,0.35);margin-top:10px"><?php echo esc_html( $n_fine ); ?></p>
    </div>
</div>

<!-- CTA band -->
<div class="cta-band">
    <div>
        <h2><?php echo wp_kses( $cb_title, $em_kses ); ?></h2>
        <p><?php echo esc_html( $cb_sub ); ?></p>
    </div>
    <div class="cta-band-actions">
        <button type="button" class="btn-primary-lg" data-open-form-modal><?php echo esc_html( $cb_primary ); ?></button>
        <button type="button" class="btn-ghost" data-open-chat style="color:#fff;border-color:rgba(255,255,255,0.3);"><?php echo esc_html( $cb_secondary ); ?></button>
    </div>
</div>

<script>
(function () {
    var grid = document.getElementById('blog-grid');
    if (!grid) return;
    document.querySelectorAll('[data-blog-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-blog-filter]').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var filter = btn.dataset.blogFilter;
            grid.querySelectorAll('.blog-card').forEach(function (card) {
                if (filter === 'all') {
                    card.style.display = '';
                } else {
                    card.style.display = card.dataset.cat && card.dataset.cat.indexOf(filter) !== -1 ? '' : 'none';
                }
            });
        });
    });
})();
</script>

<?php get_footer(); ?>
