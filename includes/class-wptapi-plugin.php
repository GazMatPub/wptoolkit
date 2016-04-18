<?php
/**
 * Plugin
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPToolKit_Plugin Class
 */
class WPToolKit_Plugin {

	public function __construct() {
		
		add_action( 'wp_loaded', array( $this, 'init' ) );

	}

	/**
	 * Hook in methods.
	 */
	public static function init() {

		add_action( 'wp_ajax_wptoolkit_install_plugin', array( __CLASS__, 'wpt_ajax_install_plugin' ) );
		add_action( 'wp_ajax_wptoolkit_install_theme', array( __CLASS__, 'wpt_ajax_install_theme' ) );
		add_filter( 'all_plugins', array( __CLASS__, 'wpt_override_plugin_names') );
		
	}

	public static function wpt_install_plugins($plugins, $type = "plugin") {

		if($type == "theme"){
			$args = array(
				'path' => ABSPATH.'wp-content/themes/',
				'preserve_zip' => false
			);
			$wptoolkit_plugins = get_option('wptoolkit_themes');
			$request = "wpt_theme_download=get&theme_id";
			$id_key = "theme_id";
			$file_key = "Theme_file";
		}elseif($type == "plugin"){
			$args = array(
				'path' => ABSPATH.'wp-content/plugins/',
				'preserve_zip' => false
			);
			$wptoolkit_plugins = get_option('wptoolkit_plugins');
			$request = "wpt_plugin_download=get&plugin_id";
			$id_key = "plugin_id";
			$file_key = "Plugin_file";
		}
			
	    foreach($plugins as $plugin) {
	    	
			$wptoolkit_licence_manager = get_option('wptoolkit_plugin_manager');
	    	$email = $wptoolkit_licence_manager['activation_email'];
			$licence_key = $wptoolkit_licence_manager['api_key'];
			$product_id = 'WPToolKit%20Plugin%20Manager';
			$instance = get_option('wptoolkit_plugin_manager_instance');


			$plugin_url = 'https://api.wptoolkit.com/?'.$request.'='.$wptoolkit_plugins[$plugin][$id_key].'&email='.$email.'&licence_key='.$licence_key.'&product_id='.$product_id.'&instance='.$instance.'&request=wptoolkit_status';

	    	$url = $plugin_url;
			$item_dir = dirname($wptoolkit_plugins[$plugin][$file_key]);
	    	$path = $args['path'].$item_dir.'.zip';

	    	// if (file_exists($args['path'].$plugin)) {
	    	if (file_exists($args['path'].$item_dir)) {
		    	return "Error 1003"; // Plugin already existed
	    	}

	    	if ( $wptoolkit_plugins[$plugin]['free'] != 1 && get_option( 'wptoolkit_plugin_manager_activated' ) != 'Activated' ) {
	    		return "Error 1006";
	    	}

	    	// $ch = curl_init($url);
			// curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.6 (KHTML, like Gecko) Chrome/20.0.1090.0 Safari/536.6');
		    // $data = curl_exec($ch); 
		    // if(curl_errno($ch))
			// {
		    	// return "Error 1002"; // 1002 failed to download plugin from URL
			// }

		    // curl_close($ch);
		    
			$file = WPT_remote_download($url);
			
			$target_path = $args['path'].$file["filenam"];
		    if(is_array($file) && file_put_contents($target_path, $file["body"])) {

		    	if($zip = zip_open($target_path)) {

		    		if (is_resource($zip)) {
			            while($entry = zip_read($zip))
			            {
			                    $is_file = substr(zip_entry_name($entry), -1) == '/' ? false : true;
			                    $file_path = $args['path'].zip_entry_name($entry);
			                    if($is_file)
			                    {
			                            if(zip_entry_open($zip,$entry,"r")) 
			                            {
			                                    $fstream = zip_entry_read($entry, zip_entry_filesize($entry));
			                                    file_put_contents($file_path, $fstream );
			                                    //chmod($file_path, 0777);
			                            }
			                            zip_entry_close($entry);
			                    }
			                    else
			                    {
			                            if(zip_entry_name($entry))
			                            {
			                            		if(!is_dir($file_path)){
			                                    	mkdir($file_path);
			                                    }
			                                    //chmod($file_path, 0777);
			                            }
			                    }
			            }
			        } else {
			        	
			        	return "Error 1005";
			        }
		            zip_close($zip);
			    } else {	
		    		return "Error 1004"; // 1004 couldn't open zip file
			    }
			    if($args['preserve_zip'] === false)
			    {
			            unlink($target_path);
			    }

		    } else {
				unlink($target_path);
				// var_dump($file);
		    	return "Error 1001 ". $url; // 1001 failed to put zip file in directory
		    }
	    }
	    return 'Installed';
	}

	public static function wpt_ajax_install_plugin() {
		$plugins = array($_POST['plugin']);
		echo WPToolKit_Plugin::wpt_install_plugins($plugins);
		wp_die();
	}

	public static function wpt_ajax_install_theme() {
		$themes = array($_POST['theme']);
		echo WPToolKit_Plugin::wpt_install_plugins($themes, "theme");
		wp_die();
	}

	public function wpt_override_plugin_names($plugins) {
		
		if ($wptoolkit_plugins = get_option('wptoolkit_plugins')) {
			foreach($plugins as $key => $plugin) {
				if (array_key_exists($key, $wptoolkit_plugins)) {
					if (!empty($wptoolkit_plugins[$key]['wptoolkit_name'])) {
						$plugins[$key]['Name'] = $wptoolkit_plugins[$key]['wptoolkit_name'];
					}
				}
			}
		}
		return $plugins;
	}

}

WPToolKit_Plugin::init();
