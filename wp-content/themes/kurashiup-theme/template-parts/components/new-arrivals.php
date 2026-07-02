<?php
$new_products = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 4,
    'post_status' => 'publish',
]);

$product_archive_link = get_post_type_archive_link('product');
?>

<section id="new-arrivals" class="bg-white py-24">
    <div class="mx-auto max-w-screen-2xl px-6">

        <div class="mb-10 flex flex-col gap-6 border-b border-stone-200 pb-10 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">
                    New Arrivals
                </p>

                <h2 class="mt-3 text-3xl font-bold text-stone-900">
                    新着商品
                </h2>
            </div>

            <?php if ($product_archive_link) : ?>
                <a
                    href="<?php echo esc_url($product_archive_link); ?>"
                    class="inline-flex items-center gap-3 self-start text-sm font-semibold text-stone-900 transition hover:text-[#C6A46A] md:self-auto"
                >
                    <span class="h-px w-10 bg-[#C6A46A]"></span>
                    すべて見る
                </a>
            <?php endif; ?>
        </div>

        <?php if ($new_products->have_posts()) : ?>

            <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">

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
