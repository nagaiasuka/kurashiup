<?php

function kurashiup_register_import_api_routes()
{
    register_rest_route(
        'kurashiup/v1',
        '/import-product',
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'kurashiup_handle_import_product',
            'permission_callback' => 'kurashiup_validate_import_api_request',
        ]
    );
}
add_action('rest_api_init', 'kurashiup_register_import_api_routes');

function kurashiup_handle_import_product(WP_REST_Request $request)
{
    $title = sanitize_text_field((string) $request->get_param('title'));
    $external_product_id = sanitize_text_field((string) $request->get_param('external_product_id'));
    $asin = sanitize_text_field((string) $request->get_param('asin'));

    if ($title === '') {
        return new WP_REST_Response([
            'success' => false,
            'wp_post_id' => null,
            'synced_at' => current_time('mysql'),
            'error' => 'The title field is required.',
        ], 400);
    }

    if ($external_product_id === '' && $asin === '') {
        return new WP_REST_Response([
            'success' => false,
            'wp_post_id' => null,
            'synced_at' => current_time('mysql'),
            'error' => 'Either external_product_id or asin is required.',
        ], 400);
    }

    $post_status = kurashiup_normalize_import_status($request->get_param('status'));
    $amazon_image_url = (string) ($request->get_param('amazon_image_url') ?: $request->get_param('image'));
    $image_validation_error = kurashiup_validate_amazon_image_url($amazon_image_url);

    if ($image_validation_error !== '') {
        return new WP_REST_Response([
            'success' => false,
            'wp_post_id' => null,
            'synced_at' => current_time('mysql'),
            'error' => $image_validation_error,
        ], 400);
    }

    $existing_post_id = kurashiup_find_existing_product_post(
        $request->get_param('wp_post_id'),
        $external_product_id,
        $asin
    );

    $postarr = [
        'post_type' => 'product',
        'post_status' => $post_status,
        'post_title' => $title,
    ];

    if ($existing_post_id) {
        $postarr['ID'] = $existing_post_id;
        $post_id = wp_update_post($postarr, true);
        $action = 'updated';
    } else {
        $post_id = wp_insert_post($postarr, true);
        $action = 'created';
    }

    if (is_wp_error($post_id)) {
        return new WP_REST_Response([
            'success' => false,
            'wp_post_id' => null,
            'synced_at' => current_time('mysql'),
            'error' => $post_id->get_error_message(),
        ], 500);
    }

    $meta_updates = [
        '_kurashiup_external_product_id' => $external_product_id,
        '_kurashiup_source_status' => sanitize_text_field((string) $request->get_param('status')),
        '_kurashiup_amazon_url' => esc_url_raw((string) $request->get_param('amazon_url')),
        '_kurashiup_amazon_image_url' => esc_url_raw($amazon_image_url),
        '_kurashiup_asin' => $asin,
        '_kurashiup_reference_price' => sanitize_text_field((string) $request->get_param('reference_price')),
        '_kurashiup_short_description' => sanitize_textarea_field((string) ($request->get_param('short_description') ?: $request->get_param('description'))),
        '_kurashiup_is_featured' => kurashiup_request_truthy($request->get_param('is_featured')) ? '1' : '0',
    ];

    foreach ($meta_updates as $meta_key => $meta_value) {
        if ($meta_value !== '') {
            update_post_meta($post_id, $meta_key, $meta_value);
        } elseif ($meta_key === '_kurashiup_is_featured') {
            update_post_meta($post_id, $meta_key, '0');
        }
    }

    $taxonomy_result = kurashiup_assign_import_terms($post_id, [
        'product_category' => $request->get_param('categories') ?: $request->get_param('category'),
        'product_tag' => $request->get_param('tags') ?: $request->get_param('tag'),
        'product_brand' => $request->get_param('brands') ?: $request->get_param('brand'),
    ]);

    if (is_wp_error($taxonomy_result)) {
        return new WP_REST_Response([
            'success' => false,
            'wp_post_id' => $post_id,
            'synced_at' => current_time('mysql'),
            'error' => $taxonomy_result->get_error_message(),
        ], 400);
    }

    return new WP_REST_Response([
        'success' => true,
        'action' => $action,
        'wp_post_id' => $post_id,
        'post_status' => get_post_status($post_id),
        'synced_at' => current_time('mysql'),
        'error' => '',
        'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
    ], $action === 'created' ? 201 : 200);
}

function kurashiup_validate_import_api_request(WP_REST_Request $request)
{
    $expected_token = kurashiup_get_import_api_token();

    if ($expected_token === '') {
        return new WP_Error(
            'kurashiup_import_token_not_configured',
            'Import API token is not configured.',
            [
                'status' => 500,
                'success' => false,
                'wp_post_id' => null,
                'synced_at' => current_time('mysql'),
                'error' => 'Import API token is not configured.',
            ]
        );
    }

    $provided_token = kurashiup_get_request_import_api_token($request);

    if ($provided_token === '' || ! hash_equals($expected_token, $provided_token)) {
        return new WP_Error(
            'kurashiup_invalid_import_token',
            'Invalid import API token.',
            [
                'status' => 401,
                'success' => false,
                'wp_post_id' => null,
                'synced_at' => current_time('mysql'),
                'error' => 'Invalid import API token.',
            ]
        );
    }

    return true;
}

