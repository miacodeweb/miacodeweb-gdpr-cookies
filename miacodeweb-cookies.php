<?php
/**
 * Plugin Name: MiaCodeWEB GDPR Cookie Blocker
 * Plugin URI: https://miacodeweb.com
 * Description: A lightweight and customizable WordPress plugin to comply with GDPR/LGPD regulations, blocking tracking scripts until user consent.
 * Version: 1.1.0
 * Author: MiaCodeWEB
 * Author URI: https://miacodeweb.com
 * License: GPLv2 or later
 * Text Domain: miacodeweb-gdpr
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiaCodeWEB_GDPR_Plugin {

    private $cookie_name = 'mcw_gdpr_consent';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_footer', array( $this, 'show_cookie_banner' ) );
        add_action( 'wp_head', array( $this, 'inject_consented_scripts' ) );
    }

    public function create_admin_menu() {
        add_menu_page(
            __( 'MiaCodeWEB GDPR Settings', 'miacodeweb-gdpr' ),
            __( 'GDPR Cookies', 'miacodeweb-gdpr' ),
            'manage_options',
            'miacodeweb-gdpr',
            array( $this, 'settings_page_html' ),
            'dashicons-shield',
            80
        );
    }

    public function register_settings() {
        // Registro con sanitización de seguridad exigida por WP
        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_color_fondo', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#1a237e'
        ) );
        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_texto_aviso', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __( 'We use cookies to improve your experience.', 'miacodeweb-gdpr' )
        ) );
        // Los scripts no se sanitizan con strip_tags porque rompería el código JS.
        // Solo administradores pueden guardar esto (validado por manage_options).
        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_scripts_tracking' );
    }

    public function settings_page_html() {
        // Doble control de seguridad
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Sanitizar y escapar variables de la URL
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'appearance';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( '🛡️ MiaCodeWEB GDPR Configuration', 'miacodeweb-gdpr' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=miacodeweb-gdpr&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Appearance', 'miacodeweb-gdpr' ); ?></a>
                <a href="?page=miacodeweb-gdpr&tab=scripts" class="nav-tab <?php echo $active_tab == 'scripts' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Scripts (Blocking)', 'miacodeweb-gdpr' ); ?></a>
                <a href="?page=miacodeweb-gdpr&tab=system" class="nav-tab <?php echo $active_tab == 'system' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Status ⭐', 'miacodeweb-gdpr' ); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'mcw_gdpr_options' ); ?>

                <?php if ( $active_tab == 'appearance' ) : ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Banner Text', 'miacodeweb-gdpr' ); ?></th>
                        <td><textarea name="mcw_gdpr_texto_aviso" rows="3" cols="50"><?php echo esc_textarea( get_option('mcw_gdpr_texto_aviso', __( 'We use cookies to improve your experience.', 'miacodeweb-gdpr' )) ); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Background Color (Hex)', 'miacodeweb-gdpr' ); ?></th>
                        <td><input type="color" name="mcw_gdpr_color_fondo" value="<?php echo esc_attr( get_option('mcw_gdpr_color_fondo', '#1a237e') ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                
                <?php elseif ( $active_tab == 'scripts' ) : ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Tracking Scripts', 'miacodeweb-gdpr' ); ?><br><small><?php esc_html_e( '(Blocked until user consent)', 'miacodeweb-gdpr' ); ?></small></th>
                        <td>
                            <textarea name="mcw_gdpr_scripts_tracking" rows="10" cols="60"><?php echo esc_textarea( get_option('mcw_gdpr_scripts_tracking') ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Paste your Google Analytics, Meta Pixel, etc. <script> tags here.', 'miacodeweb-gdpr' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>

                <?php elseif ( $active_tab == 'system' ) : ?>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #00a0d2; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h3><?php esc_html_e( 'Server Diagnostics', 'miacodeweb-gdpr' ); ?></h3>
                    <p><strong><?php esc_html_e( 'PHP Version:', 'miacodeweb-gdpr' ); ?></strong> <?php echo esc_html( phpversion() ); ?></p>
                    <p><strong><?php esc_html_e( 'Web Server:', 'miacodeweb-gdpr' ); ?></strong> <?php echo isset($_SERVER['SERVER_SOFTWARE']) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : esc_html__( 'Unknown', 'miacodeweb-gdpr' ); ?></p>
                    <hr>
                    <h4>🚀 <?php esc_html_e( 'Does your website load slowly or need professional optimization?', 'miacodeweb-gdpr' ); ?></h4>
                    <p><?php esc_html_e( 'Speed affects your SEO. At MiaCodeWEB we specialize in Linux VPS server administration and advanced WordPress optimization.', 'miacodeweb-gdpr' ); ?></p>
                    <a href="https://miacodeweb.com" target="_blank" class="button button-primary"><?php esc_html_e( 'View Maintenance Plans', 'miacodeweb-gdpr' ); ?></a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        <?php
    }

    public function inject_consented_scripts() {
        if ( isset( $_COOKIE[ $this->cookie_name ] ) && $_COOKIE[ $this->cookie_name ] === 'accepted' ) {
            $scripts = get_option( 'mcw_gdpr_scripts_tracking', '' );
            if ( ! empty( $scripts ) ) {
                echo "\n\n" . wp_unslash( $scripts ) . "\n";
            }
        }
    }

    public function show_cookie_banner() {
        if ( isset( $_COOKIE[ $this->cookie_name ] ) ) {
            return;
        }

        $color = get_option( 'mcw_gdpr_color_fondo', '#1a237e' );
        $text  = get_option( 'mcw_gdpr_texto_aviso', __( 'We use cookies to improve your experience.', 'miacodeweb-gdpr' ) );
        $btn_text = __( 'Accept All', 'miacodeweb-gdpr' );
        ?>
        <style>
            #mcw-cookie-banner { position:fixed; bottom:0; left:0; width:100%; background:<?php echo esc_attr( $color ); ?>; color:#fff; padding:15px; text-align:center; z-index:99999; font-family: sans-serif; }
            #mcw-btn-aceptar { background:#fff; color:<?php echo esc_attr( $color ); ?>; border:none; padding:8px 15px; margin-left:10px; cursor:pointer; font-weight:bold; border-radius:3px; }
        </style>
        <div id="mcw-cookie-banner">
            <span><?php echo esc_html( $text ); ?></span>
            <button id="mcw-btn-aceptar"><?php echo esc_html( $btn_text ); ?></button>
        </div>
        <script>
            document.getElementById('mcw-btn-aceptar').addEventListener('click', function() {
                var d = new Date(); d.setTime(d.getTime() + (30*24*60*60*1000));
                document.cookie = "<?php echo esc_js( $this->cookie_name ); ?>=accepted; expires=" + d.toUTCString() + "; path=/";
                document.getElementById('mcw-cookie-banner').style.display = 'none';
                window.location.reload();
            });
        </script>
        <?php
    }
}
new MiaCodeWEB_GDPR_Plugin();