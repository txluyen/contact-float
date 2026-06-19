<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function txluyen_cf_add_menu() {
    add_options_page(
        'Contact Float Settings',
        'Contact Float',
        'manage_options',
        'txluyen-contact-float',
        'txluyen_cf_settings_page'
    );
}

function txluyen_cf_register_settings() {
    register_setting(
        'txluyen_cf_options_group',
        'txluyen_contact_float_options',
        array( 'sanitize_callback' => 'txluyen_cf_sanitize' )
    );

    add_settings_section( 'txluyen_cf_section_contact', 'Thông tin liên hệ', '__return_false', 'txluyen-contact-float' );
    add_settings_field( 'phone',            'Số điện thoại',          'txluyen_cf_field_phone',      'txluyen-contact-float', 'txluyen_cf_section_contact' );
    add_settings_field( 'zalo_url',         'Link Zalo',              'txluyen_cf_field_zalo_url',   'txluyen-contact-float', 'txluyen_cf_section_contact' );
    add_settings_field( 'banggia_shortcode', 'UX Block Shortcode (Bảng giá)', 'txluyen_cf_field_banggia', 'txluyen-contact-float', 'txluyen_cf_section_contact' );

    add_settings_section( 'txluyen_cf_section_design', 'Giao diện', '__return_false', 'txluyen-contact-float' );
    add_settings_field( 'bg_color',   'Màu nền nút',    'txluyen_cf_field_bg_color',   'txluyen-contact-float', 'txluyen_cf_section_design' );
    add_settings_field( 'text_color', 'Màu icon & text', 'txluyen_cf_field_text_color', 'txluyen-contact-float', 'txluyen_cf_section_design' );
    add_settings_field( 'position',   'Vị trí',         'txluyen_cf_field_position',   'txluyen-contact-float', 'txluyen_cf_section_design' );
}

function txluyen_cf_sanitize( $input ) {
    $clean                      = array();
    $clean['phone']             = sanitize_text_field( $input['phone'] ?? '' );
    $clean['zalo_url']          = esc_url_raw( $input['zalo_url'] ?? '' );
    $clean['banggia_shortcode'] = sanitize_text_field( $input['banggia_shortcode'] ?? '' );
    $clean['bg_color']          = sanitize_hex_color( $input['bg_color'] ?? '#1a3c6e' ) ?: '#1a3c6e';
    $clean['text_color']        = sanitize_hex_color( $input['text_color'] ?? '#ffffff' ) ?: '#ffffff';
    $clean['position']          = in_array( $input['position'] ?? 'right', array( 'right', 'left' ), true )
                                    ? $input['position']
                                    : 'right';
    return $clean;
}

function txluyen_cf_field_phone() {
    $opts = txluyen_cf_get_options();
    printf(
        '<input type="text" name="txluyen_contact_float_options[phone]" value="%s" class="regular-text" placeholder="0909 090 090">',
        esc_attr( $opts['phone'] )
    );
}

function txluyen_cf_field_zalo_url() {
    $opts = txluyen_cf_get_options();
    printf(
        '<input type="url" name="txluyen_contact_float_options[zalo_url]" value="%s" class="regular-text" placeholder="https://zalo.me/...">',
        esc_attr( $opts['zalo_url'] )
    );
}

function txluyen_cf_field_banggia() {
    $opts = txluyen_cf_get_options();
    printf(
        '<input type="text" name="txluyen_contact_float_options[banggia_shortcode]" value="%s" class="regular-text" placeholder="[ux_block id=&quot;1234&quot;]">',
        esc_attr( $opts['banggia_shortcode'] )
    );
    echo '<p class="description">Shortcode của UX Block dùng làm popup Bảng giá. Ví dụ: <code>[ux_block id="1234"]</code><br>Copy shortcode trong Flatsome → UX Blocks → chọn block → <em>Copy Shortcode</em>.</p>';
}

function txluyen_cf_field_bg_color() {
    $opts = txluyen_cf_get_options();
    printf(
        '<input type="color" name="txluyen_contact_float_options[bg_color]" value="%s">',
        esc_attr( $opts['bg_color'] )
    );
}

function txluyen_cf_field_text_color() {
    $opts = txluyen_cf_get_options();
    printf(
        '<input type="color" name="txluyen_contact_float_options[text_color]" value="%s">',
        esc_attr( $opts['text_color'] )
    );
}

function txluyen_cf_field_position() {
    $opts = txluyen_cf_get_options();
    $pos  = $opts['position'];
    echo '<label><input type="radio" name="txluyen_contact_float_options[position]" value="right"' . checked( $pos, 'right', false ) . '> Phải</label>&nbsp;&nbsp;';
    echo '<label><input type="radio" name="txluyen_contact_float_options[position]" value="left"'  . checked( $pos, 'left',  false ) . '> Trái</label>';
}

function txluyen_cf_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'contact-float' ) );
    }
    ?>
    <div class="wrap">
        <h1>Contact Float Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'txluyen_cf_options_group' );
            do_settings_sections( 'txluyen-contact-float' );
            submit_button( 'Lưu cài đặt' );
            ?>
        </form>
    </div>
    <?php
}
