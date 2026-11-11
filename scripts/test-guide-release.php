<?php
// Run only against a disposable WordPress database, after installing WordPress.
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if (! is_blog_installed()) {
    wp_install('Release test', 'release-admin', 'release@example.test', true, '', 'local-release-test');
}
update_option('template', 'kurashiup-theme');
update_option('stylesheet', 'kurashiup-theme');
update_option('home', 'https://amocreo.com');
update_option('siteurl', 'https://amocreo.com');
register_post_type('review_guide', ['public' => true, 'supports' => ['title', 'editor', 'thumbnail']]);
add_theme_support('post-thumbnails');
require_once '/var/www/html/wp-content/themes/kurashiup-theme/inc/guide-release.php';
function check_release($condition, $message) {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}
$key = 'kurashiup_release_running-watch-20261111';
$directory = get_template_directory() . '/releases/running-watch-20261111';
$asset = $directory . '/assets/pace4.png';
$bytes = file_get_contents($asset);
file_put_contents($asset, 'partial FTP file');
kurashiup_publish_watch_release();
check_release(! get_option($key), 'Partial uploads do not publish an article');
file_put_contents($asset, $bytes);
$collision = wp_insert_post(['post_type' => 'review_guide', 'post_status' => 'draft', 'post_name' => 'running-watch-8-compare', 'post_title' => 'Existing unrelated article']);
kurashiup_publish_watch_release();
check_release(get_post($collision)->post_title === 'Existing unrelated article' && ! get_option($key), 'Existing articles are not overwritten');
wp_delete_post($collision, true);
delete_transient($key . '_retry');
$protected = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Existing production content', 'post_content' => 'Keep this exactly']);
kurashiup_publish_watch_release();
$post_id = (int) get_option($key);
check_release($post_id > 0 && get_post_status($post_id) === 'publish', 'Complete approved release publishes');
$content = get_post_field('post_content', $post_id);
check_release(strpos($content, '<svg') !== false && strpos($content, '<style') !== false, 'Inline comparison chart and styling survive WordPress saving');
check_release(strpos($content, '="assets/') === false, 'Relative image paths become public theme URLs');
check_release(substr_count($content, 'https://link.amazon/') === 8, 'All eight affiliate links remain in the article');
check_release(get_post_field('post_content', $protected) === 'Keep this exactly', 'Unrelated existing content is unchanged');
$thumbnail = get_post_thumbnail_id($post_id);
$metadata = wp_get_attachment_metadata($thumbnail);
check_release($metadata['width'] === 1200 && $metadata['height'] === 630, 'X featured image is registered at 1200 by 630');
$before_posts = wp_count_posts('review_guide')->publish;
$before_media = wp_count_posts('attachment')->inherit;
kurashiup_publish_watch_release();
check_release(wp_count_posts('review_guide')->publish === $before_posts && wp_count_posts('attachment')->inherit === $before_media, 'Repeated requests do not duplicate posts or media');
echo "Published test article $post_id, featured image $thumbnail\n";
