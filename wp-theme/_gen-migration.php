<?php
/**
 * One-off generator: reads the corrected homepage postmeta from the local DB
 * and emits inc/migrate-homepage-content.php (a one-time production migration).
 * Run with the Local PHP binary:  php _gen-migration.php
 */

// Read base64 TSV dump (meta_key \t base64(meta_value)) produced by the mysql client.
$dump = __DIR__ . '/_meta-dump.tsv';
if (!is_file($dump)) {
    fwrite(STDERR, "Missing dump: $dump\n");
    exit(1);
}

// WordPress-core meta we must NOT migrate (page-specific internals)
$skip = [
    '_edit_lock', '_edit_last', '_wp_page_template', '_pingme',
    '_encloseme', '_wp_old_slug', '_wp_old_date', '_thumbnail_id',
];

$pairs = [];
foreach (file($dump, FILE_IGNORE_NEW_LINES) as $line) {
    if ($line === '') continue;
    $tab = strpos($line, "\t");
    if ($tab === false) continue;
    $k = substr($line, 0, $tab);
    $v = base64_decode(substr($line, $tab + 1));
    if (in_array($k, $skip, true)) continue;
    $pairs[$k] = $v;
}

// Build the PHP array literal with correct escaping.
$lines = [];
foreach ($pairs as $k => $v) {
    $lines[] = "\t" . var_export($k, true) . " => " . var_export($v, true) . ",";
}
$arrayBody = implode("\n", $lines);

$out = <<<PHP
<?php
/**
 * One-time homepage content migration.
 *
 * Seeds all ACF homepage field values into the database for the site's
 * configured front page. Safe to ship to production:
 *   - Auto-detects the front-page post ID (no hardcoded ID).
 *   - Idempotent: each meta key is only written if it does NOT already exist,
 *     so it never overwrites content an admin has edited.
 *   - Runs once, guarded by the option flag below; flip the version to re-run.
 *
 * This replaces the raw SQL seed/fix scripts. Generated from the verified
 * (utf8mb4-correct) local content, so emoji and special characters are intact.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ECM_HOMEPAGE_SEED_VERSION' ) ) {
    define( 'ECM_HOMEPAGE_SEED_VERSION', 'v1' );
}

add_action( 'init', function () {

    \$flag = get_option( 'ecm_homepage_seed_done' );
    if ( \$flag === ECM_HOMEPAGE_SEED_VERSION ) {
        return; // already migrated at this version
    }

    // Resolve the configured front page; bail if the site isn't using one yet.
    \$pid = (int) get_option( 'page_on_front' );
    if ( \$pid <= 0 ) {
        return;
    }

    \$data = ecm_homepage_seed_data();

    foreach ( \$data as \$key => \$value ) {
        // INSERT-IGNORE semantics: never clobber an existing value.
        if ( ! metadata_exists( 'post', \$pid, \$key ) ) {
            update_post_meta( \$pid, \$key, \$value );
        }
    }

    update_option( 'ecm_homepage_seed_done', ECM_HOMEPAGE_SEED_VERSION );

}, 5 );

/**
 * The full set of homepage ACF meta (field values + ACF field-key reference
 * rows) in the exact format ACF stores. Keep field-key rows ('_name' => 'field_...')
 * — they are what makes the values editable in the ACF admin UI.
 */
function ecm_homepage_seed_data() : array {
    return [
$arrayBody
    ];
}
PHP;

$dest = __DIR__ . '/eldercare-matters/inc/migrate-homepage-content.php';
file_put_contents($dest, $out);
echo "Wrote " . count($pairs) . " meta rows to:\n$dest\n";
