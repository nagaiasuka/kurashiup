<?php
$product_categories = get_the_terms(get_the_ID(), 'product_category');
$product_category = ! empty($product_categories) && ! is_wp_error($product_categories)
    ? $product_categories[0]
    : null;
?>

<nav class="mb-8 text-sm text-stone-500" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2">
        <li>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-stone-900">
                Home
            </a>
        </li>

        <?php if ($product_category) : ?>
            <li>/</li>
            <li>
                <a
                    href="<?php echo esc_url(get_term_link($product_category)); ?>"
                    class="hover:text-stone-900"
                >
                    <?php echo esc_html($product_category->name); ?>
                </a>
            </li>
        <?php endif; ?>

        <li>/</li>
        <li class="text-stone-900">
            <?php the_title(); ?>
        </li>
    </ol>
</nav>