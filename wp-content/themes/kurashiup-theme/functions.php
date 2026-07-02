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
}
add_action('wp_enqueue_scripts', 'kurashiup_enqueue_assets');

require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/product-fields.php';
require_once get_template_directory() . '/inc/taxonomies.php';