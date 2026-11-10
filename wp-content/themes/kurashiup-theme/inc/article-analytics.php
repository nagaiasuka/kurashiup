<?php
/**
 * Privacy-conscious daily article view and referrer aggregates.
 */
defined('ABSPATH') || exit;

function kurashiup_article_analytics_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'kurashiup_article_daily';
}

function kurashiup_install_article_analytics_table() {
    if (get_option('kurashiup_article_analytics_schema') === '1') {
        return;
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = kurashiup_article_analytics_table_name();
    $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
        article_id bigint(20) unsigned NOT NULL,
        viewed_on date NOT NULL,
        source varchar(191) NOT NULL,
        views bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (article_id,viewed_on,source),
        KEY viewed_on (viewed_on),
        KEY article_id (article_id)
    ) {$collate};");

    update_option('kurashiup_article_analytics_schema', '1');
}
add_action('admin_init', 'kurashiup_install_article_analytics_table');
add_action('after_switch_theme', 'kurashiup_install_article_analytics_table');

function kurashiup_article_analytics_source() {
    $referrer = wp_get_referer();
    if (! $referrer) {
        return 'direct';
    }

    $host = strtolower((string) wp_parse_url($referrer, PHP_URL_HOST));
    if ('' === $host) {
        return 'direct';
    }
    $host = preg_replace('/^www\./', '', $host);
    $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $site_host = preg_replace('/^www\./', '', $site_host);
    if ($host === $site_host || str_ends_with($host, '.' . $site_host)) {
        return 'internal';
    }

    return substr(sanitize_text_field($host), 0, 191);
}

function kurashiup_track_article_view() {
    if (is_admin() || is_user_logged_in() || is_preview() || is_feed()
        || post_password_required() || ! is_singular(['post', 'review_guide'])) {
        return;
    }

    $user_agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ('' !== $user_agent && preg_match('/bot|crawler|spider|slurp|preview|headless/i', $user_agent)) {
        return;
    }

    global $wpdb;
    $article_id = get_queried_object_id();
    if (! $article_id || 'publish' !== get_post_status($article_id)) {
        return;
    }

    $table = kurashiup_article_analytics_table_name();
    $day = current_time('Y-m-d');
    $source = kurashiup_article_analytics_source();
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$table} (article_id, viewed_on, source, views)
         VALUES (%d, %s, %s, 1)
         ON DUPLICATE KEY UPDATE views = views + 1",
        $article_id,
        $day,
        $source
    ));
}
add_action('template_redirect', 'kurashiup_track_article_view', 20);

function kurashiup_render_article_analytics() {
    if (! current_user_can('manage_options')) {
        return;
    }

    $period = isset($_GET['analytics_period']) && 'month' === sanitize_key(wp_unslash($_GET['analytics_period']))
        ? 'month'
        : 'day';
    $raw_date = isset($_GET['analytics_date']) ? sanitize_text_field(wp_unslash($_GET['analytics_date'])) : '';
    $format = 'month' === $period ? 'Y-m' : 'Y-m-d';
    $date = DateTimeImmutable::createFromFormat('!' . $format, $raw_date, wp_timezone());
    if (! $date || $date->format($format) !== $raw_date) {
        $date = new DateTimeImmutable('now', wp_timezone());
        if ('month' === $period) {
            $date = $date->modify('first day of this month');
        }
    }
    $start = 'month' === $period ? $date->modify('first day of this month') : $date;
    $end = 'month' === $period ? $date->modify('last day of this month') : $date;
    $start_day = $start->format('Y-m-d');
    $end_day = $end->format('Y-m-d');

    global $wpdb;
    $table = kurashiup_article_analytics_table_name();
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT article_id, source, SUM(views) AS views
         FROM {$table}
         WHERE viewed_on BETWEEN %s AND %s
         GROUP BY article_id, source",
        $start_day,
        $end_day
    ));

    $stats = [];
    foreach ($rows as $row) {
        $id = (int) $row->article_id;
        if (! isset($stats[$id])) {
            $stats[$id] = ['views' => 0, 'sources' => []];
        }
        $views = (int) $row->views;
        $stats[$id]['views'] += $views;
        $stats[$id]['sources'][$row->source] = $views;
    }

    $article_ids = get_posts([
        'post_type' => ['post', 'review_guide'],
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);
    $articles = [];
    foreach ($article_ids as $id) {
        $id = (int) $id;
        $articles[] = [
            'id' => $id,
            'views' => $stats[$id]['views'] ?? 0,
            'sources' => $stats[$id]['sources'] ?? [],
        ];
    }
    usort($articles, static function ($a, $b) {
        return ($b['views'] <=> $a['views']) ?: strcasecmp(get_the_title($a['id']), get_the_title($b['id']));
    });

    $input_type = 'month' === $period ? 'month' : 'date';
    $input_value = 'month' === $period ? $date->format('Y-m') : $date->format('Y-m-d');
    ?>
    <h2>記事別アクセス</h2>
    <p>公開記事とガイド記事の閲覧数を日別・月別に集計します。流入元は参照元ドメインだけを集計し、URLのパスや検索語、閲覧者のIPアドレスは保存しません。</p>
    <form method="get" action="">
        <input type="hidden" name="page" value="kurashiup-analytics">
        <label for="kurashiup-analytics-period">集計単位</label>
        <select id="kurashiup-analytics-period" name="analytics_period">
            <option value="day" <?php selected($period, 'day'); ?>>日別</option>
            <option value="month" <?php selected($period, 'month'); ?>>月別</option>
        </select>
        <label for="kurashiup-analytics-date">対象</label>
        <input id="kurashiup-analytics-date" type="<?php echo esc_attr($input_type); ?>" name="analytics_date" value="<?php echo esc_attr($input_value); ?>">
        <?php submit_button('表示', 'secondary', '', false); ?>
    </form>
    <p><strong>対象期間：</strong><?php echo esc_html($start_day); ?><?php if ($start_day !== $end_day) : ?> ～ <?php echo esc_html($end_day); ?><?php endif; ?>（<?php echo esc_html(wp_timezone_string()); ?>）</p>
    <table class="widefat striped">
        <thead><tr><th>記事</th><th>閲覧数</th><th>流入元（閲覧数）</th></tr></thead>
        <tbody>
        <?php if ($articles) : foreach ($articles as $article) : ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($article['id'])); ?>"><?php echo esc_html(get_the_title($article['id'])); ?></a></td>
                <td><?php echo esc_html(number_format_i18n($article['views'])); ?></td>
                <td>
                    <?php if ($article['sources']) :
                        arsort($article['sources']);
                        $source_labels = [];
                        foreach ($article['sources'] as $source => $views) {
                            $label = 'direct' === $source ? '直接' : ('internal' === $source ? 'サイト内' : $source);
                            $source_labels[] = esc_html($label . ' ' . number_format_i18n($views));
                        }
                        echo implode(' / ', $source_labels);
                    else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="3">記事がありません。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="description">計測はこの機能の公開後に始まります。ログイン中の閲覧と一般的なクローラーは除外します。Google検索の表示回数・クリック数はSearch Console連携が必要です。</p>
    <?php
}
