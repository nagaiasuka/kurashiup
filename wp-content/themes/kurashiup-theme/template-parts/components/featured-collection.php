<?php
$featured_products = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'no_found_rows' => true,
    'meta_key' => '_kurashiup_is_featured',
    'orderby' => [
        'meta_value_num' => 'DESC',
        'date' => 'DESC',
    ],
    'meta_query' => [
        [
            'key' => '_kurashiup_is_featured',
            'value' => '1',
            'compare' => '=',
        ],
    ],
]);

if (! $featured_products->have_posts()) {
    $featured_products = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 4,
        'post_status' => 'publish',
        'no_found_rows' => true,
    ]);
}

$product_archive_link = get_post_type_archive_link('product');
?>

<section id="featured-collection" class="relative overflow-hidden bg-[#070B14] py-24 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(198,164,106,0.14),transparent_24%),radial-gradient(circle_at_84%_72%,rgba(255,255,255,0.05),transparent_28%)]"></div>
    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:96px_96px] opacity-[0.22]"></div>

    <div class="relative mx-auto max-w-screen-2xl px-6">
        <div class="flex flex-col gap-8 border-b border-white/10 pb-10 md:flex-row md:items-end md:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.42em] text-[#C6A46A]">
                    Featured Collection
                </p>

                <h2 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">
                    編集部おすすめ
                </h2>

                <p class="mt-4 text-sm leading-7 text-stone-400 md:text-base">
                    暮らしに静かな高揚感をもたらすものを、質感と佇まいまで含めて選びました。
                </p>
            </div>

            <?php if ($product_archive_link) : ?>
                <a
                    href="<?php echo esc_url($product_archive_link); ?>"
                    class="inline-flex items-center gap-3 self-start text-sm font-semibold text-white transition hover:text-[#C6A46A] md:self-auto"
                >
                    <span class="h-px w-10 bg-[#C6A46A]"></span>
                    もっと見る
                </a>
            <?php endif; ?>
        </div>

        <?php if ($featured_products->have_posts()) : ?>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <?php while ($featured_products->have_posts()) : $featured_products->the_post(); ?>
                    <?php get_template_part('template-parts/components/product-card', null, ['theme' => 'dark']); ?>
                <?php endwhile; ?>
            </div>

            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p class="mt-12 text-stone-400">
                商品がありません。
            </p>
        <?php endif; ?>
    </div>
</section>
