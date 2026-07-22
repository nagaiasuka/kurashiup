<?php
$categories = get_terms([
    'taxonomy' => 'product_category',
    'hide_empty' => false,
]);
?>

<section id="categories" class="bg-stone-100 py-20">
    <div class="mx-auto max-w-screen-2xl px-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">
            Category
        </p>

        <h2 class="mt-3 text-3xl font-bold text-stone-900">
            カテゴリから探す
        </h2>

        <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            <?php foreach ($categories as $category) : ?>
                <a
                    href="<?php echo esc_url(get_term_link($category)); ?>"
                    class="group rounded-2xl border border-stone-200 bg-white p-6 transition duration-500 hover:-translate-y-1 hover:shadow-lg"
                >
                    <p class="text-sm font-semibold text-stone-400">
                        Product Category
                    </p>

                    <h3 class="mt-4 text-xl font-bold text-stone-900">
                        <?php echo esc_html($category->name); ?>
                    </h3>

                    <p class="mt-6 text-sm font-semibold text-stone-900 transition group-hover:translate-x-1">
                        View →
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>