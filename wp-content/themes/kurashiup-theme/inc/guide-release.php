<?php
/**
 * One-time publication of a reviewed, immutable Git release.
 * No request parameters, remote fetches, or unauthenticated write API are used.
 * Existing articles (including an unrelated post with the same slug) are left alone.
 */
function kurashiup_publish_watch_release() {
    $release = 'running-watch-20261111';
    $done_key = 'kurashiup_release_' . $release;
    if (get_option($done_key) || wp_installing() || ! post_type_exists('review_guide')) {
        return;
    }
    if (! in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['amocreo.com', 'www.amocreo.com'], true)) {
        return;
    }
    $directory = get_template_directory() . '/releases/' . $release;
    $manifest_file = $directory . '/manifest.json';
    if (! is_readable($manifest_file)) {
        return;
    }
    $manifest = json_decode(file_get_contents($manifest_file), true);
    $required = ['title', 'slug', 'excerpt', 'content_file', 'featured_image_file', 'featured_image_alt', 'information_checked_date', 'files'];
    foreach ($required as $key) {
        if (empty($manifest[$key])) {
            return;
        }
    }
    if ($manifest['slug'] !== 'running-watch-8-compare' || count($manifest['files']) !== 10) {
        return;
    }
    // FTP can expose a partial release; do nothing until every file is complete.
    foreach ($manifest['files'] as $name => $digest) {
        if (strpos($name, '..') !== false || substr($name, 0, 1) === '/' || ! is_readable($directory . '/' . $name)
            || ! hash_equals($digest, hash_file('sha256', $directory . '/' . $name))) {
            return;
        }
    }
    if (get_transient($done_key . '_retry')) {
        return;
    }
    // add_option is atomic. Never steal a lock from an in-flight publication.
    if (! add_option($done_key . '_lock', time(), '', false)) {
        return;
    }
    try {
        $existing = get_page_by_path($manifest['slug'], OBJECT, 'review_guide');
        if ($existing && get_post_meta($existing->ID, '_kurashiup_release', true) !== $release) {
            throw new RuntimeException('Existing article with this slug was left unchanged.');
        }
        if ($existing && $existing->post_status === 'publish') {
            update_option($done_key, $existing->ID, false);
            return;
        }
        $authors = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
        if (empty($authors)) {
            throw new RuntimeException('No existing administrator author found.');
        }
        $post_id = $existing ? $existing->ID : wp_insert_post(wp_slash([
            'post_type' => 'review_guide', 'post_status' => 'draft',
            'post_title' => $manifest['title'], 'post_name' => $manifest['slug'],
            'post_author' => $authors[0]->ID, 'comment_status' => 'closed',
            'meta_input' => ['_kurashiup_release' => $release],
        ]), true);
        if (is_wp_error($post_id)) {
            throw new RuntimeException($post_id->get_error_message());
        }
        $featured_id = (int) get_post_meta($post_id, '_kurashiup_release_featured', true);
        if (! $featured_id || ! wp_attachment_is_image($featured_id)) {
            $image_name = $manifest['featured_image_file'];
            $upload = wp_upload_bits($image_name, null, file_get_contents($directory . '/' . $image_name));
            if ($upload['error']) {
                throw new RuntimeException($upload['error']);
            }
            $featured_id = wp_insert_attachment([
                'post_mime_type' => 'image/jpeg', 'post_title' => $manifest['title'],
                'post_status' => 'inherit', 'post_author' => $authors[0]->ID,
            ], $upload['file'], $post_id, true);
            if (is_wp_error($featured_id)) {
                throw new RuntimeException($featured_id->get_error_message());
            }
            update_post_meta($post_id, '_kurashiup_release_featured', $featured_id);
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata($featured_id, wp_generate_attachment_metadata($featured_id, $upload['file']));
            update_post_meta($featured_id, '_wp_attachment_image_alt', $manifest['featured_image_alt']);
        }
        if (! set_post_thumbnail($post_id, $featured_id) && get_post_thumbnail_id($post_id) !== $featured_id) {
            throw new RuntimeException('Could not set featured image.');
        }
        $content = file_get_contents($directory . '/' . $manifest['content_file']);
        $asset_url = trailingslashit(get_template_directory_uri()) . 'releases/' . $release . '/assets/';
        $content = str_replace(['="assets/', "='assets/"], ['="' . esc_url($asset_url), "='" . esc_url($asset_url)], $content);
        update_post_meta($post_id, '_kurashiup_meta_description', $manifest['excerpt']);
        update_post_meta($post_id, '_kurashiup_guide_format', 'comparison');
        update_post_meta($post_id, '_kurashiup_review_checked_at', $manifest['information_checked_date']);
        update_post_meta($post_id, '_kurashiup_review_basis', 'メーカー公式仕様・公式サポート資料に基づく比較。出典URLと仕様の条件は本文末尾に記載。実機使用レビューではありません。');
        // Preserve the reviewed inline SVG/CSS, only for this fixed Git-owned content.
        $kses_priority = has_filter('content_save_pre', 'wp_filter_post_kses');
        if ($kses_priority !== false) {
            remove_filter('content_save_pre', 'wp_filter_post_kses', $kses_priority);
        }
        try {
            $result = wp_update_post(wp_slash([
                'ID' => $post_id, 'post_content' => $content,
                'post_excerpt' => $manifest['excerpt'], 'post_status' => 'publish',
            ]), true);
        } finally {
            if ($kses_priority !== false) {
                add_filter('content_save_pre', 'wp_filter_post_kses', $kses_priority);
            }
        }
        if (is_wp_error($result) || get_post_status($post_id) !== 'publish') {
            throw new RuntimeException('Could not publish prepared article.');
        }
        update_option($done_key, $post_id, false);
        delete_option($done_key . '_error');
    } catch (Throwable $error) {
        update_option($done_key . '_error', $error->getMessage(), false);
        set_transient($done_key . '_retry', 1, 300);
        error_log('KurashiUp guide release: ' . $error->getMessage());
    } finally {
        delete_option($done_key . '_lock');
    }
}
add_action('wp_loaded', 'kurashiup_publish_watch_release', 20);
