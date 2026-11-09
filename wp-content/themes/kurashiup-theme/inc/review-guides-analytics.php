<?php
/**
 * Review-guide content type and editorial metadata for KurashiUp.
 */
defined('ABSPATH') || exit;

function kurashiup_register_review_guide_post_type() {
    register_post_type('review_guide', [
        'labels' => [
            'name' => '比較・解説記事',
            'singular_name' => '比較・解説記事',
            'add_new_item' => '比較・解説記事を追加',
            'edit_item' => '比較・解説記事を編集',
            'view_item' => '記事を見る',
            'search_items' => '記事を検索',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'guide', 'with_front' => false],
        'menu_position' => 6,
        'menu_icon' => 'dashicons-media-document',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'kurashiup_register_review_guide_post_type');

function kurashiup_review_guide_meta_box() {
    add_meta_box(
        'kurashiup_review_guide_details',
        '記事の根拠・SEO情報',
        'kurashiup_render_review_guide_meta_box',
        'review_guide',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'kurashiup_review_guide_meta_box');

function kurashiup_render_review_guide_meta_box($post) {
    wp_nonce_field('kurashiup_save_review_guide_meta', 'kurashiup_review_guide_nonce');
    $description = get_post_meta($post->ID, '_kurashiup_meta_description', true);
    $basis = get_post_meta($post->ID, '_kurashiup_review_basis', true);
    $checked_at = get_post_meta($post->ID, '_kurashiup_review_checked_at', true);
    ?>
    <p><label for="kurashiup_meta_description"><strong>検索結果向け説明文</strong>（120字前後）</label></p>
    <textarea id="kurashiup_meta_description" name="kurashiup_meta_description" rows="3" maxlength="200" class="widefat"><?php echo esc_textarea($description); ?></textarea>
    <p><label for="kurashiup_review_basis"><strong>比較・レビューの根拠</strong></label></p>
    <textarea id="kurashiup_review_basis" name="kurashiup_review_basis" rows="3" class="widefat" placeholder="例：メーカー公表仕様と、公開購入者レビューの傾向を編集部が確認"><?php echo esc_textarea($basis); ?></textarea>
    <p><label for="kurashiup_review_checked_at"><strong>情報確認日</strong></label></p>
    <input id="kurashiup_review_checked_at" name="kurashiup_review_checked_at" type="date" value="<?php echo esc_attr($checked_at); ?>">
    <p class="description">実際に使用していない場合は、本文にも「購入者レビュー・公開仕様の調査」と明記し、使用体験として表現しないでください。</p>
    <?php
}

function kurashiup_save_review_guide_meta($post_id) {
    if (
        ! isset($_POST['kurashiup_review_guide_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kurashiup_review_guide_nonce'])), 'kurashiup_save_review_guide_meta')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || ! current_user_can('edit_post', $post_id)
        || 'review_guide' !== get_post_type($post_id)
    ) {
        return;
    }

    $fields = [
        'kurashiup_meta_description' => '_kurashiup_meta_description',
        'kurashiup_review_basis' => '_kurashiup_review_basis',
        'kurashiup_review_checked_at' => '_kurashiup_review_checked_at',
    ];
    foreach ($fields as $input => $meta_key) {
        if (! isset($_POST[$input])) {
            continue;
        }
        $value = sanitize_textarea_field(wp_unslash($_POST[$input]));
        update_post_meta($post_id, $meta_key, $value);
    }
}
add_action('save_post_review_guide', 'kurashiup_save_review_guide_meta');

function kurashiup_review_guide_meta_description() {
    if (! is_singular('review_guide')) {
        return;
    }
    // Avoid duplicate description tags when a dedicated SEO plugin is active.
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')) {
        return;
    }
    $description = get_post_meta(get_queried_object_id(), '_kurashiup_meta_description', true);
    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
}
add_action('wp_head', 'kurashiup_review_guide_meta_description', 2);

function kurashiup_search_review_guides($query) {
    if (! is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', ['product', 'review_guide']);
    }
}
add_action('pre_get_posts', 'kurashiup_search_review_guides', 20);

function kurashiup_analytics_admin_menu() {
    add_menu_page(
        'KurashiUpのクリック分析',
        '分析',
        'manage_options',
        'kurashiup-analytics',
        'kurashiup_render_analytics_page',
        'dashicons-chart-bar',
        27
    );
}
add_action('admin_menu', 'kurashiup_analytics_admin_menu');

function kurashiup_render_analytics_page() {
    if (! current_user_can('manage_options')) {
        return;
    }
    $products = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => 100,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <div class="wrap">
        <h1>KurashiUpのクリック分析</h1>
        <p>以下はサイト内で記録しているAmazonリンクの累計クリック数です。検索結果の表示回数や記事閲覧数ではありません。</p>
        <table class="widefat striped">
            <thead><tr><th>商品</th><th>状態</th><th>Amazonクリック（累計）</th></tr></thead>
            <tbody>
            <?php if ($products) : foreach ($products as $product) : ?>
                <tr>
                    <td><a href="<?php echo esc_url(get_edit_post_link($product->ID)); ?>"><?php echo esc_html(get_the_title($product)); ?></a></td>
                    <td><?php echo esc_html(get_post_status_object($product->post_status)->label); ?></td>
                    <td><?php echo esc_html(number_format_i18n((int) get_post_meta($product->ID, '_kurashiup_amazon_click_count', true))); ?></td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="3">商品データがありません。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <hr>
        <h2>検索・閲覧データ</h2>
        <p>検索結果の表示回数・クリック数はGoogle Search Console、サイト閲覧数はGoogle Analytics 4の連携が必要です。連携後はGoogle Site Kitの管理画面で確認してください。</p>
    </div>
    <?php
}
