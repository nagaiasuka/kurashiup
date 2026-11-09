<?php
$guide_archive_link = get_post_type_archive_link('review_guide');
$latest_guides = get_posts([
    'post_type' => 'review_guide',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'has_password' => false,
]);
?>
<section class="relative overflow-hidden bg-[#070B14] text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_76%_30%,rgba(198,164,106,0.18),transparent_26%),radial-gradient(circle_at_18%_68%,rgba(30,41,59,0.55),transparent_34%)]"></div>
    <div class="absolute inset-0 opacity-[0.04] bg-[linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] bg-[size:82px_82px]"></div>

    <div class="relative mx-auto grid max-w-screen-2xl items-center gap-10 px-6 pb-20 pt-32 md:px-12 md:pt-44 lg:min-h-[72vh] lg:grid-cols-2 lg:gap-16">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-[#E4C58E]">KURASHIUP GUIDE</p>
            <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight sm:text-6xl">
                <span class="block text-white">暮らしに合うものを、</span>
                <span class="block text-[#E4C58E]">見つけよう。</span>
            </h1>
            <p class="mt-6 max-w-xl text-base leading-8 text-stone-300 md:text-lg">
                暮らしの道具の選び方から、あると便利なグッズまで。メーカーの公開情報や購入者レビューをもとに紹介します。
            </p>
            <a href="<?php echo esc_url($guide_archive_link); ?>" class="mt-8 inline-flex min-h-12 items-center gap-4 rounded-full bg-[#E4C58E] px-6 py-3 font-semibold text-[#070B14] hover:bg-white">
                ガイド記事を読む <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="rounded-3xl border border-white/15 bg-white/5 p-6 sm:p-8">
            <?php if ($latest_guides) : ?>
                <h2 class="text-xl font-bold text-white">新着のガイド記事</h2>
                <ul class="mt-4 divide-y divide-white/15">
                    <?php foreach ($latest_guides as $guide) : ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($guide)); ?>" class="flex items-center justify-between gap-4 py-4 font-semibold text-white hover:text-[#E4C58E]">
                                <span><?php echo esc_html(get_the_title($guide)); ?></span>
                                <span class="shrink-0 text-[#E4C58E]" aria-hidden="true">→</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <h2 class="text-xl font-bold text-white">記事でわかること</h2>
                <ul class="mt-4 list-disc space-y-3 ps-5 text-sm leading-7 text-stone-300">
                    <li>メーカーの公開仕様で比較できる違い</li>
                    <li>購入者レビューに見られる評価や気になる点</li>
                    <li>筋トレや家事など、場面に合わせてあると便利なグッズ</li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
