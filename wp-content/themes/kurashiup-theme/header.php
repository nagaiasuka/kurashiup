<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-white text-stone-900'); ?>>
<?php wp_body_open(); ?>

<?php
$product_archive_link = get_post_type_archive_link('product');
$guide_archive_link = get_post_type_archive_link('review_guide');
$is_front = is_front_page();
?>

<header class="fixed left-0 top-0 z-50 w-full border-b border-white/10 bg-[#070B14]/70 text-white backdrop-blur-xl">
    <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6 md:py-9 lg:px-8 xl:px-16">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-2xl font-bold tracking-tight md:text-3xl">
            KurashiUp
        </a>

        <nav aria-label="メインナビゲーション" class="flex items-center gap-4 text-sm md:gap-5 xl:gap-8">
            <a href="<?php echo esc_url($guide_archive_link); ?>" class="whitespace-nowrap font-semibold text-[#E4C58E] hover:text-white">ガイド記事</a>
            <a href="<?php echo esc_url($product_archive_link ?: home_url('/product/')); ?>" class="hidden text-white/70 transition hover:text-white sm:inline">Products</a>
            <a href="<?php echo esc_url($is_front ? '#categories' : home_url('/#categories')); ?>" class="hidden text-white/70 transition hover:text-white md:inline">Category</a>
            <a href="<?php echo esc_url($is_front ? '#popular-ranking' : home_url('/#popular-ranking')); ?>" class="hidden text-white/70 transition hover:text-white md:inline">Ranking</a>
            <a href="<?php echo esc_url($is_front ? '#about' : home_url('/#about')); ?>" class="hidden text-white/70 transition hover:text-white md:inline">About</a>
        </nav>
        <div class="hidden lg:block">
            <?php get_search_form(); ?>
        </div>
    </div>
</header>
