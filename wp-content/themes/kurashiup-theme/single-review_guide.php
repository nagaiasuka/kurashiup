<?php get_header(); ?>
<main class="pb-20 pt-36">
  <div class="mx-auto max-w-4xl px-6">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <?php get_template_part('template-parts/layout/breadcrumb'); ?>
      <article <?php post_class('review-guide'); ?>>
        <header class="mb-10">
          <p class="text-sm font-semibold tracking-wide text-stone-500"><?php echo esc_html(kurashiup_review_guide_format_label(get_the_ID())); ?></p>
          <h1 class="mt-3 text-3xl font-bold leading-tight text-stone-900 md:text-4xl"><?php the_title(); ?></h1>
          <?php $summary = has_excerpt() ? get_the_excerpt() : get_post_meta(get_the_ID(), '_kurashiup_meta_description', true); ?>
          <?php if ($summary && ! post_password_required()) : ?><p class="mt-5 text-lg leading-8 text-stone-600"><?php echo esc_html($summary); ?></p><?php endif; ?>
          <p class="mt-4 text-sm text-stone-600">公開情報・購入者レビューをもとにした調査記事です。実際の使用体験は含みません。</p>
          <p class="mt-4 text-sm text-stone-500">更新日：<time datetime="<?php echo esc_attr(get_the_modified_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_modified_date('Y年n月j日')); ?></time></p>
        </header>
        <?php if (has_post_thumbnail() && ! post_password_required()) : ?>
          <figure class="mb-10"><?php the_post_thumbnail('large', ['class' => 'h-auto w-full rounded-2xl']); ?></figure>
        <?php endif; ?>
        <div class="review-guide-content leading-8">
          <?php the_content(); ?>
        </div>
        <?php
          $basis = get_post_meta(get_the_ID(), '_kurashiup_review_basis', true);
          $checked_at = get_post_meta(get_the_ID(), '_kurashiup_review_checked_at', true);
        ?>
        <?php if (($basis || $checked_at) && ! post_password_required()) : ?>
          <aside class="mt-12 rounded-2xl bg-stone-50 p-6 text-sm leading-7 text-stone-600">
            <h2 class="mb-2 font-semibold text-stone-900">調査について</h2>
            <?php if ($basis) : ?><?php echo wp_kses_post(wpautop(make_clickable(esc_html($basis)))); ?><?php endif; ?>
            <?php if ($checked_at) : ?><p class="mt-2">情報確認日：<time datetime="<?php echo esc_attr($checked_at); ?>"><?php echo esc_html($checked_at); ?></time></p><?php endif; ?>
          </aside>
        <?php endif; ?>
      </article>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer(); ?>
