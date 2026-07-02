<?php get_header(); ?>

<main class="section">
    <div class="mx-auto max-w-screen-2xl px-6">

        <?php
        $products = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 20,
            'post_status' => 'publish',
        ]);
        ?>

        <?php if ($products->have_posts()) : ?>
            <div class="grid grid-cols-2 gap-5 lg:grid-cols-4 2xl:grid-cols-5">

                <?php while ($products->have_posts()) : $products->the_post(); ?>

                    <?php get_template_part('template-parts/components/product-card'); ?>

                <?php endwhile; ?>

            </div>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>

            <p class="text-center text-stone-500">
                商品がありません。
            </p>

        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>