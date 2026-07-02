<?php get_header(); ?>

<?php
$brands = get_the_terms(get_the_ID(), 'product_brand');
$brand_name = ! empty($brands) && ! is_wp_error($brands) ? $brands[0]->name : '';

$amazon_url = get_post_meta(get_the_ID(), '_kurashiup_amazon_url', true);
$short_description = get_post_meta(get_the_ID(), '_kurashiup_short_description', true);
?>

<main class="section">
    <div class="mx-auto max-w-6xl px-6">

        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>

                <?php get_template_part('template-parts/layout/breadcrumb'); ?>

                <?php
                    $back_url = wp_get_referer() ?: home_url('/');
                ?>

                <a
                    href="<?php echo esc_url($back_url); ?>"
                    class="mb-8 inline-flex text-sm font-medium text-stone-500 hover:text-stone-900"
                >
                    ← 一覧へ戻る
                </a>
                <div class="grid gap-10 lg:grid-cols-2 lg:items-start">

                    <div class="rounded-3xl bg-stone-50 p-8">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail(
                                'large',
                                [
                                    'class' => 'mx-auto max-h-[480px] w-full object-contain'
                                ]
                            ); ?>
                        <?php else : ?>
                            <div class="aspect-square rounded-2xl bg-stone-100"></div>
                        <?php endif; ?>
                    </div>

                    <div class="lg:pt-8">
                        <?php if ($brand_name) : ?>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-stone-500">
                                <?php echo esc_html($brand_name); ?>
                            </p>
                        <?php endif; ?>

                        <h1 class="mt-4 text-3xl font-bold leading-tight text-stone-900 md:text-4xl">
                            <?php the_title(); ?>
                        </h1>

                        <?php if ($short_description) : ?>
                            <div class="mt-8">
                                <h2 class="text-sm font-semibold tracking-wide text-stone-900">
                                    商品について
                                </h2>
                                <p class="mt-3 text-base leading-8 text-stone-600">
                                    <?php echo esc_html($short_description); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($amazon_url) : ?>
                            <a
                                href="<?php echo esc_url($amazon_url); ?>"
                                target="_blank"
                                rel="noopener sponsored"
                                class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-stone-900 px-8 py-4 text-sm font-semibold text-white transition hover:bg-stone-700 sm:w-auto"
                            >
                                Amazonで見る →
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php get_template_part('template-parts/components/related-products'); ?>
            <?php endwhile; ?>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>