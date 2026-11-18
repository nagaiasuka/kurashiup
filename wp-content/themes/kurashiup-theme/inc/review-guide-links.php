<?php
/** Model purchase links, kept outside imported article content. */
defined('ABSPATH') || exit;

function kurashiup_review_guide_models($content) {
    preg_match_all('/<h[2-6]\b[^>]*\bid=["\'](model-\d+)["\'][^>]*>(.*?)<\/h[2-6]>/is', $content, $matches, PREG_SET_ORDER);
    $models = [];
    foreach ($matches as $match) {
        $models[$match[1]] = wp_strip_all_tags($match[2]);
    }
    return $models;
}

function kurashiup_review_guide_amazon_tag($post_id) {
    if (metadata_exists('post', $post_id, '_kurashiup_guide_amazon_tag')) {
        return (string) get_post_meta($post_id, '_kurashiup_guide_amazon_tag', true);
    }
    return get_post_field('post_name', $post_id) === 'running-open-ear-earphones-6-compare' ? 'amocreo-22' : '';
}

function kurashiup_review_guide_links_meta_box() {
    add_meta_box('kurashiup_guide_links', 'モデル別アフィリエイトリンク', 'kurashiup_render_guide_links_meta_box', 'review_guide', 'normal');
}
add_action('add_meta_boxes', 'kurashiup_review_guide_links_meta_box');

function kurashiup_render_guide_links_meta_box($post) {
    wp_nonce_field('kurashiup_save_guide_links', 'kurashiup_guide_links_nonce');
    $links = get_post_meta($post->ID, '_kurashiup_guide_affiliate_links', true);
    $links = is_array($links) ? $links : [];
    $models = kurashiup_review_guide_models($post->post_content);
    echo '<p><label for="kurashiup-guide-amazon-tag"><strong>AmazonトラッキングID</strong></label><br>';
    echo '<input type="text" id="kurashiup-guide-amazon-tag" name="kurashiup_guide_amazon_tag" value="' . esc_attr(kurashiup_review_guide_amazon_tag($post->ID)) . '" placeholder="amocreo-22"></p>';
    echo '<p>各モデルの購入URLを入力してください。Amazon・楽天・ASPなどの発行済みアフィリエイトURLを使用できます。URLが空欄の場合は、上記ID付きの商品名によるAmazon検索リンクを表示します。IDも空欄の場合はボタンを表示しません。</p>';
    if (! $models) {
        echo '<p>本文のモデル見出しに model-1、model-2 などのHTMLアンカーを設定し、一度保存すると入力欄が表示されます。</p>';
    }
    foreach ($models as $id => $name) {
        echo '<p><label for="kurashiup-link-' . esc_attr($id) . '"><strong>' . esc_html($name) . '</strong></label><br>';
        echo '<input class="widefat" type="url" id="kurashiup-link-' . esc_attr($id) . '" name="kurashiup_guide_links[' . esc_attr($id) . ']" value="' . esc_attr($links[$id] ?? '') . '" placeholder="https://"></p>';
    }
}

function kurashiup_save_guide_links($post_id) {
    if (! is_string($_POST['kurashiup_guide_links_nonce'] ?? null)
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kurashiup_guide_links_nonce'])), 'kurashiup_save_guide_links')
        || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || ! current_user_can('edit_post', $post_id)) {
        return;
    }
    $input = $_POST['kurashiup_guide_links'] ?? [];
    if (! is_array($input)) {
        return;
    }
    if (is_string($_POST['kurashiup_guide_amazon_tag'] ?? null)) {
        $tag = sanitize_text_field(wp_unslash($_POST['kurashiup_guide_amazon_tag']));
        if ($tag === '' || preg_match('/^[a-zA-Z0-9-]+$/D', $tag)) {
            update_post_meta($post_id, '_kurashiup_guide_amazon_tag', $tag);
        }
    }
    $links = [];
    foreach (kurashiup_review_guide_models(get_post_field('post_content', $post_id)) as $id => $name) {
        if (! is_string($input[$id] ?? null)) {
            continue;
        }
        $url = esc_url_raw(trim(wp_unslash($input[$id])), ['http', 'https']);
        if ($url && in_array(wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $links[$id] = $url;
        }
    }
    update_post_meta($post_id, '_kurashiup_guide_affiliate_links', wp_slash($links));
}
add_action('save_post_review_guide', 'kurashiup_save_guide_links');

function kurashiup_review_guide_purchase_links($content) {
    if (! is_singular('review_guide') || ! in_the_loop() || ! is_main_query() || post_password_required()) {
        return $content;
    }
    $links = get_post_meta(get_the_ID(), '_kurashiup_guide_affiliate_links', true);
    $links = is_array($links) ? $links : [];
    $tag = kurashiup_review_guide_amazon_tag(get_the_ID());
    $models = kurashiup_review_guide_models($content);
    $replace_official = get_post_field('post_name', get_the_ID()) === 'running-open-ear-earphones-6-compare';
    $has_purchase_link = false;
    $content = preg_replace_callback(
        '/(<h([2-6])\b[^>]*\bid=["\'](model-\d+)["\'][^>]*>.*?<\/h\2>)(.*?)(?=<h[1-6]\b|\z)/is',
        static function ($match) use ($links, $tag, $models, $replace_official, &$has_purchase_link) {
            $url = esc_url($links[$match[3]] ?? '', ['http', 'https']);
            $label = '価格・購入先を確認する';
            if (! $url && $tag && ! empty($models[$match[3]])) {
                $url = esc_url('https://www.amazon.co.jp/s?' . http_build_query([
                    'k' => html_entity_decode($models[$match[3]], ENT_QUOTES, 'UTF-8'),
                    'tag' => $tag,
                ], '', '&', PHP_QUERY_RFC3986));
                $label = 'Amazonで商品を探す';
            }
            $body = $match[4];
            if ($replace_official || $url) {
                $body = preg_replace('/<p\b[^>]*>\s*<a\b[^>]*>\s*メーカー公式仕様\s*<\/a>\s*<\/p>/iu', '', $body);
            }
            if ($url) {
                $has_purchase_link = true;
                $body .= '<p class="guide-purchase"><a class="guide-purchase-link" href="' . $url . '" target="_blank" rel="sponsored nofollow noopener">' . esc_html($label) . '</a></p>';
            }
            return $match[1] . $body;
        },
        $content
    );
    if ($has_purchase_link) {
        $content = '<p class="guide-affiliate-disclosure">この記事にはアフィリエイトリンクが含まれます。</p>' . $content;
    }
    return $content;
}
add_filter('the_content', 'kurashiup_review_guide_purchase_links', 20);
