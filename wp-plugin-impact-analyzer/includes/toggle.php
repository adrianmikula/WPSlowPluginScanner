<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_prepare_temp_mu_plugin() {
    pia_create_mu_plugins_directory();

    $content = "<?php\n"
        . "if ( ! empty( \$_GET['pia_test'] ) ) {\n"
        . "    \$disable = isset( \$_GET['pia_disable'] ) ? rawurldecode( \$_GET['pia_disable'] ) : '';\n"
        . "    \$disable = preg_replace( '/[^A-Za-z0-9_\\-\\/.]/', '', \$disable );\n"
        . "    if ( ! empty( \$disable ) ) {\n"
        . "        add_filter( 'pre_option_active_plugins', function( \$value ) use ( \$disable ) {\n"
        . "            if ( is_array( \$value ) ) {\n"
        . "                return array_values( array_diff( \$value, array( \$disable ) ) );\n"
        . "            }\n"
        . "            return \$value;\n"
        . "        } );\n"
        . "        add_filter( 'pre_site_option_active_sitewide_plugins', function( \$value ) use ( \$disable ) {\n"
        . "            if ( is_array( \$value ) ) {\n"
        . "                unset( \$value[ \$disable ] );\n"
        . "                return \$value;\n"
        . "            }\n"
        . "            return \$value;\n"
        . "        } );\n"
        . "    }\n"
        . "}\n";

    file_put_contents( PIA_TEMP_MU_PLUGIN, $content );
}

function pia_create_mu_plugins_directory() {
    $mu_dir = WP_CONTENT_DIR . '/mu-plugins';
    if ( ! file_exists( $mu_dir ) ) {
        wp_mkdir_p( $mu_dir );
    }
}
