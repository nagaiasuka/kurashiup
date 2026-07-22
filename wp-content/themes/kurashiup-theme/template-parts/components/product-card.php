<?php
$categories = get_the_terms(get_the_ID(), 'product_category');
$category_name = ! empty($categories) && ! is_wp_error($categories)
    ? $categories[0]->name
    : '';

$product_url = get_permalink();
$theme = $args['theme'] ?? 'light';
$is_dark = 'dark' === $theme;

$article_classes = $is_dark
    ? 'border-white/10 bg-white/[0.03] shadow-[0_24px_80px_rgba(0,0,0,0.28)] hover:bg-white/[0.05] hover:shadow-[0_32px_96px_rgba(0,0,0,0.34)]'
    : 'border-stone-200/80 bg-white hover:shadow-xl';

$media_classes = $is_dark
    ? 'bg-white/[0.03]'
    : 'bg-stone-50';

$placeholder_classes = $is_dark
    ? 'bg-white/10'
    : 'bg-stone-100';

$category_classes = $is_dark
    ? 'text-stone-500'
    : 'text-stone-400';

$title_classes = $is_dark
    ? 'text-white'
    : 'text-stone-900';

$link_classes = $is_dark
    ? 'text-white'
    : 'text-stone-900';
?>

<article class="group overflow-hidden rounded-2xl border transition-all duration-500 hover:-translate-y-2 <?php echo esc_attr($article_classes); ?>">
    <a href="<?php echo esc_url($product_url); ?>" class="block">

        <div class="flex aspect-[4/3] items-center justify-center p-6 <?php echo esc_attr($media_classes); ?>">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail(
                    'medium',
                    [
                        'class' => 'max-h-48 w-full object-contain transition duration-700 group-hover:scale-105'
                    ]
                ); ?>
            <?php else : ?>
                <div class="h-full w-full rounded-xl <?php echo esc_attr($placeholder_classes); ?>"></div>
            <?php endif; ?>
        </div>

        <div class="p-5">
            <?php if ($category_name) : ?>
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] <?php echo esc_attr($category_classes); ?>">
                    <?php echo esc_html($category_name); ?>
                </p>
            <?php endif; ?>

            <h2 class="mt-3 line-clamp-2 text-base font-semibold leading-relaxed <?php echo esc_attr($title_classes); ?>">
                <?php the_title(); ?>
            </h2>

            <span class="mt-5 inline-flex text-xs font-semibold transition group-hover:translate-x-1 <?php echo esc_attr($link_classes); ?>">
                詳細を見る →
            </span>
        </div>

    </a>
</article>
