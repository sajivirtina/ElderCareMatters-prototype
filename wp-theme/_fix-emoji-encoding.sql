-- Fix emoji/special-char encoding in seeded ACF fields
-- Run with: mysql --default-character-set=utf8mb4 -u root -proot -P 10053 local < _fix-emoji-encoding.sql

USE local;

UPDATE wp_postmeta SET meta_value = '✦ Trusted Care Matching · Free for Families - America\'s oldest and most respected Elder Care Directory' WHERE post_id=2 AND meta_key='trust_bar_text';

UPDATE wp_postmeta SET meta_value = '📋' WHERE post_id=2 AND meta_key='hero_cta_primary_icon';
UPDATE wp_postmeta SET meta_value = '💬' WHERE post_id=2 AND meta_key='hero_cta_secondary_icon';

UPDATE wp_postmeta SET meta_value = '🔒 Free for Families' WHERE post_id=2 AND meta_key='hero_badges_0_text';
UPDATE wp_postmeta SET meta_value = '✓ No spam, ever'     WHERE post_id=2 AND meta_key='hero_badges_1_text';
UPDATE wp_postmeta SET meta_value = '⭐ 4.9 avg rating'   WHERE post_id=2 AND meta_key='hero_badges_2_text';

UPDATE wp_postmeta SET meta_value = '🏠' WHERE post_id=2 AND meta_key='hero_quick_cats_0_emoji';
UPDATE wp_postmeta SET meta_value = '🏡' WHERE post_id=2 AND meta_key='hero_quick_cats_1_emoji';
UPDATE wp_postmeta SET meta_value = '🧠' WHERE post_id=2 AND meta_key='hero_quick_cats_2_emoji';
UPDATE wp_postmeta SET meta_value = '⚖️' WHERE post_id=2 AND meta_key='hero_quick_cats_3_emoji';
UPDATE wp_postmeta SET meta_value = '🕊️' WHERE post_id=2 AND meta_key='hero_quick_cats_4_emoji';

UPDATE wp_postmeta SET meta_value = 'America\'s oldest & most respected elder care directory · Operating in all 50 states' WHERE post_id=2 AND meta_key='hero_trust_text';

UPDATE wp_postmeta SET meta_value = '12k+ families matched'      WHERE post_id=2 AND meta_key='hero_fc_rating_label';
UPDATE wp_postmeta SET meta_value = '500+ Verified Providers'    WHERE post_id=2 AND meta_key='hero_fc_verified_title';
UPDATE wp_postmeta SET meta_value = 'Pre-screened & trusted'     WHERE post_id=2 AND meta_key='hero_fc_verified_sub';
UPDATE wp_postmeta SET meta_value = 'to your first match · always free' WHERE post_id=2 AND meta_key='hero_fc_time_label';

UPDATE wp_postmeta SET meta_value = '🏠' WHERE post_id=2 AND meta_key='categories_0_icon';
UPDATE wp_postmeta SET meta_value = 'In-home aides, personal care, medication management and companionship — so your loved one can stay where they\'re most comfortable.' WHERE post_id=2 AND meta_key='categories_0_description';
UPDATE wp_postmeta SET meta_value = '🏡' WHERE post_id=2 AND meta_key='categories_1_icon';
UPDATE wp_postmeta SET meta_value = '🧠' WHERE post_id=2 AND meta_key='categories_2_icon';
UPDATE wp_postmeta SET meta_value = 'Secure, specialized environments for Alzheimer\'s and dementia with structured daily routines and trained staff.' WHERE post_id=2 AND meta_key='categories_2_description';
UPDATE wp_postmeta SET meta_value = '⚖️'  WHERE post_id=2 AND meta_key='categories_3_icon';
UPDATE wp_postmeta SET meta_value = '👩‍💼' WHERE post_id=2 AND meta_key='categories_4_icon';
UPDATE wp_postmeta SET meta_value = '🕊️' WHERE post_id=2 AND meta_key='categories_5_icon';
UPDATE wp_postmeta SET meta_value = '💙'  WHERE post_id=2 AND meta_key='categories_6_icon';

UPDATE wp_postmeta SET meta_value = 'Select one category — we\'ll instantly show verified local providers and guide you to a free, no-obligation match.' WHERE post_id=2 AND meta_key='cats_section_subtitle';

UPDATE wp_postmeta SET meta_value = '📍' WHERE post_id=2 AND meta_key='why_cards_0_icon';
UPDATE wp_postmeta SET meta_value = '✓'  WHERE post_id=2 AND meta_key='why_cards_1_icon';
UPDATE wp_postmeta SET meta_value = '🔒' WHERE post_id=2 AND meta_key='why_cards_2_icon';
UPDATE wp_postmeta SET meta_value = '🛡️' WHERE post_id=2 AND meta_key='why_cards_3_icon';

UPDATE wp_postmeta SET meta_value = '✓'  WHERE post_id=2 AND meta_key='footer_trust_badges_0_icon';
UPDATE wp_postmeta SET meta_value = '🔒' WHERE post_id=2 AND meta_key='footer_trust_badges_1_icon';
UPDATE wp_postmeta SET meta_value = '⭐' WHERE post_id=2 AND meta_key='footer_trust_badges_2_icon';

UPDATE wp_postmeta SET meta_value = '👩‍⚕️' WHERE post_id=2 AND meta_key='chat_avatar_emoji';
UPDATE wp_postmeta SET meta_value = '💬'  WHERE post_id=2 AND meta_key='chat_fab_icon';

UPDATE wp_postmeta SET meta_value = '· Change' WHERE post_id=2 AND meta_key='location_badge_change';
UPDATE wp_postmeta SET meta_value = 'Detecting…' WHERE post_id=2 AND meta_key='location_badge_detecting';
UPDATE wp_postmeta SET meta_value = 'City or ZIP code…' WHERE post_id=2 AND meta_key='location_modal_city_placeholder';
UPDATE wp_postmeta SET meta_value = 'Searching for a parent in a different city? Enter their location.' WHERE post_id=2 AND meta_key='location_modal_sub';
