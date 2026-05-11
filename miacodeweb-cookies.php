<?php
/**
 * Plugin Name: MiaCodeWEB GDPR Cookie Blocker
 * Plugin URI: https://miacodeweb.com
 * Description: Bloqueador de scripts de terceros con opciones de consentimiento granular.
 * Version: 1.1.0
 * Author: MiaCodeWEB
 * Author URI: https://miacodeweb.com
 * License: GPLv2 or later
 * Text Domain: miacodeweb-gdpr
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MiaCodeWEB_GDPR_Plugin {

    private $cookie_name = 'mcw_gdpr_consent';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'crear_menu_admin' ) );
        add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );
        add_action( 'wp_footer', array( $this, 'mostrar_banner_cookies' ) );
        add_action( 'wp_head', array( $this, 'inyectar_scripts_consentidos' ) );
    }

    /**
     * 1. MENÚ PRINCIPAL EN LA BARRA LATERAL
     */
    public function crear_menu_admin() {
        // add_menu_page lo coloca en el menú principal izquierdo
        add_menu_page(
            'Ajustes GDPR MiaCodeWEB', // Título de la página
            'GDPR Cookies',            // Título en el menú lateral
            'manage_options',          // Permisos
            'miacodeweb-gdpr',         // Slug
            array( $this, 'html_pagina_ajustes' ), // Función
            'dashicons-shield',        // Icono (Escudo)
            80                         // Posición (abajo en el menú)
        );
    }

    public function registrar_ajustes() {
        register_setting( 'mcw_gdpr_opciones', 'mcw_gdpr_color_fondo' );
        register_setting( 'mcw_gdpr_opciones', 'mcw_gdpr_texto_aviso' );
        register_setting( 'mcw_gdpr_opciones', 'mcw_gdpr_scripts_tracking' );
    }

    /**
     * 2. PANEL DE ADMINISTRACIÓN CON PESTAÑAS (TABS)
     */
    public function html_pagina_ajustes() {
        // Detectar qué pestaña está activa en la URL
        $tab_activa = isset( $_GET['tab'] ) ? $_GET['tab'] : 'apariencia';
        ?>
        <div class="wrap">
            <h1>🛡️ Configuración GDPR - MiaCodeWEB</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=miacodeweb-gdpr&tab=apariencia" class="nav-tab <?php echo $tab_activa == 'apariencia' ? 'nav-tab-active' : ''; ?>">Apariencia</a>
                <a href="?page=miacodeweb-gdpr&tab=scripts" class="nav-tab <?php echo $tab_activa == 'scripts' ? 'nav-tab-active' : ''; ?>">Scripts (Bloqueo)</a>
                <a href="?page=miacodeweb-gdpr&tab=sistema" class="nav-tab <?php echo $tab_activa == 'sistema' ? 'nav-tab-active' : ''; ?>">Estado del Sistema ⭐</a>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'mcw_gdpr_opciones' ); ?>

                <?php if ( $tab_activa == 'apariencia' ) : ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Texto del Aviso</th>
                        <td><textarea name="mcw_gdpr_texto_aviso" rows="3" cols="50"><?php echo esc_attr( get_option('mcw_gdpr_texto_aviso', 'Usamos cookies para mejorar tu experiencia.') ); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Color de Fondo (Hex)</th>
                        <td><input type="color" name="mcw_gdpr_color_fondo" value="<?php echo esc_attr( get_option('mcw_gdpr_color_fondo', '#1a237e') ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                
                <?php elseif ( $tab_activa == 'scripts' ) : ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Scripts de Rastreo<br><small>(Se bloquean hasta el OK del usuario)</small></th>
                        <td>
                            <textarea name="mcw_gdpr_scripts_tracking" rows="10" cols="60"><?php echo esc_attr( get_option('mcw_gdpr_scripts_tracking') ); ?></textarea>
                            <p class="description">Pega aquí etiquetas de Google Analytics, Meta Pixel, etc.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>

                <?php elseif ( $tab_activa == 'sistema' ) : ?>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #00a0d2; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h3>Diagnóstico de tu Servidor</h3>
                    <p><strong>Versión PHP:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>Software Web:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                    <hr>
                    <h4>🚀 ¿Tu web carga lento o necesita optimización profesional?</h4>
                    <p>La velocidad afecta tu SEO. En <strong>MiaCodeWEB</strong> somos especialistas en administración de servidores Linux VPS y optimización avanzada de WordPress.</p>
                    <a href="https://miacodeweb.com" target="_blank" class="button button-primary">Consultar Planes de Mantenimiento</a>
                </div>
                <?php endif; ?>

            </form>
        </div>
        <?php
    }

    public function inyectar_scripts_consentidos() {
        if ( isset( $_COOKIE[ $this->cookie_name ] ) && $_COOKIE[ $this->cookie_name ] === 'accepted' ) {
            $scripts = get_option( 'mcw_gdpr_scripts_tracking', '' );
            if ( ! empty( $scripts ) ) {
                echo "\n\n" . unslash( $scripts ) . "\n";
            }
        }
    }

    public function mostrar_banner_cookies() {
        if ( isset( $_COOKIE[ $this->cookie_name ] ) ) return;

        $color = get_option( 'mcw_gdpr_color_fondo', '#1a237e' );
        $texto = get_option( 'mcw_gdpr_texto_aviso', 'Usamos cookies para mejorar tu experiencia.' );
        ?>
        <style>
            #mcw-cookie-banner { position:fixed; bottom:0; width:100%; background:<?php echo $color; ?>; color:#fff; padding:15px; text-align:center; z-index:99999; }
            #mcw-btn-aceptar { background:#fff; color:<?php echo $color; ?>; border:none; padding:8px 15px; margin-left:10px; cursor:pointer; font-weight:bold; border-radius:3px; }
        </style>
        <div id="mcw-cookie-banner">
            <span><?php echo esc_html( $texto ); ?></span>
            <button id="mcw-btn-aceptar">Aceptar Todo</button>
        </div>
        <script>
            document.getElementById('mcw-btn-aceptar').addEventListener('click', function() {
                var d = new Date(); d.setTime(d.getTime() + (30*24*60*60*1000));
                document.cookie = "<?php echo $this->cookie_name; ?>=accepted; expires=" + d.toUTCString() + "; path=/";
                document.getElementById('mcw-cookie-banner').style.display = 'none';
                window.location.reload();
            });
        </script>
        <?php
    }
}
new MiaCodeWEB_GDPR_Plugin();