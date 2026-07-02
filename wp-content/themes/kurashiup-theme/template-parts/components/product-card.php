<?php

$brands = get_the_terms(get_the_ID(), 'product_brand');
$brand_name = ! empty($brands) && ! is_wp_error($brands)
    ? $brands[0]->name
    : '';

$categories = get_the_terms(get_the_ID(), 'product_category');
$category_name = ! empty($categories) && ! is_wp_error($categories)
    ? $categories[0]->name
    : '';

$product_url = get_permalink();

?>

<article class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
    <a href="<?php echo esc_url($product_url); ?>" class="block">

        <div class="flex h-56 items-center justify-center bg-stone-50 p-4">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail(
                    'medium',
                    [
                        'class' => 'max-h-44 w-full object-contain'
                    ]
                ); ?>
            <?php else : ?>
                <div class="h-40 w-full rounded-xl bg-stone-100"></div>
            <?php endif; ?>
        </div>

        <div class="space-y-2 p-4">
            <?php if ($category_name) : ?>
                <p class="text-[11px] font-semibold tracking-wide text-stone-500">
                    <?php echo esc_html($category_name); ?>
                </p>
            <?php endif; ?>

            <?php if ($brand_name) : ?>
                <p class="text-xs text-stone-500">
                    <?php echo esc_html($brand_name); ?>
                </p>
            <?php endif; ?>

            <h2 class="line-clamp-2 text-base font-semibold leading-snug text-stone-900">
                <?php the_title(); ?>
            </h2>

            <span class="inline-flex pt-2 text-xs font-semibold text-stone-900 transition group-hover:translate-x-1">
                詳細を見る →
            </span>
        </div>

    </a>
</article>