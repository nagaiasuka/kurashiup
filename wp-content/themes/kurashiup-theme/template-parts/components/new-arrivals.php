<?php
$new_products = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 8,
    'post_status' => 'publish',
]);
?>

<section id="new-arrivals" class="bg-white py-24">
    <div class="mx-auto max-w-screen-2xl px-6">

        <div class="mb-10">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">
                New Arrivals
            </p>

            <h2 class="mt-3 text-3xl font-bold text-stone-900">
                新着商品
            </h2>
        </div>

        <?php if ($new_products->have_posts()) : ?>

            <div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">

                <?php while ($new_products->have_posts()) : $new_products->the_post(); ?>

                    <?php get_template_part('template-parts/components/product-card'); ?>

                <?php endwhile; ?>

            </div>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>

            <p class="text-stone-500">
                商品がありません。
            </p>

        <?php endif; ?>

    </div>
</section>