<?php
$popular_products = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'no_found_rows' => true,
    'meta_key' => '_kurashiup_amazon_click_count',
    'orderby' => [
        'meta_value_num' => 'DESC',
        'date' => 'DESC',
    ],
    'meta_query' => [
        [
            'key' => '_kurashiup_amazon_click_count',
            'value' => 0,
            'compare' => '>',
            'type' => 'NUMERIC',
        ],
    ],
]);

$product_archive_link = get_post_type_archive_link('product');
?>

<section id="popular-ranking" class="bg-white py-24">
    <div class="mx-auto max-w-screen-2xl px-6">
        <div class="flex flex-col gap-8 border-b border-stone-200 pb-10 md:flex-row md:items-end md:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">
                    Popular Ranking
                </p>

                <h2 class="mt-3 text-3xl font-bold text-stone-900">
                    人気ランキング
                </h2>

                <p class="mt-4 text-sm leading-7 text-stone-600 md:text-base">
                    実際によくクリックされている注目アイテムを、今の関心度が伝わる形でまとめました。
                </p>
            </div>

            <?php if ($product_archive_link) : ?>
                <a
                    href="<?php echo esc_url($product_archive_link); ?>"
                    class="inline-flex items-center gap-3 self-start text-sm font-semibold text-stone-900 transition hover:text-[#C6A46A] md:self-auto"
                >
                    <span class="h-px w-10 bg-[#C6A46A]"></span>
                    もっと見る
                </a>
            <?php endif; ?>
        </div>

        <?php if ($popular_products->have_posts()) : ?>
            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="grid gap-4">
                    <?php
                    $rank = 1;
                    while ($popular_products->have_posts()) :
                        $popular_products->the_post();
                        $click_count = (int) get_post_meta(get_the_ID(), '_kurashiup_amazon_click_count', true);
                        $categories = get_the_terms(get_the_ID(), 'product_category');
                        $category_name = ! empty($categories) && ! is_wp_error($categories) ? $categories[0]->name : '';
                    ?>
                        <article class="group rounded-3xl border border-stone-200 bg-white p-5 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl">
                            <a href="<?php the_permalink(); ?>" class="flex items-center gap-4">
                                <div class="flex h-24 w-12 items-center justify-center rounded-2xl bg-[#070B14] text-xl font-bold text-white">
                                    <?php echo esc_html($rank); ?>
                                </div>

                                <div class="flex w-full items-center gap-4">
                                    <div class="flex items-center justify-center rounded-2xl bg-stone-50 p-5" style="width: 6rem;">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <?php the_post_thumbnail(
                                                'medium',
                                                [
                                                    'class' => 'max-h-full w-full object-contain transition duration-700 group-hover:scale-105',
                                                ]
                                            ); ?>
                                        <?php else : ?>
                                            <div class="h-full w-full rounded-xl bg-stone-100"></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="w-full">
                                        <?php if ($category_name) : ?>
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-stone-400">
                                                <?php echo esc_html($category_name); ?>
                                            </p>
                                        <?php endif; ?>

                                        <h3 class="mt-2 line-clamp-2 text-base font-semibold leading-relaxed text-stone-900">
                                            <?php the_title(); ?>
                                        </h3>

                                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">
                                            <?php echo esc_html(number_format_i18n($click_count)); ?> clicks
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php
                        $rank++;
                    endwhile;
                    ?>
                </div>

                <?php
                wp_reset_postdata();

                $highlighted_products = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'no_found_rows' => true,
                    'meta_key' => '_kurashiup_amazon_click_count',
                    'orderby' => [
                        'meta_value_num' => 'DESC',
                        'date' => 'DESC',
                    ],
                    'meta_query' => [
                        [
                            'key' => '_kurashiup_amazon_click_count',
                            'value' => 0,
                            'compare' => '>',
                            'type' => 'NUMERIC',
                        ],
                    ],
                ]);
                ?>

                <div>
                    <?php if ($highlighted_products->have_posts()) : ?>
                        <?php while ($highlighted_products->have_posts()) : $highlighted_products->the_post(); ?>
                            <?php
                            $highlighted_click_count = (int) get_post_meta(get_the_ID(), '_kurashiup_amazon_click_count', true);
                            $short_description = get_post_meta(get_the_ID(), '_kurashiup_short_description', true);
                            ?>
                            <div class="relative overflow-hidden rounded-[2rem] bg-[#070B14] p-8 text-white">
                                <div class="relative">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#C6A46A]">
                                        Most Clicked
                                    </p>

                                    <h3 class="mt-4 text-2xl font-bold leading-tight">
                                        <?php the_title(); ?>
                                    </h3>

                                    <p class="mt-4 text-sm leading-7 text-stone-300">
                                        <?php echo esc_html($short_description ?: '今もっとも関心を集めているプロダクトです。'); ?>
                                    </p>

                                    <div class="mt-8 rounded-3xl bg-white/[0.045] p-6 backdrop-blur-xl">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <?php the_post_thumbnail(
                                                'large',
                                                [
                                                    'class' => 'mx-auto h-48 w-full object-contain',
                                                ]
                                            ); ?>
                                        <?php else : ?>
                                            <div class="h-48 w-full rounded-2xl bg-white/10"></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-6 flex items-center justify-between gap-4 border-t border-white/10" style="padding-top: 1.5rem;">
                                        <p class="text-sm text-stone-400">
                                            <?php echo esc_html(number_format_i18n($highlighted_click_count)); ?> clicks
                                        </p>

                                        <a
                                            href="<?php the_permalink(); ?>"
                                            class="inline-flex items-center gap-3 text-sm font-semibold text-white transition hover:text-[#C6A46A]"
                                        >
                                            詳細を見る
                                            <span class="h-px w-10 bg-[#C6A46A]"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <p class="mt-12 text-stone-500">
                まだランキングデータがありません。Amazonボタンのクリックが集まると表示されます。
            </p>
        <?php endif; ?>
    </div>
</section>
