<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function codemedsss_prepare_temp_mu_plugin() {
    codemedsss_create_mu_plugins_directory();

    $content = "<?php\n"
        . "if ( ! empty( \$_GET['codemedsss_test'] ) ) {\n"
        . "    \$disable = isset( \$_GET['codemedsss_disable'] ) ? rawurldecode( \$_GET['codemedsss_disable'] ) : '';\n"
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

    file_put_contents( CODESS_TEMP_MU_PLUGIN, $content );
}

function codemedsss_create_mu_plugins_directory() {
    $mu_dir = WPMU_PLUGIN_DIR;
    if ( ! file_exists( $mu_dir ) ) {
        wp_mkdir_p( $mu_dir );
    }
}
