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

    add_settings_section( 'tquanreal_cf_section_chat', 'Chat Box (Firebase)', '__return_false', 'txluyen-contact-float' );
    add_settings_field( 'chat_enabled',          'Bật chat box',         'tquanreal_cf_field_chat_enabled',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'firebase_api_key',      'Firebase API Key',     'tquanreal_cf_field_fb_api_key',      'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'firebase_auth_domain',  'Firebase Auth Domain', 'tquanreal_cf_field_fb_auth_domain',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'firebase_database_url', 'Firebase Database URL','tquanreal_cf_field_fb_db_url',       'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'firebase_project_id',   'Firebase Project ID',  'tquanreal_cf_field_fb_project_id',   'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'firebase_app_id',       'Firebase App ID',      'tquanreal_cf_field_fb_app_id',       'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'chat_admin_email',      'Admin Email (Firebase)','tquanreal_cf_field_admin_email',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'chat_admin_password',   'Admin Password',       'tquanreal_cf_field_admin_password',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'chat_panel_password',   'Admin Panel Password', 'tquanreal_cf_field_panel_password',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
    add_settings_field( 'chat_license_key',      'License Key (Premium)','tquanreal_cf_field_license_key',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
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
    $clean['chat_enabled']          = isset( $input['chat_enabled'] ) ? '1' : '0';
    $clean['firebase_api_key']      = sanitize_text_field( $input['firebase_api_key'] ?? '' );
    $clean['firebase_auth_domain']  = sanitize_text_field( $input['firebase_auth_domain'] ?? '' );
    $clean['firebase_database_url'] = esc_url_raw( $input['firebase_database_url'] ?? '' );
    $clean['firebase_project_id']   = sanitize_text_field( $input['firebase_project_id'] ?? '' );
    $clean['firebase_app_id']       = sanitize_text_field( $input['firebase_app_id'] ?? '' );
    $clean['chat_admin_email']      = sanitize_email( $input['chat_admin_email'] ?? '' );
    $existing = tquanreal_cf_get_options();
    $clean['chat_admin_password'] = ! empty( $input['chat_admin_password'] )
        ? sanitize_text_field( $input['chat_admin_password'] )
        : $existing['chat_admin_password'];
    $clean['chat_panel_password']   = sanitize_text_field( $input['chat_panel_password'] ?? '' );
    $clean['chat_license_key']      = sanitize_text_field( $input['chat_license_key'] ?? '' );
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

function tquanreal_cf_field_chat_enabled() {
    $opts = tquanreal_cf_get_options();
    printf(
        '<input type="checkbox" name="txluyen_contact_float_options[chat_enabled]" value="1" %s> Hiển thị chat bubble trên website',
        checked( $opts['chat_enabled'], '1', false )
    );
}

function tquanreal_cf_field_fb_api_key() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[firebase_api_key]" value="%s" class="regular-text">', esc_attr( $opts['firebase_api_key'] ) );
}

function tquanreal_cf_field_fb_auth_domain() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[firebase_auth_domain]" value="%s" class="regular-text" placeholder="your-project.firebaseapp.com">', esc_attr( $opts['firebase_auth_domain'] ) );
}

function tquanreal_cf_field_fb_db_url() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="url" name="txluyen_contact_float_options[firebase_database_url]" value="%s" class="regular-text" placeholder="https://your-project-default-rtdb.asia-southeast1.firebasedatabase.app">', esc_attr( $opts['firebase_database_url'] ) );
}

function tquanreal_cf_field_fb_project_id() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[firebase_project_id]" value="%s" class="regular-text">', esc_attr( $opts['firebase_project_id'] ) );
}

function tquanreal_cf_field_fb_app_id() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[firebase_app_id]" value="%s" class="large-text">', esc_attr( $opts['firebase_app_id'] ) );
}

function tquanreal_cf_field_admin_email() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="email" name="txluyen_contact_float_options[chat_admin_email]" value="%s" class="regular-text">', esc_attr( $opts['chat_admin_email'] ) );
    echo '<p class="description">Email đăng nhập Firebase của admin (dùng trong admin panel).</p>';
}

function tquanreal_cf_field_admin_password() {
    echo '<input type="password" name="txluyen_contact_float_options[chat_admin_password]" value="" class="regular-text" placeholder="Nhập password mới để thay đổi">';
    echo '<p class="description">Để trống nếu không muốn thay đổi password hiện tại.</p>';
}

function tquanreal_cf_field_panel_password() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[chat_panel_password]" value="%s" class="regular-text" placeholder="mat-khau-admin-panel">', esc_attr( $opts['chat_panel_password'] ) );
    echo '<p class="description">Mật khẩu bảo vệ trang admin panel chat.</p>';
}

function tquanreal_cf_field_license_key() {
    $opts = tquanreal_cf_get_options();
    printf( '<input type="text" name="txluyen_contact_float_options[chat_license_key]" value="%s" class="regular-text" placeholder="Để trống = bản miễn phí">', esc_attr( $opts['chat_license_key'] ) );
    echo '<p class="description">License key kích hoạt tính năng Premium (lưu lịch sử chat).</p>';
}
