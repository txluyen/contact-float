<?php
/**
 * Plugin Name: tquanreal Contact Float
 * Plugin URI:  https://github.com/txluyen/contact-float
 * Description: Floating contact buttons (Gọi, Zalo, Bảng Giá popup) with self-contained overlay — no theme dependency.
 * Version:     1.1.2
 * Author:      Trần Quân
 * Author URI:  https://tranluyen.id.vn
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: contact-float
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TQUANREAL_CF_VERSION', '1.1.2' );
define( 'TQUANREAL_CF_DIR', plugin_dir_path( __FILE__ ) );
define( 'TQUANREAL_CF_URL', plugin_dir_url( __FILE__ ) );

define( 'TQUANREAL_CF_ICON_PHONE', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.02l-2.2 2.19z"/></svg>' );

define( 'TQUANREAL_CF_ICON_ZALO', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="20" height="20" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"><path d="M13 19h25L13 45h27M35 19v26"/></svg>' );

define( 'TQUANREAL_CF_ICON_BANGGIA', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17h8v-1H8v1zm0-3h8v-1H8v1zm0-3h5v-1H8v1z"/></svg>' );

/**
 * Returns plugin options merged with defaults.
 *
 * @return array{phone: string, zalo_url: string, banggia_block_id: string, bg_color: string, text_color: string, position: string}
 */
function tquanreal_cf_get_options() {
    $defaults = array(
        'phone'             => '',
        'zalo_url'          => '',
        'banggia_shortcode' => '',
        'bg_color'          => '#1a3c6e',
        'text_color'        => '#ffffff',
        'position'          => 'right',
    );
    $saved = get_option( 'tquanreal_contact_float_options', array() );
    return wp_parse_args( $saved, $defaults );
}

require_once TQUANREAL_CF_DIR . 'admin/settings.php';

add_action( 'wp_enqueue_scripts', 'tquanreal_cf_enqueue' );
add_action( 'wp_footer',          'tquanreal_cf_render' );
add_action( 'admin_menu',         'tquanreal_cf_add_menu' );
add_action( 'admin_init',         'tquanreal_cf_register_settings' );

/**
 * Enqueue frontend CSS/JS and inject CSS custom properties from saved options.
 */
function tquanreal_cf_enqueue() {
    $opts = tquanreal_cf_get_options();

    wp_enqueue_style(
        'tquanreal-cf-style',
        TQUANREAL_CF_URL . 'assets/style.css',
        array(),
        TQUANREAL_CF_VERSION
    );

    // Convert hex bg color to R, G, B for rgba() in pulse animation
    $rgb = sscanf( $opts['bg_color'], '#%02x%02x%02x' );
    $r   = isset( $rgb[0] ) ? (int) $rgb[0] : 26;
    $g   = isset( $rgb[1] ) ? (int) $rgb[1] : 60;
    $b   = isset( $rgb[2] ) ? (int) $rgb[2] : 110;

    $inline_css = sprintf(
        ':root{--tquanreal-cf-bg:%s;--tquanreal-cf-color:%s;--tquanreal-cf-bg-rgb:%d,%d,%d;}',
        esc_attr( $opts['bg_color'] ),
        esc_attr( $opts['text_color'] ),
        $r, $g, $b
    );
    wp_add_inline_style( 'tquanreal-cf-style', $inline_css );

    // Depend on flatsome-js so our script loads after Flatsome's popup system
    $deps = wp_script_is( 'flatsome-js', 'registered' ) ? array( 'flatsome-js' ) : array();
    wp_enqueue_script(
        'tquanreal-cf-script',
        TQUANREAL_CF_URL . 'assets/script.js',
        $deps,
        TQUANREAL_CF_VERSION,
        true
    );
}

/**
 * Render the floating widget HTML into wp_footer.
 * Returns early (outputs nothing) if all contact fields are empty.
 */
function tquanreal_cf_render() {
    $opts       = tquanreal_cf_get_options();
    $phone      = $opts['phone'];
    $zalo_url   = $opts['zalo_url'];
        $shortcode  = $opts['banggia_shortcode'];
    $pos_class  = $opts['position'] === 'left' ? 'tquanreal-cf-left' : 'tquanreal-cf-right';

    if ( empty( $phone ) && empty( $zalo_url ) && empty( $shortcode ) ) {
        return;
    }

    $phone_raw = preg_replace( '/[^0-9+]/', '', $phone );
    ?>
    <div class="tquanreal-cf-widget <?php echo esc_attr( $pos_class ); ?>">

        <?php if ( $phone ) : ?>
        <a class="tquanreal-cf-btn tquanreal-cf-call tquanreal-cf-pulse"
           href="tel:<?php echo esc_attr( $phone_raw ); ?>">
            <span class="tquanreal-cf-icon"><?php echo TQUANREAL_CF_ICON_PHONE; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="tquanreal-cf-label">GỌI</span>
            <span class="tquanreal-cf-phone-number"><?php echo esc_html( $phone ); ?></span>
        </a>
        <?php endif; ?>

        <?php if ( $zalo_url ) : ?>
        <a class="tquanreal-cf-btn tquanreal-cf-zalo tquanreal-cf-pulse"
           href="<?php echo esc_url( $zalo_url ); ?>"
           target="_blank" rel="noopener noreferrer">
            <span class="tquanreal-cf-icon"><?php echo TQUANREAL_CF_ICON_ZALO; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="tquanreal-cf-label">ZALO</span>
        </a>
        <?php endif; ?>

        <?php if ( $shortcode ) : ?>
        <a class="tquanreal-cf-btn tquanreal-cf-banggia" href="#" aria-haspopup="dialog">
            <span class="tquanreal-cf-icon"><?php echo TQUANREAL_CF_ICON_BANGGIA; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="tquanreal-cf-label">BẢNG GIÁ</span>
        </a>

        <div id="tquanreal-cf-popup" class="tquanreal-cf-popup-overlay" role="dialog" aria-modal="true" aria-label="Bảng giá" hidden>
            <div class="tquanreal-cf-popup-inner">
                <button class="tquanreal-cf-popup-close" aria-label="Đóng">&times;</button>
                <div class="tquanreal-cf-popup-content">
                    <?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php
}
