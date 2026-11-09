<?php get_header(); ?>
<main class="section">
  <div class="mx-auto max-w-6xl px-6">
    <header class="mb-10">
      <p class="text-sm font-semibold tracking-wide text-stone-500">KURASHIUP GUIDE</p>
      <h1 class="mt-3 text-3xl font-bold text-stone-900"><?php post_type_archive_title(); ?></h1>
      <p class="mt-4 max-w-2xl leading-7 text-stone-600">公開情報や購入者レビューを調べ、商品選びのポイントを比較・解説します。</p>
    </header>
    <?php if (have_posts()) : ?>
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php while (have_posts()) : the_post(); ?>
          <article <?php post_class('overflow-hidden rounded-2xl border border-stone-200 bg-white'); ?>>
            <a href="<?php the_permalink(); ?>" class="block">
              <?php if (has_post_thumbnail()) : the_post_thumbnail('medium_large', ['class' => 'aspect-[4/3] w-full object-cover']); endif; ?>
              <div class="p-5">
                <h2 class="text-xl font-bold leading-snug text-stone-900"><?php the_title(); ?></h2>
                <?php if (has_excerpt()) : ?><p class="mt-3 leading-7 text-stone-600"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                <span class="mt-4 inline-block text-sm font-semibold text-stone-700">記事を読む →</span>
              </div>
            </a>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else : ?><p>記事を準備中です。</p><?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
