<?php get_header(); ?>

<main class="bg-white">

    <section class="bg-[#070B14] py-24 text-white">
        <div class="mx-auto max-w-screen-2xl px-6">

            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-[#C6A46A]">
                Search
            </p>

            <h1 class="mt-4 text-5xl font-bold">
                "<?php echo esc_html(get_search_query()); ?>"
            </h1>

            <p class="mt-4 text-stone-300">
                <?php echo $wp_query->found_posts; ?>件の商品が見つかりました。
            </p>

        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto max-w-screen-2xl px-6">

            <?php if (have_posts()) : ?>

                <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">

                    <?php while (have_posts()) : the_post(); ?>

                        <?php
                        if (get_post_type() === 'product') {
                            get_template_part('template-parts/components/product-card');
                        }
                        ?>

                    <?php endwhile; ?>

                </div>

            <?php else : ?>

                <div class="rounded-3xl bg-stone-100 p-16 text-center">

                    <h2 class="text-3xl font-bold">
                        商品が見つかりませんでした。
                    </h2>

                    <p class="mt-6 text-stone-500">
                        キーワードを変えて検索してください。
                    </p>

                    <a
                        href="<?php echo esc_url(get_post_type_archive_link('product')); ?>"
                        class="mt-10 inline-flex rounded-full bg-[#070B14] px-8 py-4 text-white transition hover:bg-[#111827]"
                    >
                        商品一覧を見る
                    </a>

                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<?php get_footer(); ?>