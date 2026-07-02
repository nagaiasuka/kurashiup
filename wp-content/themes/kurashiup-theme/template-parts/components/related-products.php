<?php
$current_product_id = get_the_ID();

$product_categories = get_the_terms($current_product_id, 'product_category');
$product_category_ids = [];

if (! empty($product_categories) && ! is_wp_error($product_categories)) {
    $product_category_ids = wp_list_pluck($product_categories, 'term_id');
}

$related_products = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'post__not_in' => [$current_product_id],
    'tax_query' => ! empty($product_category_ids) ? [
        [
            'taxonomy' => 'product_category',
            'field' => 'term_id',
            'terms' => $product_category_ids,
        ],
    ] : [],
]);
?>

<?php if ($related_products->have_posts()) : ?>

    <section class="mt-24 border-t border-stone-200 pt-12">
        <div class="mb-8 flex items-end justify-between gap-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-400">
                    Related Products
                </p>
                <h2 class="mt-2 text-2xl font-bold text-stone-900">
                    関連商品
                </h2>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <?php while ($related_products->have_posts()) : $related_products->the_post(); ?>
                <?php get_template_part('template-parts/components/product-card'); ?>
            <?php endwhile; ?>
        </div>
    </section>

    <?php wp_reset_postdata(); ?>

<?php endif; ?>