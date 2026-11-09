<article <?php post_class('overflow-hidden rounded-2xl border border-stone-200 bg-white'); ?>>
  <a href="<?php the_permalink(); ?>" class="block">
    <?php if (has_post_thumbnail() && ! post_password_required()) : the_post_thumbnail('medium_large', ['class' => 'aspect-[4/3] w-full object-cover']); endif; ?>
    <div class="p-5">
      <p class="mb-2 text-xs font-semibold text-stone-600">比較・解説</p>
      <h2 class="text-xl font-bold leading-snug text-stone-900"><?php the_title(); ?></h2>
      <?php if (has_excerpt() && ! post_password_required()) : ?><p class="mt-3 leading-7 text-stone-600"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
      <span class="mt-4 inline-block text-sm font-semibold text-stone-700">記事を読む →</span>
    </div>
  </a>
</article>
