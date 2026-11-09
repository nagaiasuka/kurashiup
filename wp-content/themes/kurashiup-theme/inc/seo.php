<?php
defined('ABSPATH') || exit;

function kurashiup_has_seo_plugin() {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION');
}

function kurashiup_seo_title_parts($parts) {
    if (is_front_page() && ! is_paged() && ! kurashiup_has_seo_plugin()) {
        $parts['tagline'] = '暮らしの便利グッズと選び方ガイド';
    }
    return $parts;
}
add_filter('document_title_parts', 'kurashiup_seo_title_parts');

function kurashiup_seo_description() {
    if (is_front_page() && ! is_paged()) {
        return 'KurashiUpは、暮らしに役立つグッズを紹介するメディアです。公開仕様や購入者レビューをもとに、商品選びのポイントや場面別の便利グッズを紹介します。';
    }
    if (is_post_type_archive('review_guide') && ! is_paged()) {
        return '暮らしの道具の比較、選び方、場面に合わせた便利グッズをまとめたKurashiUpのガイド記事一覧です。';
    }
    if (is_post_type_archive('product') && ! is_paged()) {
        return 'KurashiUpの商品一覧。ガジェットや家電、収納、インテリア、生活雑貨をカテゴリ別に探せます。';
    }

    $post = get_queried_object();
    if (! is_singular(['review_guide', 'product']) || ! $post instanceof WP_Post || post_password_required($post)) {
        return '';
    }

    if ('product' === $post->post_type) {
        $description = get_post_meta($post->ID, '_kurashiup_short_description', true)
            ?: get_post_meta($post->ID, '_short_description', true);
    } else {
        $description = get_post_meta($post->ID, '_kurashiup_meta_description', true)
            ?: ($post->post_excerpt ?: $post->post_content);
    }

    return wp_html_excerpt(wp_strip_all_tags(strip_shortcodes($description), true), 160, '…');
}

function kurashiup_seo_head() {
    if (kurashiup_has_seo_plugin()) {
        return;
    }

    $description = kurashiup_seo_description();
    if ($description !== '') {
        printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
    }

    // WordPress already emits the canonical URL on individual posts and pages.
    if (is_singular() || is_preview()) {
        return;
    }

    if (is_front_page() && ! is_paged()) {
        $canonical = home_url('/');
    } elseif (is_post_type_archive(['review_guide', 'product'])) {
        global $wp_rewrite;
        $canonical = get_post_type_archive_link(get_queried_object()->name);
        $page = (int) get_query_var('paged');
        if ($page > 1) {
            $canonical = $wp_rewrite->using_permalinks()
                ? trailingslashit($canonical) . user_trailingslashit($wp_rewrite->pagination_base . '/' . $page, 'paged')
                : add_query_arg('paged', $page, $canonical);
        }
    } else {
        return;
    }

    printf("<link rel=\"canonical\" href=\"%s\">\n", esc_url($canonical));
}
add_action('wp_head', 'kurashiup_seo_head', 2);

function kurashiup_guide_social_card() {
    if (! is_singular('review_guide') || is_preview() || kurashiup_has_seo_plugin()) {
        return;
    }

    $guide = get_queried_object();
    if (! $guide instanceof WP_Post || $guide->post_status !== 'publish' || $guide->post_password !== '') {
        return;
    }

    $image_id = get_post_thumbnail_id($guide);
    $image = wp_get_attachment_image_src($image_id, 'full');
    if (! $image) {
        return;
    }

    $title = wp_strip_all_tags(get_the_title($guide));
    $description = kurashiup_seo_description();
    $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $title;
    $open_graph = [
        'og:type' => 'article',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => get_permalink($guide),
        'og:image' => $image[0],
        'og:image:width' => $image[1],
        'og:image:height' => $image[2],
        'og:image:alt' => $image_alt,
    ];
    $twitter = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => $title,
        'twitter:description' => $description,
        'twitter:image' => $image[0],
        'twitter:image:alt' => $image_alt,
    ];

    foreach ($open_graph as $property => $content) {
        printf("<meta property=\"%s\" content=\"%s\">\n", esc_attr($property), esc_attr($content));
    }
    foreach ($twitter as $name => $content) {
        printf("<meta name=\"%s\" content=\"%s\">\n", esc_attr($name), esc_attr($content));
    }
}
add_action('wp_head', 'kurashiup_guide_social_card', 3);

function kurashiup_noindex_empty_guide_archive($robots) {
    global $wp_query;
    if (is_post_type_archive('review_guide') && ! $wp_query->post_count) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'kurashiup_noindex_empty_guide_archive');
