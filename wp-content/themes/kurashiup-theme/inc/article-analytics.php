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
    if (! current_user_can('manage_options') || get_option('kurashiup_article_analytics_schema') === '2') {
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

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
        // Preserve the earliest known date from PR #35, including an existing table.
        $first = $wpdb->get_var("SELECT MIN(viewed_on) FROM {$table}");
        add_option('kurashiup_article_analytics_started', $first ?: current_time('Y-m-d'), '', false);
        update_option('kurashiup_article_analytics_schema', '2', false);
    }
}
add_action('admin_init', 'kurashiup_install_article_analytics_table');
add_action('after_switch_theme', 'kurashiup_install_article_analytics_table');

function kurashiup_article_analytics_source() {
    $referrer = wp_get_raw_referer();
    if (! $referrer) {
        return 'direct';
    }

    $host = strtolower((string) wp_parse_url($referrer, PHP_URL_HOST));
    if ('' === $host || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
        return 'direct';
    }
    $host = preg_replace('/^www\./', '', $host);
    $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $site_host = preg_replace('/^www\./', '', $site_host);
    $site_suffix = '.' . $site_host;
    if ($host === $site_host || (strlen($host) > strlen($site_suffix) && substr($host, -strlen($site_suffix)) === $site_suffix)) {
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

    if (get_option('kurashiup_article_analytics_schema') !== '2' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
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
    $saved = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$table} (article_id, viewed_on, source, views)
         VALUES (%d, %s, %s, 1)
         ON DUPLICATE KEY UPDATE views = views + 1",
        $article_id,
        $day,
        $source
    ));
    if (false === $saved) {
        update_option('kurashiup_article_analytics_write_error', current_time('mysql'), false);
    }
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

    $search = sanitize_text_field(wp_unslash($_GET['article_search'] ?? ''));
    $article_page = max(1, absint($_GET['article_page'] ?? 1));
    $query = new WP_Query([
        'post_type' => ['post', 'review_guide'], 'post_status' => 'publish',
        'posts_per_page' => 20, 'paged' => $article_page, 's' => $search,
        'orderby' => ['title' => 'ASC', 'ID' => 'ASC'],
    ]);
    $ids = wp_list_pluck($query->posts, 'ID');
    global $wpdb;
    $table = kurashiup_article_analytics_table_name();
    $stats = [];
    $db_error = get_option('kurashiup_article_analytics_schema') !== '2';
    if ($ids && ! $db_error) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT article_id, source, SUM(views) AS views FROM {$table}
             WHERE viewed_on BETWEEN %s AND %s AND article_id IN ({$placeholders})
             GROUP BY article_id, source", array_merge([$start_day, $end_day], $ids)
        ));
        $db_error = '' !== $wpdb->last_error;
        foreach ((array) $rows as $row) {
            $id = (int) $row->article_id;
            if (! isset($stats[$id])) {
                $stats[$id] = ['views' => 0, 'sources' => []];
            }
            $stats[$id]['views'] += (int) $row->views;
            $stats[$id]['sources'][$row->source] = (int) $row->views;
        }
    }
    $started = get_option('kurashiup_article_analytics_started');
    $write_error = get_option('kurashiup_article_analytics_write_error');
    $pv_state = $db_error ? '集計DBエラー' : ($write_error ? '計測書込エラーあり（欠測の可能性）' :
        (! $started || $end_day < $started ? '計測開始前・未取得' :
        ($start_day < $started ? '一部期間のみ計測' : ($start_day > current_time('Y-m-d') ? '対象日未到来' : ($end_day >= current_time('Y-m-d') ? '集計途中' : '')))));
    $articles = [];
    foreach ($ids as $id) {
        $articles[] = ['id' => $id, 'views' => $stats[$id]['views'] ?? null, 'sources' => $stats[$id]['sources'] ?? []];
    }
    wp_enqueue_script('kurashiup-search-analytics', get_template_directory_uri() . '/assets/js/search-analytics.js', [], (string) filemtime(get_template_directory() . '/assets/js/search-analytics.js'), true);
    wp_localize_script('kurashiup-search-analytics', 'kurashiupSearchAnalytics', [
        'root' => rest_url('google-site-kit/v1/'), 'nonce' => wp_create_nonce('wp_rest'),
        'start' => $start_day, 'end' => $end_day,
        'installed' => defined('GOOGLESITEKIT_VERSION'),
    ]);
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
        <label for="kurashiup-article-search">記事検索</label>
        <input id="kurashiup-article-search" name="article_search" value="<?php echo esc_attr($search); ?>">
        <?php submit_button('表示', 'secondary', '', false); ?>
    </form>
    <p><strong>対象期間：</strong><?php echo esc_html($start_day); ?><?php if ($start_day !== $end_day) : ?> ～ <?php echo esc_html($end_day); ?><?php endif; ?>（<?php echo esc_html(wp_timezone_string()); ?>）</p>
    <p>PV計測開始日：<?php echo esc_html($started ?: '未開始（管理者によるDB初期化が必要）'); ?>。開始前のPVは取得できません。<?php echo esc_html($pv_state); ?></p>
    <p id="kurashiup-search-status" role="status">Search Console：接続状態を確認中…</p>
    <p>閲覧数はページビュー数（PV）で、ユーザー数・セッション数ではありません。直接／参照元不明＝参照元情報なし、サイト内＝同一ドメイン・サブドメイン、外部＝その他の参照元ドメインです。ログイン中の閲覧・一般的なクローラー・GET以外を除外します。キャッシュ配信などPHPを通らない閲覧は計測できません。</p>
    <p>Google検索（ウェブ）の表示回数＝検索結果に表示された回数、クリック数＝検索結果からのクリック。検索指標は記事URLで取得した日次データの合算です。Google側の日付は太平洋時間（America/Los_Angeles）固定で、PVのWordPress時間とは境界が異なります。日次APIからWordPress時間への正確な再集計はできません。通常2〜3日の遅延・直近の暫定値があり、取得可能期間は最大約16か月です。欠落日はゼロ補完しません。</p>
    <noscript><p>Search Console：検索指標の取得にはJavaScriptを有効にしてください。</p></noscript>
    <table class="widefat striped">
        <thead><tr><th>記事</th><th>閲覧数（PV）</th><th>流入元（閲覧数）</th><th>検索表示回数</th><th>検索クリック数</th></tr></thead>
        <tbody>
        <?php if ($articles) : foreach ($articles as $article) : ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($article['id'])); ?>"><?php echo esc_html(get_the_title($article['id'])); ?></a></td>
                <td><?php echo esc_html($db_error ? $pv_state : ($article['views'] === null ? ('記録なし' . ($pv_state ? '（' . $pv_state . '）' : '')) : number_format_i18n($article['views']) . ($pv_state ? '（' . $pv_state . '）' : ''))); ?></td>
                <td>
                    <?php if ($article['sources']) :
                        arsort($article['sources']);
                        $source_labels = [];
                        foreach ($article['sources'] as $source => $views) {
                            $label = 'direct' === $source ? '直接／参照元不明' : ('internal' === $source ? 'サイト内' : $source);
                            $source_labels[] = esc_html($label . ' ' . number_format_i18n($views));
                        }
                        echo implode(' / ', $source_labels);
                    else : ?>
                        <?php echo esc_html($pv_state ?: '記録なし'); ?>
                    <?php endif; ?>
                </td>
                <td class="kurashiup-search-impressions" data-search-url="<?php echo esc_url(get_permalink($article['id'])); ?>">取得待ち</td>
                <td class="kurashiup-search-clicks">取得待ち</td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5">記事がありません。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php echo wp_kses_post(paginate_links([
        'base' => add_query_arg('article_page', '%#%'), 'format' => '',
        'current' => $article_page, 'total' => $query->max_num_pages,
        'prev_text' => '記事：前へ', 'next_text' => '記事：次へ',
    ])); ?>
    <p class="description">PVはサーバーで記録できた閲覧のみです。参照元URLのパス・クエリ・検索語・IPは保存しません。検索データなしは、検索実績ゼロを保証しません。URL変更・Googleの正規URLが異なる記事は一致しないことがあります。</p>
    <?php
}
