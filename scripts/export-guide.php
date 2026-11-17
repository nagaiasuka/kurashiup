<?php
// Runs inside the local WordPress container. The export is written to stdout.
require '/var/www/html/wp-load.php';

if (home_url() !== 'http://localhost:8080') {
    throw new RuntimeException('Export must run in local WordPress.');
}
$slug = $argv[1] ?? '';
$post = get_page_by_path($slug, OBJECT, 'review_guide');
if (! $post || $post->post_status !== 'publish' || $post->post_password !== '') {
    throw new RuntimeException('Select a published, public guide by its slug.');
}

$uploads = wp_get_upload_dir();
$files = [];
$store_image = static function ($path) use (&$files) {
    $bytes = is_file($path) ? file_get_contents($path) : false;
    $type = $bytes !== false ? wp_get_image_mime($path) : false;
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];
    if (! isset($extensions[$type])) {
        throw new RuntimeException('Missing or unsupported image: ' . $path);
    }
    $name = hash('sha256', $bytes) . '.' . $extensions[$type];
    $files[$name] = base64_encode($bytes);
    return $name;
};

$prefix = wp_parse_url($uploads['baseurl'], PHP_URL_PATH) . '/';
$content = preg_replace_callback(
    '~(?:' . preg_quote(home_url(), '~') . ')?' . preg_quote($prefix, '~') . '[^\s"\'<>?,#]+~',
    static function ($match) use ($uploads, $prefix, $store_image) {
        $offset = strpos($match[0], $prefix) + strlen($prefix);
        $relative = rawurldecode(substr($match[0], $offset));
        $path = realpath($uploads['basedir'] . '/' . $relative);
        if (! $path || ! str_starts_with($path, realpath($uploads['basedir']) . '/')) {
            throw new RuntimeException('Invalid local image: ' . $relative);
        }
        return '%%GUIDE_UPLOADS%%/' . $store_image($path);
    },
    $post->post_content
);
$thumbnail = get_post_thumbnail_id($post->ID);
$meta = [];
foreach (['guide_format', 'meta_description', 'review_basis', 'review_checked_at'] as $key) {
    $meta['_kurashiup_' . $key] = get_post_meta($post->ID, '_kurashiup_' . $key, true);
}

echo json_encode([
    'guide' => [
        'slug' => $post->post_name,
        'title' => $post->post_title,
        'content' => $content,
        'excerpt' => $post->post_excerpt,
        'date_gmt' => $post->post_date_gmt,
        'featured' => $thumbnail ? $store_image(get_attached_file($thumbnail)) : null,
        'alt' => $thumbnail ? get_post_meta($thumbnail, '_wp_attachment_image_alt', true) : '',
        'meta' => $meta,
    ],
    'images' => $files,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
