<?php
/**
 * Plugin Name: WP Toolkit
 * Plugin URI: https://wptoolkit.com/
 * Description: Premium Theme, Plugin & WooCommerce Extension Manager
 * Version: 1.2.8
 * Author: WP Toolkit
 * Author URI:  https://wptoolkit.com/ 
 * Copyright: WP Toolkit is based on GPLKit (https://gplkit.com). WP Toolkit is copyright 2016. 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * GitHub Plugin URI: garyp75/wptoolkit
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//** Make sure this plugin runs first
function wpt_this_plugin_first() {
	$this_plugin = plugin_basename( __FILE__ );
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_search($this_plugin, $active_plugins);
	if ($this_plugin_key) { // if it's 0 it's the first plugin already, no need to continue
		array_splice($active_plugins, $this_plugin_key, 1);
		array_unshift($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);
	}
	
	delete_option("wptoolkit_plugins");
	delete_option("wptoolkit_themes");
}
add_action("activated_plugin", "wpt_this_plugin_first");
add_action('upgrader_process_complete', 'wpt_this_plugin_first');

add_action('core_upgrade_preamble', array("WPToolKit_Updates","get_plugin_catalogue"));
add_action('core_upgrade_preamble', array("WPToolKit_Updates","get_theme_catalogue"));

$wptoolkit_plugin_manager_nag_data = get_option( "wptoolkit_plugin_manager_nag_data" );

//** Turns off WPMUDEV Dashboard Nags */
if ( ! class_exists('WPMUDEV_Dashboard_Notice3') && ! class_exists('WPMUDEV_Dashboard_Notice') ) {
	$wpmu_nag = $wptoolkit_plugin_manager_nag_data["wpt_nag_override_wpmudev"];

	if($wpmu_nag !== false && $wpmu_nag == "on"){
		class WPMUDEV_Dashboard_Notice3 {}
		class WPMUDEV_Dashboard_Notice {}
	}
}

//** Turn Off Elegant Themes updates class.
if ( ! class_exists( 'ET_Core_Updates' ) ) {
	$et_nag = $wptoolkit_plugin_manager_nag_data["wpt_nag_override_elegantthemes"];
	if($et_nag !== false && $et_nag == "on"){
		class ET_Core_Updates {}
	}
}

//** Turn Off Woo Updater Nags
if ( ! function_exists( 'woothemes_updater_notice' ) ) {
	$woothemes_nag = $wptoolkit_plugin_manager_nag_data["wpt_nag_override_woothemes"];
	if($woothemes_nag !== false && $woothemes_nag == "on"){
		function woothemes_updater_notice() {}
	}
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

function WPT_remote_download($url, $save_path = false){
	// Use wp_remote_get to fetch the data
	$response = wp_remote_get($url, array("timeout" => PHP_INT_MAX));

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

//** Force Wordpress from downloading WPToolkit plugins from our repo
function WPT_updater( $api, $action, $args ) {
	if( $action == 'plugin_information' && empty( $api ) && isset($_GET["type"]) && $_GET["type"] == "WPT" ){
		
		$wptoolkit_plugins = get_option('wptoolkit_plugins');
		$the_plugin = $wptoolkit_plugins[$_GET["plugin"]];
		
		$wptoolkit_licence_manager = get_option('wptoolkit_plugin_manager');
		$email = $wptoolkit_licence_manager['activation_email'];
		$licence_key = $wptoolkit_licence_manager['api_key'];
		
		$slug = $the_plugin["plugin_id"];
		
		$res                = new stdClass();
		$res->name          = $the_plugin['name'];
		$res->version       = $the_plugin['version'];
		$res->download_link = 'http://api.wptoolkit.com/?wpt_plugin_download=get&plugin_id='.$slug.'&email='.$email.'&licence_key='.$licence_key."&request=install&site_url=".home_url();
		$res->tested = '10.0';
		return $res;
	}
	return $api;
}
add_filter( 'plugins_api', "WPT_updater", 100, 3);

//** Force Wordpress from downloading WPToolkit plugins from our repo
function WPT_theme_updater( $api, $action, $args ) {
	if( $action == 'theme_information' && empty( $api ) && isset($_GET["type"]) && $_GET["type"] == "WPT" ){
		
		$wptoolkit_plugins = get_option('wptoolkit_themes');
		$the_theme = $wptoolkit_plugins[$_GET["theme"]];

		$wptoolkit_licence_manager = get_option('wptoolkit_plugin_manager');
		$email = $wptoolkit_licence_manager['activation_email'];
		$licence_key = $wptoolkit_licence_manager['api_key'];
		
		$slug = $the_theme["theme_id"];
		
		$res                = new stdClass();
		$res->name          = $the_theme['name'];
		$res->version       = $the_theme['version'];
		$res->download_link = 'http://api.wptoolkit.com/?wpt_theme_download=get&theme_id='.$slug.'&email='.$email.'&licence_key='.$licence_key."&request=install&site_url=".home_url();
		$res->tested = '10.0';
		return $res;
	}
	return $api;
}
add_filter( 'themes_api', "WPT_theme_updater", 100, 3);

//** Force WPToolkit to update its lists of plugins and themes
function WPT_force_update_lists(){
	WPToolKit_Updates::get_plugin_catalogue();
	die();
}
add_action( 'wp_ajax_get_plugin_catalogue', "WPT_force_update_lists" );
add_action( 'wp_ajax_nopriv_get_plugin_catalogue', "WPT_force_update_lists");

//** GitHub Updater
include_once( 'updater.php' );

if ( is_admin() ) {
 
    $config = array(
        'slug'                  => plugin_basename( __FILE__ ),
        'proper_folder_name'    => 'wptoolkit',
        'api_url'               => 'https://api.github.com/repos/garyp75/wptoolkit',
        'raw_url'               => 'https://raw.github.com/garyp75/wptoolkit/master',
        'github_url'            => 'https://github.com/garyp75/wptoolkit',
        'zip_url'               => 'https://github.com/garyp75/wptoolkit/zipball/master',
        'sslverify'             => true,
        'requires'              => '3.0',
        'tested'                => '4.5',
        'readme'                => 'README.md',
        'access_token'          => ''
    );
 
    new WP_GitHub_Updater( $config );
 
}
 
?>
