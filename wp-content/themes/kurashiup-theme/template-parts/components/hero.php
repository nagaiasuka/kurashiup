<section class="relative overflow-hidden bg-[#070B14] text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_76%_30%,rgba(198,164,106,0.18),transparent_26%),radial-gradient(circle_at_18%_68%,rgba(30,41,59,0.55),transparent_34%)]"></div>

    <div class="absolute inset-0 opacity-[0.04] bg-[linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] bg-[size:82px_82px]"></div>

        <div class="relative mx-auto grid min-h-[82vh] max-w-screen-2xl items-center gap-10 px-6 pb-24 pt-52 lg:grid-cols-[0.95fr_1.05fr]">        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.45em] text-[#C6A46A]">
                CURATED PRODUCTS
            </p>
            <h1 class="mt-8 text-6xl font-bold leading-tight tracking-tight md:text-8xl">
                <span class="block text-white">暮らしを、</span>
                <span class="block text-white">
                    少し<span class="text-[#C6A46A]">上げる。</span>
                </span>
            </h1>

            <p class="mt-8 max-w-xl text-base leading-8 text-stone-300 md:text-lg">
                本当に気に入ったものだけを。
                日常に静かな高揚感を与えてくれるアイテムを厳選して紹介します。
            </p>

            <div class="mt-12 flex items-center gap-5">
                <span class="h-px w-12 bg-[#C6A46A]"></span>
                <span class="text-xs font-semibold uppercase tracking-[0.35em] text-stone-400">
                    Scroll
                </span>
            </div>
        </div>

        <div class="relative hidden h-[540px] lg:block">

            <?php
            $hero_products = new WP_Query([
                'post_type' => 'product',
                'posts_per_page' => 5,
                'post_status' => 'publish',
            ]);
            ?>

            <?php if ($hero_products->have_posts()) : ?>
                <?php
                $positions = [
                    'left-[10%] top-[4%] h-44 w-44 rotate-[-10deg]',
                    'right-[10%] top-[2%] h-52 w-52 rotate-[8deg]',
                    'left-[38%] top-[30%] h-60 w-60 rotate-[-2deg]',
                    'left-[6%] bottom-[16%] h-40 w-40 rotate-[7deg]',
                    'right-[6%] bottom-[8%] h-48 w-48 rotate-[-7deg]',
                ];

                $index = 0;
                ?>

                <?php while ($hero_products->have_posts()) : $hero_products->the_post(); ?>

                    <a
                        href="<?php the_permalink(); ?>"
                        class="absolute <?php echo esc_attr($positions[$index] ?? $positions[0]); ?> group animate-float rounded-[2rem] border border-white/10 bg-white/[0.045] p-5 shadow-2xl backdrop-blur-xl transition duration-700 hover:-translate-y-3 hover:bg-white/[0.08]"
                        style="animation-delay: <?php echo esc_attr($index * 0.4); ?>s;"
                    >
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail(
                                'medium',
                                [
                                    'class' => 'h-full w-full object-contain transition duration-700 group-hover:scale-105'
                                ]
                            ); ?>
                        <?php else : ?>
                            <div class="h-full w-full rounded-2xl bg-white/10"></div>
                        <?php endif; ?>
                    </a>

                    <?php $index++; ?>

                <?php endwhile; ?>

                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <div class="absolute bottom-0 left-1/2 h-24 w-[72%] -translate-x-1/2 rounded-[100%] bg-[#C6A46A]/16 blur-3xl"></div>
        </div>

    </div>
</section>