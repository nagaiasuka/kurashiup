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
 * Include guides alongside products in the site's main search.
 */
function kurashiup_search_content_types($query)
{
    if (! is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', ['product', 'review_guide']);
    }
}
add_action('pre_get_posts', 'kurashiup_search_content_types');

/**
 * Include product taxonomy names and short descriptions without changing
 * WordPress's outer post type, status and visibility constraints.
 */
function kurashiup_search_product_details($search, $query)
{
    global $wpdb;

    if (is_admin() || ! $query->is_main_query() || ! $query->is_search()
        || ! preg_match('/^\s*AND\s+(.+)$/is', $search, $core)
        || ! is_string($query->get('s')) || '' === trim($query->get('s'))) {
        return $search;
    }

    $like = '%' . $wpdb->esc_like($query->get('s')) . '%';
    $product_match = $wpdb->prepare(
        "({$wpdb->posts}.post_type = 'product' AND (
            EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE tr.object_id = {$wpdb->posts}.ID
                AND tt.taxonomy IN ('product_category', 'product_brand', 'product_tag')
                AND t.name LIKE %s
            )
            OR EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = '_short_description'
                AND pm.meta_value LIKE %s
            )
        ))",
        $like,
        $like
    );

    $search = " AND (({$core[1]}) OR {$product_match}) ";
    if (! is_user_logged_in()) {
        $search .= " AND ({$wpdb->posts}.post_password = '') ";
    }
    return $search;
}
add_filter('posts_search', 'kurashiup_search_product_details', 10, 2);

add_action('admin_post_kurashiup_redirect_to_amazon', 'kurashiup_redirect_to_amazon');
add_action('admin_post_nopriv_kurashiup_redirect_to_amazon', 'kurashiup_redirect_to_amazon');

require_once get_template_directory() . '/inc/review-guides-analytics.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/product-fields.php';
require_once get_template_directory() . '/inc/taxonomies.php';
