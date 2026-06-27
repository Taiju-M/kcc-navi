<?php
/**
 * Plugin Name:       KCC Core
 * Description:        クリプトカード診断ガイドの中核。service CPT・タクソノミー・ACFフィールド・比較表ブロックを提供。
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Text Domain:       kcc-core
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KCC_CORE_VERSION', '0.1.0' );
define( 'KCC_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'KCC_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once KCC_CORE_PATH . 'inc/cpt.php';
require_once KCC_CORE_PATH . 'inc/acf-fields.php';
require_once KCC_CORE_PATH . 'inc/blocks.php';
require_once KCC_CORE_PATH . 'inc/bridge.php';

register_activation_hook( __FILE__, static function (): void {
	kcc_register_service_cpt();
	kcc_register_service_taxonomies();
	kcc_register_bridge_rewrite();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, static function (): void {
	flush_rewrite_rules();
} );
