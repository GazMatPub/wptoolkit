<?php
/**
 * Plugin Name: WP Toolkit
 * Plugin URI: https://wptoolkit.com/
 * Description: Premium Theme, Plugin & WooCommerce Extension Manager
 * Version: 1.0.0
 * Author: WP Toolkit
 * Author URI:  https://wptoolkit.com/ 
 * Copyright: WP Toolkit is based on GPLKit (https://gplkit.com). WP Toolkit is copyright 2016. 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WPToolKit' ) ) {

	/**
	 * Main WPToolKit Class
	 *
	 * @class WPToolKit
	 * @version	2.3.0
	 */
	final class WPToolKit {
		
		protected static $_instance = null;

		public $program = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		
		public function __construct() {
			
			$this->includes();
			$this->init_hooks();
			
			do_action( 'wpt_loaded' );
		}

		public function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 0 );
		}
		
		public function includes() {
			include_once( 'includes/class-wptapi-admin.php' );
			include_once( 'includes/class-wptapi-updates.php' );
			include_once( 'includes/class-wptapi-plugin.php' );
			include_once( 'includes/class-wptapi-license.php' );
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		public function init() {
			add_action( 'admin_enqueue_scripts', array($this,'wpt_enqueue_scripts') );
		}

		public function install() {
			wp_schedule_event(time(), 'twicedaily', 'wptoolkit_twicedaily_update');
			WPT()->activation();
		}
		public function uninstall() {
			wp_clear_scheduled_hook('wptoolkit_twicedaily_update');
			WPT()->uninstall();
		}

		public function wpt_enqueue_scripts($hook) {
			wp_enqueue_style( 'wptoolkit-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-styles.css' );
			wp_enqueue_script( 'wptoolkit-admin-js', plugins_url('assets/js/jquery.mixitup.min.js',__FILE__) );
		}

		public function get_wptoolkit_installed_plugins() {
			return array(
				
			);
		}
		
	}

}
register_activation_hook( __FILE__, array( 'WPToolKit', 'install' ) );
register_deactivation_hook(__FILE__, array( 'WPToolKit', 'uninstall' ) );

function GK() {
	return WPToolKit::instance();
}

// Global for backwards compatibility.
$GLOBALS['wptoolkit'] = GK();


add_action( 'wp_ajax_get_plugin_catalogue', "xxxx" );
add_action( 'wp_ajax_nopriv_get_plugin_catalogue', "xxxx");

function WPT_remote_download($url, $save_path = false){
	// Use wp_remote_get to fetch the data
	$response = wp_remote_get($url);

	// Save the body part to a variable
	$zip = $response['body'];

	
	// In the header info is the name of the XML or CVS file. I used preg_match to find it
	preg_match("/filename\s*=\s*(\\\"[^\\\"]*\\\"|'[^']*)/i", $response['headers']['content-disposition'], $match);

	if($save_path){
		// Create the name of the file and the declare the directory and path
		$file = trailingslashit($save_path).$match[1];

		// Now use the standard PHP file functions
		$fp = fopen($file, "w");
		fwrite($fp, $zip);
		fclose($fp);
		return true;
	}else{
		if($zip){
			return array("filenam" => $match[1], "body" => $zip);
		}else{
			return false;
		}
	}
}

function xxxx(){
	WPToolKit_Updates::get_plugin_catalogue();
	die();
}
add_action( 'wp_ajax_get_theme_catalogue', "yyy" );
add_action( 'wp_ajax_nopriv_get_theme_catalogue', "yyy");

function yyy(){
	WPT_remote_download( "http://api.wptoolkit.com/?wpt_plugin_download=get&plugin_id=wp-toolkit&email=magicarty@gmail.com&licence_key=827cff840d7b8f5c512a5e3efc5b17d1");
}