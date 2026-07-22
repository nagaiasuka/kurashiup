<?php
$categories = get_the_category();
$category = ! empty($categories) ? $categories[0] : null;
?>

<article class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">

    <a href="<?php the_permalink(); ?>">

        <?php if (has_post_thumbnail()) : ?>

            <?php the_post_thumbnail(
                'large',
                [
                    'class' => 'aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105'
                ]
            ); ?>

        <?php else : ?>

            <div class="aspect-[16/9] w-full bg-stone-200"></div>

        <?php endif; ?>

        <div class="p-6">

            <?php if ($category) : ?>

                <span class="text-xs font-semibold uppercase tracking-widest text-amber-700">

                    <?php echo esc_html($category->name); ?>

                </span>

            <?php endif; ?>

            <h2 class="mt-3 line-clamp-2 text-xl font-bold text-stone-900">

                <?php the_title(); ?>

            </h2>

            <p class="mt-3 line-clamp-2 text-sm leading-7 text-stone-600">

                <?php echo esc_html(get_the_excerpt()); ?>

            </p>

            <time
                datetime="<?php echo esc_attr(get_the_date('c')); ?>"
                class="mt-6 block text-sm text-stone-400">

                <?php echo esc_html(get_the_date('Y.m.d')); ?>

            </time>

        </div>

    </a>

</article>