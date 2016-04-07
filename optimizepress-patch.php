<?php
/**
 * Plugin Name: OptimizePress Patch Plugin
 * Plugin URI:  www.optimizepress.com
 * Description: Plugin used to get the important patches for OptimizePress
 * Version:     1.0.0
 * Author:      OptimizePress <info@optimizepress.com>
 * Author URI:  optimizepress.com
 * Text Domain: optimizepress-patch
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-optimizepress-patch.php' );

Op_Patch::get_instance();