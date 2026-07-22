<?php

function kurashiup_add_product_meta_boxes()
{
    add_meta_box(
        'kurashiup_product_fields',
        '商品情報',
        'kurashiup_product_fields_callback',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'kurashiup_add_product_meta_boxes');

function kurashiup_product_fields_callback($post)
{
    $amazon_url = get_post_meta($post->ID, '_kurashiup_amazon_url', true);
    $asin = get_post_meta($post->ID, '_kurashiup_asin', true);
    $reference_price = get_post_meta($post->ID, '_kurashiup_reference_price', true);
    $short_description = get_post_meta($post->ID, '_kurashiup_short_description', true);
    $amazon_click_count = (int) get_post_meta($post->ID, '_kurashiup_amazon_click_count', true);
    $is_featured = '1' === get_post_meta($post->ID, '_kurashiup_is_featured', true);

    wp_nonce_field('kurashiup_save_product_fields', 'kurashiup_product_fields_nonce');
    ?>

    <p>
        <label for="kurashiup_amazon_url">Amazon URL</label><br>
        <input type="url" id="kurashiup_amazon_url" name="kurashiup_amazon_url" value="<?php echo esc_attr($amazon_url); ?>" style="width:100%;">
    </p>

    <p>
        <label for="kurashiup_asin">ASIN</label><br>
        <input type="text" id="kurashiup_asin" name="kurashiup_asin" value="<?php echo esc_attr($asin); ?>" style="width:100%;">
    </p>

    <p>
        <label for="kurashiup_reference_price">参考価格（公開画面では非表示）</label><br>
        <input type="text" id="kurashiup_reference_price" name="kurashiup_reference_price" value="<?php echo esc_attr($reference_price); ?>" style="width:100%;">
    </p>

    <p>
        <label for="kurashiup_short_description">短い説明</label><br>
        <textarea id="kurashiup_short_description" name="kurashiup_short_description" rows="5" style="width:100%;"><?php echo esc_textarea($short_description); ?></textarea>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="kurashiup_is_featured"
                value="1"
                <?php checked($is_featured); ?>
            >
            編集部おすすめに表示する
        </label>
    </p>

    <p>
        <strong>Amazonクリック数</strong><br>
        <span><?php echo esc_html(number_format_i18n($amazon_click_count)); ?></span>
    </p>

    <?php
}

function kurashiup_save_product_fields($post_id)
{
    if (! isset($_POST['kurashiup_product_fields_nonce'])) {
        return;
    }

    if (! wp_verify_nonce($_POST['kurashiup_product_fields_nonce'], 'kurashiup_save_product_fields')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        '_kurashiup_amazon_url' => 'kurashiup_amazon_url',
        '_kurashiup_asin' => 'kurashiup_asin',
        '_kurashiup_reference_price' => 'kurashiup_reference_price',
        '_kurashiup_short_description' => 'kurashiup_short_description',
    ];

    foreach ($fields as $meta_key => $field_name) {
        if (isset($_POST[$field_name])) {
            update_post_meta(
                $post_id,
                $meta_key,
                sanitize_text_field($_POST[$field_name])
            );
        }
    }

    update_post_meta(
        $post_id,
        '_kurashiup_is_featured',
        isset($_POST['kurashiup_is_featured']) ? '1' : '0'
    );
}
add_action('save_post_product', 'kurashiup_save_product_fields');
