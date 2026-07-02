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
$is_front = is_front_page();
?>

<header class="fixed left-0 top-0 z-50 w-full border-b border-white/10 bg-[#070B14]/70 text-white backdrop-blur-xl">
    <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-10 py-9 md:px-16">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-3xl font-bold tracking-tight">
            KurashiUp
        </a>

        <nav class="hidden items-center gap-10 text-sm md:flex">
            <a href="<?php echo esc_url($product_archive_link ?: home_url('/product/')); ?>" class="text-white/70 transition hover:text-white">Products</a>
            <a href="<?php echo esc_url($is_front ? '#categories' : home_url('/#categories')); ?>" class="text-white/70 transition hover:text-white">Category</a>
            <a href="<?php echo esc_url($is_front ? '#popular-ranking' : home_url('/#popular-ranking')); ?>" class="text-white/70 transition hover:text-white">Ranking</a>
            <a href="<?php echo esc_url($is_front ? '#about' : home_url('/#about')); ?>" class="text-white/70 transition hover:text-white">About</a>
        </nav>
        <?php get_search_form(); ?>
    </div>
</header>
