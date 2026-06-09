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
<?php
/**
 * Plugin Name: MiaCodeWEB GDPR/LGPD Cookie Blocker
 * Plugin URI: https://miacodeweb.com
 * Description: Plugin liviano para WordPress que muestra banners de cookies según región y bloquea scripts de tracking hasta obtener consentimiento cuando corresponde.
 * Version: 1.2.1
 * Author: MiaCodeWEB
 * Author URI: https://miacodeweb.com
 * License: GPLv2 or later
 * Text Domain: miacodeweb-gdpr
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'MiaCodeWEB_GDPR_Plugin' ) ) {

class MiaCodeWEB_GDPR_Plugin {

    private $cookie_name  = 'mcw_gdpr_consent';
    private $region_cookie = 'mcw_gdpr_region';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_footer', array( $this, 'show_cookie_banner' ) );
        add_action( 'wp_head', array( $this, 'inject_consented_scripts' ), 20 );
        add_action( 'send_headers', array( $this, 'send_cache_headers' ) );
        add_action( 'admin_post_mcw_gdpr_save_settings', array( $this, 'save_settings' ) );
    }

    public function create_admin_menu() {
        add_menu_page(
            __( 'MiaCodeWEB Cookies', 'miacodeweb-gdpr' ),
            __( 'GDPR Cookies', 'miacodeweb-gdpr' ),
            'manage_options',
            'miacodeweb-gdpr',
            array( $this, 'settings_page_html' ),
            'dashicons-shield',
            80
        );
    }

    public function register_settings() {
        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_color_fondo', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_color_with_default' ),
            'default'           => '#1a237e',
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_privacy_url', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_text_general', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'Usamos cookies para mejorar tu experiencia. Puedes aceptar o rechazar las cookies no esenciales.', 'miacodeweb-gdpr' ),
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_text_eu', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'Usamos cookies necesarias y, con tu consentimiento, cookies de analítica y marketing. Puedes aceptar o rechazar las cookies no esenciales.', 'miacodeweb-gdpr' ),
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_text_brazil', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'Utilizamos cookies necessárias e, com seu consentimento, cookies de análise e marketing. Você pode aceitar ou rejeitar cookies não essenciais.', 'miacodeweb-gdpr' ),
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_text_usa', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'We use cookies to improve your experience. You can accept or reject non-essential cookies.', 'miacodeweb-gdpr' ),
        ) );

        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_text_california', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'We use cookies and similar technologies. California residents may opt out of non-essential cookies and certain sharing for advertising.', 'miacodeweb-gdpr' ),
        ) );

        // Permite guardar etiquetas <script>. Solo usuarios con manage_options pueden acceder a esta pantalla.
        register_setting( 'mcw_gdpr_options', 'mcw_gdpr_scripts_tracking', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_tracking_scripts' ),
            'default'           => '',
        ) );
    }

    public function sanitize_color_with_default( $color ) {
        $color = sanitize_hex_color( $color );
        return $color ? $color : '#1a237e';
    }

    public function sanitize_tracking_scripts( $scripts ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return '';
        }

        // No se usa sanitize_text_field porque rompe JS. Se eliminan tags PHP por seguridad básica.
        $scripts = (string) $scripts;
        $scripts = preg_replace( '/<\?(php)?|\?>/i', '', $scripts );
        return $scripts;
    }


    public function send_cache_headers() {
        if ( is_admin() ) {
            return;
        }

        // Evita que cachés intermedias sirvan una versión con banner a usuarios que ya aceptaron/rechazaron.
        header( 'Vary: Cookie', false );
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para guardar esta configuración.', 'miacodeweb-gdpr' ) );
        }

        check_admin_referer( 'mcw_gdpr_save_settings', 'mcw_gdpr_nonce' );

        $active_tab = isset( $_POST['mcw_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['mcw_active_tab'] ) ) : 'appearance';
        $allowed_tabs = array( 'appearance', 'regions', 'scripts', 'system' );

        if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
            $active_tab = 'appearance';
        }

        if ( $active_tab === 'appearance' ) {
            if ( isset( $_POST['mcw_gdpr_color_fondo'] ) ) {
                update_option( 'mcw_gdpr_color_fondo', $this->sanitize_color_with_default( wp_unslash( $_POST['mcw_gdpr_color_fondo'] ) ) );
            }

            if ( isset( $_POST['mcw_gdpr_privacy_url'] ) ) {
                update_option( 'mcw_gdpr_privacy_url', esc_url_raw( wp_unslash( $_POST['mcw_gdpr_privacy_url'] ) ) );
            }
        }

        if ( $active_tab === 'regions' ) {
            $text_options = array(
                'mcw_gdpr_text_general',
                'mcw_gdpr_text_eu',
                'mcw_gdpr_text_brazil',
                'mcw_gdpr_text_usa',
                'mcw_gdpr_text_california',
            );

            foreach ( $text_options as $option_name ) {
                if ( isset( $_POST[ $option_name ] ) ) {
                    update_option( $option_name, sanitize_textarea_field( wp_unslash( $_POST[ $option_name ] ) ) );
                }
            }
        }

        if ( $active_tab === 'scripts' ) {
            if ( isset( $_POST['mcw_gdpr_scripts_tracking'] ) ) {
                update_option( 'mcw_gdpr_scripts_tracking', $this->sanitize_tracking_scripts( wp_unslash( $_POST['mcw_gdpr_scripts_tracking'] ) ) );
            }
        }

        $redirect = add_query_arg(
            array(
                'page'             => 'miacodeweb-gdpr',
                'tab'              => $active_tab,
                'settings-updated' => 'true',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'appearance';
        $allowed_tabs = array( 'appearance', 'regions', 'scripts', 'system' );
        if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
            $active_tab = 'appearance';
        }
        ?>
        <div class="wrap">
            <h1>MiaCodeWEB Cookies Configuration</h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=miacodeweb-gdpr&tab=appearance' ) ); ?>" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Apariencia', 'miacodeweb-gdpr' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=miacodeweb-gdpr&tab=regions' ) ); ?>" class="nav-tab <?php echo $active_tab === 'regions' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Textos por región', 'miacodeweb-gdpr' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=miacodeweb-gdpr&tab=scripts' ) ); ?>" class="nav-tab <?php echo $active_tab === 'scripts' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Scripts bloqueados', 'miacodeweb-gdpr' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=miacodeweb-gdpr&tab=system' ) ); ?>" class="nav-tab <?php echo $active_tab === 'system' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Sistema', 'miacodeweb-gdpr' ); ?></a>
            </h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="mcw_gdpr_save_settings" />
                <input type="hidden" name="mcw_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
                <?php wp_nonce_field( 'mcw_gdpr_save_settings', 'mcw_gdpr_nonce' ); ?>
                <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Configuración guardada correctamente.', 'miacodeweb-gdpr' ); ?></p></div>
                <?php endif; ?>

                <?php if ( $active_tab === 'appearance' ) : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="mcw_gdpr_color_fondo"><?php esc_html_e( 'Color de fondo', 'miacodeweb-gdpr' ); ?></label></th>
                            <td><input type="color" id="mcw_gdpr_color_fondo" name="mcw_gdpr_color_fondo" value="<?php echo esc_attr( get_option( 'mcw_gdpr_color_fondo', '#1a237e' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcw_gdpr_privacy_url"><?php esc_html_e( 'URL de política de privacidad/cookies', 'miacodeweb-gdpr' ); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="mcw_gdpr_privacy_url" name="mcw_gdpr_privacy_url" value="<?php echo esc_attr( get_option( 'mcw_gdpr_privacy_url', '' ) ); ?>" placeholder="https://tusitio.com/politica-de-privacidad/" />
                                <p class="description"><?php esc_html_e( 'Opcional. Se mostrará como enlace dentro del banner.', 'miacodeweb-gdpr' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>

                <?php elseif ( $active_tab === 'regions' ) : ?>
                    <p><?php esc_html_e( 'La región se detecta por cabeceras del servidor/CDN: Cloudflare CF-IPCountry, GeoIP de hosting, o Accept-Language como último recurso. Si no hay datos fiables, se usa General.', 'miacodeweb-gdpr' ); ?></p>
                    <table class="form-table" role="presentation">
                        <?php $this->region_textarea_row( 'mcw_gdpr_text_general', __( 'General / Argentina / Resto del mundo', 'miacodeweb-gdpr' ) ); ?>
                        <?php $this->region_textarea_row( 'mcw_gdpr_text_eu', __( 'Unión Europea / Reino Unido / EEE', 'miacodeweb-gdpr' ) ); ?>
                        <?php $this->region_textarea_row( 'mcw_gdpr_text_brazil', __( 'Brasil - LGPD', 'miacodeweb-gdpr' ) ); ?>
                        <?php $this->region_textarea_row( 'mcw_gdpr_text_usa', __( 'Estados Unidos', 'miacodeweb-gdpr' ) ); ?>
                        <?php $this->region_textarea_row( 'mcw_gdpr_text_california', __( 'California - CCPA/CPRA', 'miacodeweb-gdpr' ) ); ?>
                    </table>
                    <?php submit_button(); ?>

                <?php elseif ( $active_tab === 'scripts' ) : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="mcw_gdpr_scripts_tracking"><?php esc_html_e( 'Scripts de tracking', 'miacodeweb-gdpr' ); ?></label><br><small><?php esc_html_e( 'Bloqueados hasta aceptar', 'miacodeweb-gdpr' ); ?></small></th>
                            <td>
                                <textarea id="mcw_gdpr_scripts_tracking" name="mcw_gdpr_scripts_tracking" rows="12" cols="80" class="large-text code"><?php echo esc_textarea( get_option( 'mcw_gdpr_scripts_tracking', '' ) ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Pega aquí Google Analytics, Meta Pixel, Google Tag Manager u otros scripts no esenciales.', 'miacodeweb-gdpr' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>

                <?php elseif ( $active_tab === 'system' ) : ?>
                    <div style="background:#fff;padding:20px;border-left:4px solid #00a0d2;margin-top:20px;box-shadow:0 1px 1px rgba(0,0,0,.04);">
                        <h3><?php esc_html_e( 'Diagnóstico del servidor', 'miacodeweb-gdpr' ); ?></h3>
                        <p><strong><?php esc_html_e( 'PHP:', 'miacodeweb-gdpr' ); ?></strong> <?php echo esc_html( phpversion() ); ?></p>
                        <p><strong><?php esc_html_e( 'Servidor:', 'miacodeweb-gdpr' ); ?></strong> <?php echo isset( $_SERVER['SERVER_SOFTWARE'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : esc_html__( 'Desconocido', 'miacodeweb-gdpr' ); ?></p>
                        <p><strong><?php esc_html_e( 'Región detectada para esta visita:', 'miacodeweb-gdpr' ); ?></strong> <?php echo esc_html( $this->get_user_region() ); ?></p>
                        <hr>
                        <h4><?php esc_html_e( 'MiaCodeWEB', 'miacodeweb-gdpr' ); ?></h4>
                        <p><?php esc_html_e( 'Administración de servidores Linux VPS, seguridad, optimización WordPress y mantenimiento web.', 'miacodeweb-gdpr' ); ?></p>
                        <a href="https://miacodeweb.com" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Ver servicios', 'miacodeweb-gdpr' ); ?></a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    private function region_textarea_row( $option_name, $label ) {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><textarea id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" rows="4" cols="80" class="large-text"><?php echo esc_textarea( get_option( $option_name, '' ) ); ?></textarea></td>
        </tr>
        <?php
    }

    public function inject_consented_scripts() {
        $consent = isset( $_COOKIE[ $this->cookie_name ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $this->cookie_name ] ) ) : '';

        if ( $consent !== 'accepted' ) {
            return;
        }

        $scripts = get_option( 'mcw_gdpr_scripts_tracking', '' );
        if ( ! empty( $scripts ) ) {
            echo "\n<!-- MiaCodeWEB consented tracking scripts -->\n";
            echo wp_unslash( $scripts );
            echo "\n<!-- /MiaCodeWEB consented tracking scripts -->\n";
        }
    }

    public function show_cookie_banner() {
        if ( isset( $_COOKIE[ $this->cookie_name ] ) ) {
            return;
        }

        $region      = $this->get_user_region();
        $banner_data = $this->get_banner_data_for_region( $region );
        $color       = get_option( 'mcw_gdpr_color_fondo', '#1a237e' );
        $privacy_url = get_option( 'mcw_gdpr_privacy_url', '' );
        ?>
        <style>
            #mcw-cookie-banner{position:fixed;bottom:0;left:0;width:100%;box-sizing:border-box;background:<?php echo esc_attr( $color ); ?>;color:#fff;padding:16px 18px;text-align:center;z-index:99999;font-family:Arial,sans-serif;box-shadow:0 -2px 10px rgba(0,0,0,.25)}
            #mcw-cookie-banner p{margin:0 0 10px;font-size:14px;line-height:1.45}
            #mcw-cookie-banner a{color:#fff;text-decoration:underline}
            .mcw-cookie-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
            .mcw-cookie-btn{border:0;padding:9px 16px;cursor:pointer;font-weight:700;border-radius:4px}
            #mcw-btn-accept{background:#fff;color:<?php echo esc_attr( $color ); ?>}
            #mcw-btn-reject{background:transparent;color:#fff;border:1px solid #fff}
        </style>
        <div id="mcw-cookie-banner" role="dialog" aria-live="polite" aria-label="<?php echo esc_attr__( 'Cookie consent banner', 'miacodeweb-gdpr' ); ?>" data-region="<?php echo esc_attr( $region ); ?>">
            <p>
                <?php echo esc_html( $banner_data['text'] ); ?>
                <?php if ( ! empty( $privacy_url ) ) : ?>
                    <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $banner_data['policy_label'] ); ?></a>
                <?php endif; ?>
            </p>
            <div class="mcw-cookie-actions">
                <button type="button" class="mcw-cookie-btn" id="mcw-btn-reject"><?php echo esc_html( $banner_data['reject'] ); ?></button>
                <button type="button" class="mcw-cookie-btn" id="mcw-btn-accept"><?php echo esc_html( $banner_data['accept'] ); ?></button>
            </div>
        </div>
        <script>
        (function(){
            var cookieName = <?php echo wp_json_encode( $this->cookie_name ); ?>;
            var regionCookie = <?php echo wp_json_encode( $this->region_cookie ); ?>;
            var region = <?php echo wp_json_encode( $region ); ?>;
            var days = 180;

            function getCookie(name) {
                var parts = document.cookie ? document.cookie.split('; ') : [];
                for (var i = 0; i < parts.length; i++) {
                    var pair = parts[i].split('=');
                    if (decodeURIComponent(pair[0]) === name) {
                        return decodeURIComponent(pair.slice(1).join('='));
                    }
                }
                return '';
            }

            function hideBanner() {
                var banner = document.getElementById('mcw-cookie-banner');
                if (banner) {
                    banner.style.display = 'none';
                    banner.setAttribute('aria-hidden', 'true');
                }
            }

            function setCookie(name, value, daysToExpire) {
                var maxAge = daysToExpire * 24 * 60 * 60;
                var d = new Date();
                d.setTime(d.getTime() + (maxAge * 1000));

                var cookie = name + '=' + encodeURIComponent(value)
                    + '; Max-Age=' + maxAge
                    + '; expires=' + d.toUTCString()
                    + '; path=/'
                    + '; SameSite=Lax';

                if (location.protocol === 'https:') {
                    cookie += '; Secure';
                }

                document.cookie = cookie;
            }

            function updateGoogleConsent(value) {
                if (typeof window.gtag !== 'function') {
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = function(){ window.dataLayer.push(arguments); };
                }

                if (value === 'accepted') {
                    window.gtag('consent', 'update', {
                        'analytics_storage': 'granted',
                        'ad_storage': 'granted',
                        'ad_user_data': 'granted',
                        'ad_personalization': 'granted',
                        'functionality_storage': 'granted',
                        'security_storage': 'granted',
                        'personalization_storage': 'granted'
                    });

                    window.gtag('event', 'mcw_cookie_consent_accepted');
                } else {
                    window.gtag('consent', 'update', {
                        'analytics_storage': 'denied',
                        'ad_storage': 'denied',
                        'ad_user_data': 'denied',
                        'ad_personalization': 'denied',
                        'functionality_storage': 'denied',
                        'security_storage': 'granted',
                        'personalization_storage': 'denied'
                    });
                }
            }

            function saveConsent(value) {
                setCookie(cookieName, value, days);
                setCookie(regionCookie, region, days);
                updateGoogleConsent(value);
                hideBanner();

                // Verificación inmediata: si el navegador no permite guardar la cookie, no recargamos en bucle.
                if (getCookie(cookieName) !== value && window.console) {
                    console.warn('MiaCodeWEB Cookies: el navegador no permitió guardar la cookie de consentimiento.');
                }
            }

            // Protección contra caché: si LiteSpeed/Cloudflare sirvió una página vieja con banner,
            // lo ocultamos igualmente si la cookie ya existe en el navegador.
            if (getCookie(cookieName)) {
                hideBanner();
                return;
            }

            var accept = document.getElementById('mcw-btn-accept');
            var reject = document.getElementById('mcw-btn-reject');

            if (accept) {
                accept.addEventListener('click', function(event){
                    event.preventDefault();
                    saveConsent('accepted');
                });
            }

            if (reject) {
                reject.addEventListener('click', function(event){
                    event.preventDefault();
                    saveConsent('rejected');
                });
            }
        })();
        </script>
        <?php
    }

    private function get_banner_data_for_region( $region ) {
        $data = array(
            'text'         => get_option( 'mcw_gdpr_text_general', __( 'Usamos cookies para mejorar tu experiencia. Puedes aceptar o rechazar las cookies no esenciales.', 'miacodeweb-gdpr' ) ),
            'accept'       => __( 'Aceptar', 'miacodeweb-gdpr' ),
            'reject'       => __( 'Rechazar', 'miacodeweb-gdpr' ),
            'policy_label' => __( 'Ver política de privacidad', 'miacodeweb-gdpr' ),
        );

        if ( $region === 'eu' ) {
            $data['text']         = get_option( 'mcw_gdpr_text_eu', $data['text'] );
            $data['accept']       = __( 'Aceptar cookies no esenciales', 'miacodeweb-gdpr' );
            $data['reject']       = __( 'Rechazar no esenciales', 'miacodeweb-gdpr' );
            $data['policy_label'] = __( 'Ver política de cookies', 'miacodeweb-gdpr' );
        } elseif ( $region === 'brazil' ) {
            $data['text']         = get_option( 'mcw_gdpr_text_brazil', $data['text'] );
            $data['accept']       = __( 'Aceitar', 'miacodeweb-gdpr' );
            $data['reject']       = __( 'Rejeitar', 'miacodeweb-gdpr' );
            $data['policy_label'] = __( 'Ver política de privacidade', 'miacodeweb-gdpr' );
        } elseif ( $region === 'california' ) {
            $data['text']         = get_option( 'mcw_gdpr_text_california', $data['text'] );
            $data['accept']       = __( 'Accept', 'miacodeweb-gdpr' );
            $data['reject']       = __( 'Do Not Sell or Share / Reject', 'miacodeweb-gdpr' );
            $data['policy_label'] = __( 'Privacy policy', 'miacodeweb-gdpr' );
        } elseif ( $region === 'usa' ) {
            $data['text']         = get_option( 'mcw_gdpr_text_usa', $data['text'] );
            $data['accept']       = __( 'Accept', 'miacodeweb-gdpr' );
            $data['reject']       = __( 'Reject', 'miacodeweb-gdpr' );
            $data['policy_label'] = __( 'Privacy policy', 'miacodeweb-gdpr' );
        }

        return $data;
    }

    private function get_user_region() {
        $country = $this->get_country_code();
        $state   = $this->get_region_code();

        // California se detecta solo si el hosting/CDN entrega región. Cloudflare normalmente entrega país, no estado, salvo reglas avanzadas.
        if ( $country === 'US' && $state === 'CA' ) {
            return 'california';
        }

        if ( $country === 'BR' ) {
            return 'brazil';
        }

        if ( $country === 'US' ) {
            return 'usa';
        }

        $eu_countries = array(
            'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE',
            'IS','LI','NO','GB','UK','CH'
        );

        if ( in_array( $country, $eu_countries, true ) ) {
            return 'eu';
        }

        return 'general';
    }

    private function get_country_code() {
        $candidates = array(
            'HTTP_CF_IPCOUNTRY',          // Cloudflare.
            'GEOIP_COUNTRY_CODE',         // Extensión/hosting GeoIP.
            'HTTP_X_COUNTRY_CODE',        // Algunos proxies/CDN.
            'HTTP_X_GEOIP_COUNTRY_CODE',  // Algunos proxies/CDN.
        );

        foreach ( $candidates as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
                if ( preg_match( '/^[A-Z]{2}$/', $country ) ) {
                    return $country;
                }
            }
        }

        // Último recurso: idioma del navegador. No es geolocalización real, pero ayuda si el servidor no entrega país.
        $accept_language = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) ) : '';

        if ( strpos( $accept_language, 'pt-br' ) !== false ) {
            return 'BR';
        }

        if ( preg_match( '/\b(en-us|es-us)\b/', $accept_language ) ) {
            return 'US';
        }

        if ( preg_match( '/\b(es-es|fr-fr|de-de|it-it|pt-pt|nl-nl|pl-pl|sv-se|da-dk|fi-fi)\b/', $accept_language ) ) {
            return 'ES';
        }

        return 'AR';
    }

    private function get_region_code() {
        $candidates = array(
            'HTTP_CF_REGION_CODE',
            'HTTP_X_REGION_CODE',
            'HTTP_X_GEOIP_REGION',
            'GEOIP_REGION',
        );

        foreach ( $candidates as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $region = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
                if ( preg_match( '/^[A-Z0-9_-]{1,10}$/', $region ) ) {
                    return $region;
                }
            }
        }

        return '';
    }
}

new MiaCodeWEB_GDPR_Plugin();

}

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
