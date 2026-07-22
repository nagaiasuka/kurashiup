<?php

function kurashiup_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'global' => 'グローバルナビゲーション',
    ]);
}
add_action('after_setup_theme', 'kurashiup_theme_setup');

function kurashiup_enqueue_assets() {
    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();

    wp_enqueue_style(
        'kurashiup-app',
        $theme_uri . '/assets/css/app.css',
        [],
        filemtime($theme_dir . '/assets/css/app.css')
    );

    wp_enqueue_script(
        'kurashiup-app',
        $theme_uri . '/assets/js/app.js',
        [],
        filemtime($theme_dir . '/assets/js/app.js'),
        true
    );

    wp_add_inline_script(
        'kurashiup-app',
        'window.kurashiupData = ' . wp_json_encode([
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kurashiup_track_amazon_click'),
        ]) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'kurashiup_enqueue_assets');

function kurashiup_track_amazon_click()
{
    check_ajax_referer('kurashiup_track_amazon_click', 'nonce');

    $product_id = isset($_POST['productId']) ? absint($_POST['productId']) : 0;

    if (! $product_id || 'product' !== get_post_type($product_id)) {
        wp_send_json_error([
            'message' => 'Invalid product.',
        ], 400);
    }

    $click_count = (int) get_post_meta($product_id, '_kurashiup_amazon_click_count', true);
    $updated = update_post_meta($product_id, '_kurashiup_amazon_click_count', $click_count + 1);

    if (false === $updated) {
        wp_send_json_error([
            'message' => 'Unable to update click count.',
        ], 500);
    }

    wp_send_json_success([
        'clickCount' => $click_count + 1,
    ]);
}
add_action('wp_ajax_kurashiup_track_amazon_click', 'kurashiup_track_amazon_click');
add_action('wp_ajax_nopriv_kurashiup_track_amazon_click', 'kurashiup_track_amazon_click');

function kurashiup_redirect_to_amazon()
{
    $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

    if (! $product_id || ! wp_verify_nonce($nonce, 'kurashiup_amazon_redirect_' . $product_id)) {
        wp_die('Invalid request.', 'KurashiUp', [
            'response' => 400,
        ]);
    }

    $amazon_url = get_post_meta($product_id, '_kurashiup_amazon_url', true);

    if (! $amazon_url || 'product' !== get_post_type($product_id)) {
        wp_die('Product not found.', 'KurashiUp', [
            'response' => 404,
        ]);
    }

    $click_count = (int) get_post_meta($product_id, '_kurashiup_amazon_click_count', true);
    update_post_meta($product_id, '_kurashiup_amazon_click_count', $click_count + 1);

    wp_redirect(esc_url_raw($amazon_url), 302, 'KurashiUp');
    exit;
}

/**
 * 商品のみ検索対象にする
 */
function kurashiup_search_products_only($query)
{
    if (
        ! is_admin()
        && $query->is_main_query()
        && $query->is_search()
    ) {
        $query->set('post_type', 'product');
    }
}

/**
 * 商品説明・ブランドも検索対象に含める
 */
function kurashiup_search_join($join)
{
    global $wpdb;

    if (is_search() && !is_admin()) {
        $join .= " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id";
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
        $join .= " LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id";
        $join .= " LEFT JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id";
    }

    return $join;
}
add_filter('posts_join', 'kurashiup_search_join');

function kurashiup_search_where($where)
{
    global $wpdb;

    if (is_search() && !is_admin()) {

        $keyword = get_search_query();

        if ($keyword) {

            $like = '%' . $wpdb->esc_like($keyword) . '%';

            $where .= $wpdb->prepare(
                " OR (
                    t.name LIKE %s
                    OR (
                        pm.meta_key = '_short_description'
                        AND pm.meta_value LIKE %s
                    )
                )",
                $like,
                $like
            );
        }
    }

    return $where;
}
add_filter('posts_where', 'kurashiup_search_where');

function kurashiup_search_distinct($distinct)
{
    if (is_search() && !is_admin()) {
        return "DISTINCT";
    }

    return $distinct;
}
add_filter('posts_distinct', 'kurashiup_search_distinct');
add_action('pre_get_posts', 'kurashiup_search_products_only');

add_action('admin_post_kurashiup_redirect_to_amazon', 'kurashiup_redirect_to_amazon');
add_action('admin_post_nopriv_kurashiup_redirect_to_amazon', 'kurashiup_redirect_to_amazon');

require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/product-fields.php';
require_once get_template_directory() . '/inc/taxonomies.php';
