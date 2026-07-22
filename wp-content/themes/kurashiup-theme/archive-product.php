<?php get_header(); ?>

<main class="bg-white">
    <section class="relative overflow-hidden bg-[#070B14] pb-24 pt-52 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_76%_30%,rgba(198,164,106,0.18),transparent_26%),radial-gradient(circle_at_18%_68%,rgba(30,41,59,0.55),transparent_34%)]"></div>
        <div class="absolute inset-0 opacity-[0.04] bg-[linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] bg-[size:82px_82px]"></div>

        <div class="relative mx-auto max-w-screen-2xl px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.45em] text-[#C6A46A]">
                Products
            </p>

            <h1 class="mt-6 text-3xl font-bold tracking-tight text-white md:text-4xl">
                すべての商品
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-8 text-stone-300 md:text-lg">
                暮らしに静かな高揚感をもたらすプロダクトを、カテゴリをまたいで一覧できます。
            </p>
        </div>
    </section>

    <section class="py-24">
        <div class="mx-auto max-w-screen-2xl px-6">
            <?php if (have_posts()) : ?>
                <div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php get_template_part('template-parts/components/product-card'); ?>
                    <?php endwhile; ?>
                </div>

                <div class="mt-12 flex justify-center">
                    <?php
                    $pagination_links = paginate_links([
                        'mid_size' => 1,
                        'prev_text' => '←',
                        'next_text' => '→',
                        'type' => 'array',
                    ]);
                    ?>

                    <?php if (! empty($pagination_links)) : ?>
                        <div class="flex flex-wrap items-center justify-center gap-2 text-sm font-semibold text-stone-900">
                            <?php foreach ($pagination_links as $pagination_link) : ?>
                                <span class="inline-flex rounded-full border border-stone-200 px-4 py-2">
                                    <?php echo wp_kses_post($pagination_link); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p class="text-center text-stone-500">
                    商品がありません。
                </p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
