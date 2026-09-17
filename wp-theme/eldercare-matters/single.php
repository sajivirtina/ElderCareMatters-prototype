<?php
/**
 * Single post template — ECM Resources & Guides.
 *
 * Used for all standard WordPress Posts (post_type=post).
 * Mirrors the blog-detail prototype design with real post data.
 */

get_header();

if ( ! have_posts() ) {
    get_footer();
    return;
}

the_post();

$post_id   = get_the_ID();
$blog_url  = home_url( '/resources/' );
$find_url  = home_url( '/find-care/' );

// Primary category (exclude Uncategorized).
$cats = get_the_terms( $post_id, 'category' );
$primary_cat = null;
if ( $cats && ! is_wp_error( $cats ) ) {
    foreach ( $cats as $c ) {
        if ( 'uncategorized' !== $c->slug ) { $primary_cat = $c; break; }
    }
    if ( ! $primary_cat ) $primary_cat = $cats[0];
}

function ecm_single_cat_meta( ?WP_Term $term ) : array {
    if ( ! $term ) return [ 'blog-cat--home-care', '🏠', '#e6f4ea' ];
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
        if ( str_contains( $slug, $key ) || str_contains( $name, $key ) ) return $val;
    }
    return [ 'blog-cat--home-care', '📰', '#e6f4ea' ];
}

[ $cat_class, $cat_icon, $cat_bg ] = ecm_single_cat_meta( $primary_cat );
$cat_name = $primary_cat ? $primary_cat->name : 'Elder Care';

// Read time.
$words    = str_word_count( wp_strip_all_tags( get_the_content() ) );
$read_min = max( 1, (int) round( $words / 200 ) );

// Author.
$author_name = get_the_author_meta( 'display_name' );

// Featured image.
$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );

// Related posts — same category, exclude current.
$related_posts = [];
if ( $primary_cat ) {
    $q_rel = new WP_Query( [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'post__not_in'        => [ $post_id ],
        'tax_query'           => [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $primary_cat->term_id ] ],
        'orderby'             => 'date',
        'order'               => 'DESC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ] );
    $related_posts = $q_rel->posts;
    wp_reset_postdata();
}
// If fewer than 3 related in same cat, pad from recent posts.
if ( count( $related_posts ) < 3 ) {
    $pad_exclude = array_merge( [ $post_id ], array_map( fn( $p ) => $p->ID, $related_posts ) );
    $q_pad = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3 - count( $related_posts ),
        'post__not_in'   => $pad_exclude,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
    $related_posts = array_merge( $related_posts, $q_pad->posts );
    wp_reset_postdata();
}
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
    <span class="breadcrumb-sep">›</span>
    <a href="<?php echo esc_url( $blog_url ); ?>">Resources &amp; Guides</a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current"><?php echo esc_html( $cat_name ); ?> Guide</span>
</div>

<!-- Article Header -->
<div class="blog-article-header">
    <?php if ( $thumb_url ) : ?>
    <div class="blog-article-hero-img">
        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="eager">
    </div>
    <?php endif; ?>
    <span class="blog-card-cat <?php echo esc_attr( $cat_class ); ?>"><?php echo esc_html( $cat_name ); ?></span>
    <h1 class="blog-article-title"><?php the_title(); ?></h1>
    <div class="blog-article-meta">
        <span class="blog-article-meta-author">✍️ <?php echo esc_html( $author_name ); ?></span>
        <span class="blog-article-meta-sep">·</span>
        <span><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span>
        <span class="blog-article-meta-sep">·</span>
        <span>⏱ <?php echo esc_html( $read_min ); ?> min read</span>
        <span class="blog-article-meta-sep">·</span>
        <span>🔖 Free guide</span>
    </div>
</div>

<!-- Article Body -->
<div class="blog-article-body">
    <?php the_content(); ?>
</div>

<!-- Related Articles -->
<?php if ( $related_posts ) : ?>
<section class="inner-section inner-section--cream">
    <div class="inner-section-header">
        <div>
            <h2>Related <em>Guides</em></h2>
            <p>Continue reading to make the most informed decision for your family.</p>
        </div>
        <div class="inner-section-aside"><a href="<?php echo esc_url( $blog_url ); ?>">All guides →</a></div>
    </div>
    <div class="blog-grid">
        <?php foreach ( $related_posts as $rp ) :
            $r_cat = null;
            $r_cats = get_the_terms( $rp->ID, 'category' );
            if ( $r_cats && ! is_wp_error( $r_cats ) ) {
                foreach ( $r_cats as $rc ) {
                    if ( 'uncategorized' !== $rc->slug ) { $r_cat = $rc; break; }
                }
                if ( ! $r_cat ) $r_cat = $r_cats[0];
            }
            [ $r_cls, $r_icon, $r_bg ] = ecm_single_cat_meta( $r_cat );
            $r_cat_name = $r_cat ? $r_cat->name : 'Elder Care';
            $r_excerpt  = $rp->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $rp->post_content ), 22, '…' );
            $r_words    = str_word_count( wp_strip_all_tags( $rp->post_content ) );
            $r_mins     = max( 1, (int) round( $r_words / 200 ) );
        ?>
        <a class="blog-card" href="<?php echo esc_url( get_permalink( $rp ) ); ?>">
            <div class="blog-card-icon" style="background:<?php echo esc_attr( $r_bg ); ?>"><?php echo esc_html( $r_icon ); ?></div>
            <div class="blog-card-body">
                <span class="blog-card-cat <?php echo esc_attr( $r_cls ); ?>"><?php echo esc_html( $r_cat_name ); ?></span>
                <div class="blog-card-title"><?php echo esc_html( $rp->post_title ); ?></div>
                <p class="blog-card-excerpt"><?php echo esc_html( $r_excerpt ); ?></p>
            </div>
            <div class="blog-card-footer">
                <span><?php echo esc_html( get_the_date( 'M j, Y', $rp ) . ' · ' . $r_mins . ' min read' ); ?></span>
                <span class="blog-card-footer-link">Read more →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- CTA band -->
<div class="cta-band">
    <div>
        <h2>Ready to find <?php echo esc_html( strtolower( $cat_name ) ); ?>? <em>We'll guide you.</em></h2>
        <p>Our free care advisor will walk you through your options in under 2 minutes.</p>
    </div>
    <div class="cta-band-actions">
        <button type="button" class="btn-primary-lg" data-open-form-modal>📋 Start Your Free Match →</button>
        <button type="button" class="btn-ghost" data-open-chat style="color:#fff;border-color:rgba(255,255,255,0.3);">💬 Chat with advisor</button>
    </div>
</div>

<?php get_footer(); ?>
