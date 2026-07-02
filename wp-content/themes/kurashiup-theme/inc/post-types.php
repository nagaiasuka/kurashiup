<?php

function kurashiup_register_product_post_type() {
    $labels = [
        'name' => '商品',
        'singular_name' => '商品',
        'menu_name' => '商品',
        'add_new' => '新規商品を追加',
        'add_new_item' => '新規商品を追加',
        'edit_item' => '商品を編集',
        'new_item' => '新規商品',
        'view_item' => '商品を見る',
        'search_items' => '商品を検索',
        'not_found' => '商品が見つかりません',
        'not_found_in_trash' => 'ゴミ箱に商品が見つかりません',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-cart',
        'rewrite' => [
            'slug' => 'products',
        ],
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'excerpt',
        ],
        'show_in_rest' => true,
    ];

    register_post_type('product', $args);
}
add_action('init', 'kurashiup_register_product_post_type');