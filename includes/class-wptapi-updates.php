<?php
/**
 * Updates
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPToolKit_Updates Class
 */
class WPToolKit_Updates {

	public function __construct() {
		
		add_action( 'wp_loaded', array( $this, 'init' ) );
		
	}

	/**
	 * Hook in methods.
	 */
	public static function init() {

		if (is_admin()) {

			add_filter('site_transient_update_plugins', array(__CLASS__, 'override_update_locations') );

		}
		
	}

	public static function override_update_locations($value) {

		if ( get_option( 'wptoolkit_plugin_manager_activated' ) == 'Activated' ) {

			$all_plugins = get_plugins();

			if ($wptoolkit_plugins = get_option('wptoolkit_plugins')) {

				foreach($all_plugins as $key => $plugin) {

					if (array_key_exists($key, $wptoolkit_plugins)) {

						$wptoolkit_licence_manager = get_option('wptoolkit_plugin_manager');

						$email = $wptoolkit_licence_manager['activation_email'];
						$licence_key = $wptoolkit_licence_manager['api_key'];
						$product_id = 'WPToolKit%20Plugin%20Manager';
						$instance = get_option('wptoolkit_plugin_manager_instance');

						$plugin_url = 'https://api.wptoolkit.com/?wpt_plugin_download=get&plugin_id='.$wptoolkit_plugins[$key]['plugin_id'].'&email='.$email.'&licence_key='.$licence_key.'&product_id='.$product_id.'&instance='.$instance.'&request=wptoolkit_status';
						
				        $obj = new stdClass();
				        $obj->slug = $wptoolkit_plugins[$key]['plugin_id'];
						$obj->plugin = $key;
				        $obj->new_version = $wptoolkit_plugins[$key]['version'];
				        $obj->package = $plugin_url;
				        
				        // if new version is different to current version
						if ($all_plugins[$key]['Version'] != $obj->new_version) {
					        // add to transient
				    	    $value->response[$key] = $obj;
						}

					}
				
				}
			}
		}
		
		
		return $value;

	}

	public static function get_plugin_catalogue() { 
		$url = 'https://api.wptoolkit.com/?wptoolkit_repo=json&type=all';
		$request = wp_remote_post( $url, array('timeout' => 45) );  
		if( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
	    	
	    	$json = json_decode( $request['body'], true ); // attempt decode

	    	if( $json !== null ) {
		    	update_option('wptoolkit_plugins', $json);
				echo $request['body'];
	    	} // return json
	    	
	    }
	}
	
	public static function get_theme_catalogue() { 
		$url = 'https://api.wptoolkit.com/?wptoolkit_repo=json&type=themes';
		$request = wp_remote_post( $url, array('timeout' => 45) );  
		if( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
	    	
	    	$json = json_decode( $request['body'], true ); // attempt decode
	    	if( $json !== null ) {
		    	update_option('wptoolkit_themes', $json);
				echo $request['body'];
	    	} // return json
	    	
	    }
	}
}

WPToolKit_Updates::init();

add_action( 'wptoolkit_twicedaily_update', array('WPToolKit_Updates', 'get_plugin_catalogue'), 10 );
add_action( 'wptoolkit_twicedaily_update', array('WPToolKit_Updates', 'get_theme_catalogue'), 11 );