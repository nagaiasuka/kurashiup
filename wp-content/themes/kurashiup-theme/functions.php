<?php

function kurashiup_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'global' => 'グローバルナビゲーション',
    ]);
}
add_action('after_setup_theme', 'kurashiup_theme_setup');