function kurashiup_get_import_api_token()
{
    $constant_names = [
        'KURASHIUP_IMPORT_TOKEN',
        'KURASHIUP_IMPORT_API_TOKEN',
    ];

    foreach ($constant_names as $constant_name) {
        if (defined($constant_name) && constant($constant_name)) {
            return trim((string) constant($constant_name));
        }
    }

    $environment_names = [
        'KURASHIUP_IMPORT_TOKEN',
        'KURASHIUP_IMPORT_API_TOKEN',
    ];

    foreach ($environment_names as $environment_name) {
        $token = getenv($environment_name);

        if (is_string($token) && trim($token) !== '') {
            return trim($token);
        }
    }

    return '';
}

function kurashiup_get_request_import_api_token(WP_REST_Request $request)
{
    $header_token = (string) $request->get_header('X-Kurashiup-Token');

    if ($header_token !== '') {
        return trim($header_token);
    }

    return trim((string) $request->get_param('secret'));
}

function kurashiup_normalize_import_status($raw_status)
{
    $status = sanitize_text_field((string) $raw_status);
    $status_map = [
        'publish' => 'publish',
        'published' => 'publish',
        '公開' => 'publish',
        '公開済み' => 'publish',
        'draft' => 'draft',
        '下書き' => 'draft',
        '公開準備完了' => 'draft',
        'pending' => 'pending',
        'review' => 'pending',
        'レビュー待ち' => 'pending',
        'private' => 'private',
        '非公開' => 'private',
    ];

    return $status_map[$status] ?? 'draft';
}

function kurashiup_find_existing_product_post($wp_post_id, $external_product_id, $asin)
{
    $candidate_post_id = absint($wp_post_id);

    if ($candidate_post_id && get_post_type($candidate_post_id) === 'product') {
        return $candidate_post_id;
    }

    $meta_candidates = [
        '_kurashiup_external_product_id' => $external_product_id,
        '_kurashiup_asin' => $asin,
    ];

    foreach ($meta_candidates as $meta_key => $meta_value) {
        if ($meta_value === '') {
            continue;
        }

        $posts = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
        ]);

        if (! empty($posts)) {
            return (int) $posts[0];
        }
    }

    return 0;
}

function kurashiup_assign_import_terms($post_id, array $taxonomy_input)
{
    foreach ($taxonomy_input as $taxonomy => $raw_terms) {
        $terms = kurashiup_normalize_import_terms($raw_terms);

        if (empty($terms)) {
            continue;
        }

        $term_ids = [];

        foreach ($terms as $term_name) {
            $existing_term = term_exists($term_name, $taxonomy);

            if ($existing_term === 0 || $existing_term === null) {
                $created_term = wp_insert_term($term_name, $taxonomy);

                if (is_wp_error($created_term)) {
                    return $created_term;
                }

                $term_ids[] = (int) $created_term['term_id'];
                continue;
            }

            $term_ids[] = (int) (is_array($existing_term) ? $existing_term['term_id'] : $existing_term);
        }

        $set_result = wp_set_object_terms($post_id, $term_ids, $taxonomy, false);

        if (is_wp_error($set_result)) {
            return $set_result;
        }
    }

    return true;
}

function kurashiup_normalize_import_terms($raw_terms)
{
    if (is_array($raw_terms)) {
        $terms = $raw_terms;
    } else {
        $terms = explode(',', (string) $raw_terms);
    }

    $terms = array_map('wp_unslash', $terms);
    $terms = array_map('trim', $terms);
    $terms = array_map('sanitize_text_field', $terms);
    $terms = array_filter($terms, static function ($term) {
        return $term !== '';
    });

    return array_values(array_unique($terms));
}

function kurashiup_request_truthy($value)
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string) $value));

    return in_array($normalized, ['1', 'true', 'yes', 'on', '公開', 'おすすめ', 'featured'], true);
}

function kurashiup_validate_amazon_image_url($image_url)
{
    $image_url = trim($image_url);

    if ($image_url === '') {
        return '';
    }

    if (! wp_http_validate_url($image_url)) {
        return 'The image field must be a valid URL.';
    }

    if (strpos($image_url, '*') !== false || strpos($image_url, '\\_') !== false) {
        return 'The image URL format is invalid. Use a direct Amazon image URL like https://m.media-amazon.com/images/I/filename._AC_SL1309_.jpg';
    }

    $host = wp_parse_url($image_url, PHP_URL_HOST);

    if (! is_string($host) || stripos($host, 'm.media-amazon.com') === false) {
        return 'The image URL must use the m.media-amazon.com host.';
    }

    return '';
}
