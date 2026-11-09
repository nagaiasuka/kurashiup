<?php get_header(); ?>
<main class="pb-20 pt-36">
  <div class="mx-auto max-w-6xl px-6">
    <header class="mb-10">
      <p class="text-sm font-semibold tracking-wide text-stone-500">KURASHIUP GUIDE</p>
      <h1 class="mt-3 text-3xl font-bold text-stone-900"><?php post_type_archive_title(); ?></h1>
      <p class="mt-4 max-w-2xl leading-7 text-stone-600">暮らしの道具の選び方や、場面に合わせてあると便利なグッズを紹介します。</p>
    </header>
    <?php if (have_posts()) : ?>
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/components/review-guide-card'); ?>
        <?php endwhile; ?>
      </div>
      <?php the_posts_pagination(); ?>
    <?php else : ?><p>記事を準備中です。</p><?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
