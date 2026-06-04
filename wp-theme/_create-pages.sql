-- Create ECM pages and assign page templates
-- Columns set explicitly to satisfy NOT NULL-without-default constraints.

SET @now = NOW();

-- Helper pattern repeated per page ------------------------------------------

-- CATEGORY
INSERT INTO wp_posts
  (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
   post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
   post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order,
   post_type, post_mime_type, comment_count)
VALUES
  (1, @now, @now, '', 'Find Care – Category', '', 'publish', 'closed', 'closed', '',
   'category', '', '', @now, @now, '', 0, '', 0, 'page', '', 0);
SET @cat_id = LAST_INSERT_ID();
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@cat_id, '_wp_page_template', 'template-category.php');

-- SEARCH / FIND CARE
INSERT INTO wp_posts
  (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
   post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
   post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order,
   post_type, post_mime_type, comment_count)
VALUES
  (1, @now, @now, '', 'Find Care', '', 'publish', 'closed', 'closed', '',
   'find-care', '', '', @now, @now, '', 0, '', 0, 'page', '', 0);
SET @srch_id = LAST_INSERT_ID();
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@srch_id, '_wp_page_template', 'template-search.php');

-- BLOG / RESOURCES
INSERT INTO wp_posts
  (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
   post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
   post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order,
   post_type, post_mime_type, comment_count)
VALUES
  (1, @now, @now, '', 'Resources', '', 'publish', 'closed', 'closed', '',
   'resources', '', '', @now, @now, '', 0, '', 0, 'page', '', 0);
SET @blog_id = LAST_INSERT_ID();
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@blog_id, '_wp_page_template', 'template-blog.php');

-- BLOG DETAIL
INSERT INTO wp_posts
  (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
   post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
   post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order,
   post_type, post_mime_type, comment_count)
VALUES
  (1, @now, @now, '', 'Resources Guide', '', 'publish', 'closed', 'closed', '',
   'resources-guide', '', '', @now, @now, '', 0, '', 0, 'page', '', 0);
SET @bd_id = LAST_INSERT_ID();
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@bd_id, '_wp_page_template', 'template-blog-detail.php');

-- FOR PROVIDERS
INSERT INTO wp_posts
  (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
   post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
   post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order,
   post_type, post_mime_type, comment_count)
VALUES
  (1, @now, @now, '', 'For Providers', '', 'publish', 'closed', 'closed', '',
   'for-providers', '', '', @now, @now, '', 0, '', 0, 'page', '', 0);
SET @fp_id = LAST_INSERT_ID();
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (@fp_id, '_wp_page_template', 'template-for-providers.php');

-- Report
SELECT p.ID, p.post_title, p.post_name, pm.meta_value AS template
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_page_template'
WHERE p.post_name IN ('category','find-care','resources','resources-guide','for-providers')
ORDER BY p.ID;
