<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="border-b border-stone-200 bg-stone-50">
    <div class="container-ku flex items-center justify-between py-5">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-2xl font-bold tracking-tight text-stone-900">
            KurashiUp
        </a>

        <nav class="hidden items-center gap-8 text-sm font-medium text-stone-700 md:flex">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-stone-950">Home</a>
            <a href="<?php echo esc_url(home_url('/ranking/')); ?>" class="hover:text-stone-950">Ranking</a>
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="hover:text-stone-950">About</a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="hover:text-stone-950">Contact</a>
        </nav>
    </div>
</header>
