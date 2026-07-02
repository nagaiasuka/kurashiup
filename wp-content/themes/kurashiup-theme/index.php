<?php get_header(); ?>

<main class="section">

    <div class="container-ku">

        <?php if (have_posts()) : ?>

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">

                <?php while (have_posts()) : the_post(); ?>

                    <?php get_template_part(
                        'template-parts/components/article-card'
                    ); ?>

                <?php endwhile; ?>

            </div>

        <?php else : ?>

            <p class="text-center text-stone-500">
                記事がありません。
            </p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>