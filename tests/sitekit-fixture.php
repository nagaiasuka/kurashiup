<?php
/** Local UI fixture only. Copy to wp-content/mu-plugins on a disposable localhost. */
defined('ABSPATH') || exit;
if (wp_parse_url(home_url(), PHP_URL_HOST) !== 'localhost') { return; }
define('GOOGLESITEKIT_VERSION', 'local-fixture');
add_action('rest_api_init', static function () {
    $permission = static function () { return current_user_can('manage_options'); };
    register_rest_route('google-site-kit/v1', '/modules/search-console/data/settings', [
        'methods' => 'GET', 'permission_callback' => $permission,
        'callback' => static function () { return ['propertyID' => 'LOCAL MOCK — NOT GOOGLE DATA']; },
    ]);
    register_rest_route('google-site-kit/v1', '/modules/search-console/data/searchanalytics', [
        'methods' => 'GET', 'permission_callback' => $permission,
        'callback' => static function ($request) {
            $state = get_option('kurashiup_test_search_state', 'success');
            if ($state === 'empty') { return []; }
            if ($state === 'forbidden') { return new WP_Error('forbidden', 'Local fixture', ['status' => 403]); }
            if ($state === 'error') { return new WP_Error('upstream_error', 'Local fixture', ['status' => 502]); }
            $rows = [['keys' => [$request['startDate']], 'impressions' => 12, 'clicks' => 3]];
            if ($request['startDate'] !== $request['endDate']) {
                $rows[] = ['keys' => [$request['endDate']], 'impressions' => 18, 'clicks' => 2];
            }
            return $rows;
        },
    ]);
});
