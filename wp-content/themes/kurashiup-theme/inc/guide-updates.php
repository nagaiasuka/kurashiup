<?php
defined('ABSPATH') || exit;

/**
 * Apply a Git-reviewed snapshot to a public guide.
 * Keep the destination post ID so existing links and analytics survive.
 */
function kurashiup_apply_guide_update($directory) {
    $raw = file_get_contents($directory . '/manifest.json');
    $guide = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    $revision = hash('sha256', $raw);
    $slug = basename($directory);
    if ($guide['slug'] !== $slug || ! preg_match('/^(?:[a-z0-9-]|%[0-9a-f]{2})+$/D', $slug)) {
        throw new RuntimeException('Invalid guide slug.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $guide['date_gmt'], new DateTimeZone('UTC'));
    if (! $date || $date->format('Y-m-d H:i:s') !== $guide['date_gmt'] || $date->getTimestamp() > time()) {
        throw new RuntimeException('Invalid published date.');
    }
    $revision_key = '_kurashiup_guide_git_revision';
    $creation_key = '_kurashiup_guide_git_creation';
    $existing = get_page_by_path($slug, OBJECT, ['review_guide']);
    if ($existing && get_post_meta($existing->ID, $revision_key, true) === $revision) {
        return;
    }
    $lock = 'kurashiup_guide_update_lock_' . hash('sha256', $slug);
    if (! add_option($lock, time(), '', false)) {
        return;
    }
    try {
        $post = get_page_by_path($slug, OBJECT, ['review_guide']);
        $allow_create = ($guide['allow_create'] ?? false) === true;
        if (hash_file('sha256', $directory . '/manifest.json') !== $revision
            || (! $post && ! $allow_create)
            || ($post && (get_post_meta($post->ID, $revision_key, true) === $revision
                || $post->post_password !== ''
                || ($post->post_status !== 'publish' && ! ($allow_create && $post->post_status === 'draft'
                    && get_post_meta($post->ID, $creation_key, true) === $slug))))) {
            return;
        }
        foreach ($guide['images'] as $name) {
            if (! preg_match('/^([a-f0-9]{64})\.(jpg|png|webp|gif|avif)$/D', $name, $parts)
                || ! is_readable($directory . '/' . $name)
                || hash_file('sha256', $directory . '/' . $name) !== $parts[1]) {
                return; // FTPS may still be transferring this release.
            }
        }
        $uploads = wp_get_upload_dir();
        $target = $uploads['basedir'] . '/kurashiup-guide-git';
        if ($guide['images'] && ! wp_mkdir_p($target)) {
            throw new RuntimeException('Cannot create guide image directory.');
        }
        foreach ($guide['images'] as $name) {
            $file = $target . '/' . $name;
            $digest = strstr($name, '.', true);
            if (is_file($file) && hash_file('sha256', $file) === $digest) {
                continue;
            }
            $temp = tempnam($target, 'guide-');
            try {
                if (! $temp || ! copy($directory . '/' . $name, $temp)
                    || hash_file('sha256', $temp) !== $digest
                    || ! chmod($temp, 0644) || ! rename($temp, $file)) {
                    throw new RuntimeException('Cannot install guide image.');
                }
            } finally {
                if ($temp && is_file($temp)) {
                    unlink($temp);
                }
            }
        }

        if (! $post) {
            $authors = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
            if (! $authors) {
                throw new RuntimeException('No existing administrator author found.');
            }
            $id = wp_insert_post(wp_slash([
                'post_type' => 'review_guide',
                'post_status' => 'draft',
                'post_name' => $slug,
                'post_title' => $guide['title'],
                'post_author' => $authors[0]->ID,
                'comment_status' => 'closed',
                'meta_input' => [$creation_key => $slug],
            ]), true);
            if (is_wp_error($id) || ! $id || get_post_field('post_name', $id) !== $slug) {
                throw new RuntimeException('Could not create the guide draft at the approved slug.');
            }
            $post = get_post($id);
        }

        $thumbnail = 0;
        if ($guide['featured']) {
            if (! in_array($guide['featured'], $guide['images'], true)) {
                throw new RuntimeException('Missing featured image.');
            }
            $file = $target . '/' . $guide['featured'];
            $thumbnail = (int) get_post_thumbnail_id($post->ID);
            $current = $thumbnail ? get_attached_file($thumbnail) : false;
            if (! $current || ! is_file($current)
                || hash_file('sha256', $current) !== strstr($guide['featured'], '.', true)
                || get_post_meta($thumbnail, '_wp_attachment_image_alt', true) !== $guide['alt']) {
                $thumbnail = kurashiup_guide_update_attachment($post, $file, $guide['alt']);
            }
        }

        $content = str_replace('%%GUIDE_UPLOADS%%', esc_url_raw($uploads['baseurl'] . '/kurashiup-guide-git'), $guide['content']);
        $priority = has_filter('content_save_pre', 'wp_filter_post_kses');
        if ($priority !== false) {
            remove_filter('content_save_pre', 'wp_filter_post_kses', $priority);
        }
        try {
            $result = wp_update_post(wp_slash([
                'ID' => $post->ID,
                'post_title' => $guide['title'],
                'post_content' => $content,
                'post_excerpt' => $guide['excerpt'],
                'post_date_gmt' => $guide['date_gmt'],
                'post_date' => get_date_from_gmt($guide['date_gmt']),
                'edit_date' => true,
            ]), true);
        } finally {
            if ($priority !== false) {
                add_filter('content_save_pre', 'wp_filter_post_kses', $priority);
            }
        }
        if (is_wp_error($result) || $result !== $post->ID) {
            throw new RuntimeException('Could not update guide post.');
        }
        foreach (['guide_format', 'meta_description', 'review_basis', 'review_checked_at'] as $key) {
            $key = '_kurashiup_' . $key;
            update_post_meta($post->ID, $key, wp_slash($guide['meta'][$key]));
        }
        if ($thumbnail) {
            set_post_thumbnail($post->ID, $thumbnail);
        } else {
            delete_post_thumbnail($post->ID);
        }
        if ((int) get_post_thumbnail_id($post->ID) !== $thumbnail) {
            throw new RuntimeException('Could not update featured image.');
        }
        if ($post->post_status === 'draft') {
            wp_publish_post($post->ID);
            if (get_post_status($post->ID) !== 'publish') {
                throw new RuntimeException('Could not publish the prepared guide.');
            }
        }
        update_post_meta($post->ID, $revision_key, $revision);
    } finally {
        delete_option($lock);
    }
}

function kurashiup_guide_update_attachment($post, $file, $alt) {
    $key = '_kurashiup_guide_attachment';
    $identity = hash('sha256', basename($file) . "\n" . $alt);
    $matches = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_parent' => $post->ID,
        'fields' => 'ids',
        'numberposts' => 1,
        'meta_key' => $key,
        'meta_value' => $identity,
    ]);
    if ($matches && wp_attachment_is_image($matches[0])) {
        return (int) $matches[0];
    }

    $id = wp_insert_attachment(wp_slash([
        'post_mime_type' => wp_get_image_mime($file),
        'post_title' => $post->post_title,
        'post_status' => 'inherit',
        'post_author' => $post->post_author,
        'meta_input' => [$key => $identity, '_wp_attachment_image_alt' => $alt],
    ]), $file, $post->ID, true);
    if (is_wp_error($id) || ! $id) {
        throw new RuntimeException('Could not register featured image.');
    }
    // The original is already available and validated by checksum.
    $size = wp_getimagesize($file);
    wp_update_attachment_metadata($id, [
        'width' => $size[0],
        'height' => $size[1],
        'file' => _wp_relative_upload_path($file),
        'sizes' => [],
    ]);
    return $id;
}

function kurashiup_apply_deployed_guide_updates() {
    if (wp_installing() || ! post_type_exists('review_guide')
        || ! in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['amocreo.com', 'www.amocreo.com'], true)) {
        return;
    }
    foreach (glob(get_template_directory() . '/releases/guide-updates/*/manifest.json') ?: [] as $manifest) {
        try {
            kurashiup_apply_guide_update(dirname($manifest));
        } catch (Throwable $error) {
            error_log('KurashiUp guide update: ' . $error->getMessage());
        }
    }
}
add_action('wp_loaded', 'kurashiup_apply_deployed_guide_updates', 21);

// The deployment job checks that WordPress has applied the exact packaged revision.
add_action('template_redirect', static function () {
    if (isset($_GET['guide_deploy']) && is_singular('review_guide')) {
        $revision = get_post_meta(get_queried_object_id(), '_kurashiup_guide_git_revision', true);
        if (preg_match('/^[a-f0-9]{64}$/D', $revision)) {
            nocache_headers();
            header('X-KurashiUp-Guide-Revision: ' . $revision);
        }
    }
});
