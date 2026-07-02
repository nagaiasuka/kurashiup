<?php

function kurashiup_register_product_taxonomies()
{
    register_taxonomy(
        'product_category',
        'product',
        [
            'label' => '商品カテゴリ',
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'product-category',
            ],
        ]
    );

    register_taxonomy(
        'product_brand',
        'product',
        [
            'label' => 'ブランド',
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'brand',
            ],
        ]
    );

    register_taxonomy(
        'product_tag',
        'product',
        [
            'label' => '商品タグ',
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'product-tag',
            ],
        ]
    );
}

add_action('init', 'kurashiup_register_product_taxonomies');