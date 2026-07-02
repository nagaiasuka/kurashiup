<?php get_header(); ?>

<main class="bg-[#070B14] text-white">

    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_25%,rgba(198,164,106,0.18),transparent_26%),radial-gradient(circle_at_20%_70%,rgba(30,41,59,0.55),transparent_34%)]"></div>

        <div class="relative mx-auto flex min-h-[80vh] max-w-5xl flex-col items-center justify-center px-6 text-center">

            <p class="text-sm font-semibold uppercase tracking-[0.45em] text-[#C6A46A]">
                ERROR 404
            </p>

            <h1 class="mt-6 text-6xl font-bold leading-tight md:text-8xl">
                ページが<br>
                <span class="text-[#C6A46A]">見つかりません。</span>
            </h1>

            <p class="mt-8 max-w-xl text-lg leading-8 text-stone-300">
                お探しのページは削除されたか、URLが変更された可能性があります。
            </p>

            <div class="mt-12 flex flex-wrap justify-center gap-4">

                <a
                    href="<?php echo esc_url(home_url('/')); ?>"
                    class="rounded-full bg-[#C6A46A] px-8 py-4 font-semibold text-black transition hover:scale-105"
                >
                    トップへ戻る
                </a>

                <a
                    href="<?php echo esc_url(get_post_type_archive_link('product')); ?>"
                    class="rounded-full border border-white/20 px-8 py-4 font-semibold transition hover:bg-white/10"
                >
                    商品を見る
                </a>

            </div>

        </div>
    </section>

</main>

<?php get_footer(); ?>