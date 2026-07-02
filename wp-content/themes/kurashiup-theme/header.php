<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-white text-stone-900'); ?>>
<?php wp_body_open(); ?>

<header class="fixed left-0 top-0 z-50 w-full border-b border-white/10 bg-[#070B14]/70 text-white backdrop-blur-xl">
    <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-10 py-9 md:px-16">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-3xl font-bold tracking-tight">
            KurashiUp
        </a>

        <nav class="hidden items-center gap-10 text-sm md:flex">
            <a href="#new-arrivals" class="text-white/70 transition hover:text-white">Products</a>
            <a href="#categories" class="text-white/70 transition hover:text-white">Category</a>
            <a href="#popular-ranking" class="text-white/70 transition hover:text-white">Ranking</a>
            <a href="#" class="text-white/70 transition hover:text-white">About</a>
        </nav>

    </div>
</header>
