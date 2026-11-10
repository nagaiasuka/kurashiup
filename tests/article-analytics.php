<?php
// Run only on a disposable WordPress: wp eval-file /path/to/this/file --allow-root.
if (wp_parse_url(home_url(), PHP_URL_HOST) !== 'localhost') {
    throw new RuntimeException('Disposable localhost environment required.');
}
function check($condition, $label) {
    if (! $condition) { throw new RuntimeException($label); }
    echo "PASS: {$label}\n";
}
global $wpdb;
wp_set_current_user(1);
kurashiup_install_article_analytics_table();
check(get_option('kurashiup_article_analytics_schema') === '2', 'schema installed');
$started = get_option('kurashiup_article_analytics_started');
kurashiup_install_article_analytics_table();
check(get_option('kurashiup_article_analytics_started') === $started, 'migration is idempotent');
$id = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Analytics fixture article']);
$guide = wp_insert_post(['post_type' => 'review_guide', 'post_status' => 'publish', 'post_title' => 'Analytics fixture guide']);
$product = wp_insert_post(['post_type' => 'product', 'post_status' => 'publish', 'post_title' => 'Analytics fixture product']);
update_post_meta($product, '_kurashiup_amazon_click_count', 17);
update_option('kurashiup_article_analytics_started', '2026-09-01');
$table = kurashiup_article_analytics_table_name();
$wpdb->insert($table, ['article_id' => $id, 'viewed_on' => '2026-10-01', 'source' => 'direct', 'views' => 2]);
$wpdb->insert($table, ['article_id' => $id, 'viewed_on' => '2026-10-31', 'source' => 'example.org', 'views' => 3]);
$wpdb->insert($table, ['article_id' => $id, 'viewed_on' => '2026-11-01', 'source' => 'internal', 'views' => 100]);
function render_fixture($unit, $date) {
    $_GET = ['analytics_period' => $unit, 'analytics_date' => $date, 'article_search' => 'Analytics fixture'];
    ob_start(); kurashiup_render_analytics_page(); return ob_get_clean();
}
$day = render_fixture('day', '2026-10-01');
check(strpos($day, '>2</td>') !== false, 'daily PV');
$month = render_fixture('month', '2026-10');
check(strpos($month, '>5</td>') !== false && strpos($month, '>105</td>') === false, 'monthly PV excludes next month');
check(strpos($month, esc_url(get_edit_post_link($guide))) !== false, 'guide edit link');
check(strpos($month, '>17</td>') !== false, 'Amazon cumulative clicks preserved');
check(strpos($month, '記録なし') !== false, 'missing PV not zero filled');
check(strpos(render_fixture('day', '2026-08-31'), '計測開始前・未取得') !== false, 'before measurement');
$_SERVER['HTTP_REFERER'] = 'https://www.example.org/private?q=secret';
check(kurashiup_article_analytics_source() === 'example.org', 'only referrer domain retained');
$_SERVER['HTTP_REFERER'] = home_url('/');
check(kurashiup_article_analytics_source() === 'internal', 'internal referrer');
unset($_SERVER['HTTP_REFERER']);
check(kurashiup_article_analytics_source() === 'direct', 'missing referrer');
$_SERVER['HTTP_REFERER'] = 'http://192.0.2.1/private';
check(kurashiup_article_analytics_source() === 'direct', 'referrer IP not stored');
unset($_SERVER['HTTP_REFERER']);
update_option('timezone_string', 'Asia/Tokyo');
$boundary = new DateTimeImmutable('2026-10-31T15:00:00Z');
check(wp_date('Y-m-d', $boundary->getTimestamp(), wp_timezone()) === '2026-11-01', 'Tokyo month boundary');
// Exercise tracker with a timezone different from the PHP/DB server.
add_filter('pre_option_timezone_string', static function () { return 'Pacific/Kiritimati'; });
wp_set_current_user(0);
wp($id ? ['p' => $id] : []);
$_SERVER['HTTP_USER_AGENT'] = 'Analytics test browser';
$_SERVER['REQUEST_METHOD'] = 'GET';
$today = current_time('Y-m-d');
kurashiup_track_article_view();
check((int) $wpdb->get_var($wpdb->prepare("SELECT SUM(views) FROM {$table} WHERE article_id=%d AND viewed_on=%s", $id, $today)) >= 1, 'tracker uses WP local date');
$before = (int) $wpdb->get_var("SELECT SUM(views) FROM {$table}");
$_SERVER['HTTP_USER_AGENT'] = 'Googlebot'; kurashiup_track_article_view();
check((int) $wpdb->get_var("SELECT SUM(views) FROM {$table}") === $before, 'crawler excluded');
$_SERVER['HTTP_USER_AGENT'] = 'Browser'; wp_set_current_user(1); kurashiup_track_article_view();
check((int) $wpdb->get_var("SELECT SUM(views) FROM {$table}") === $before, 'logged-in admin excluded');
wp_set_current_user(1);
update_option('kurashiup_article_analytics_write_error', '2026-10-01 12:00:00');
check(strpos(render_fixture('day', '2026-10-01'), '計測書込エラーあり') !== false, 'write error visible');
delete_option('kurashiup_article_analytics_write_error');
update_option('kurashiup_article_analytics_schema', 'test-unavailable');
check(strpos(render_fixture('day', '2026-10-01'), '集計DBエラー') !== false, 'DB error distinct from missing PV');
wp_set_current_user(0);
kurashiup_install_article_analytics_table();
check(get_option('kurashiup_article_analytics_schema') === 'test-unavailable', 'unauthorized schema migration denied');
update_option('kurashiup_article_analytics_schema', '2');
ob_start(); kurashiup_render_analytics_page(); $output = ob_get_clean();
check($output === '', 'unauthorized admin output denied');
wp_set_current_user(1);
foreach ([$id, $guide, $product] as $post_id) {
    $wpdb->delete($table, ['article_id' => $post_id]);
    wp_delete_post($post_id, true);
}
update_option('kurashiup_article_analytics_started', $started);
