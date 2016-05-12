<?php
/**
 * Admin
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPTAPI_Admin Class
 */
class WPTAPI_Admin {

	public function __construct() {
		
		add_action( 'wp_loaded', array( $this, 'init' ) );
		
	}

	/**
	 * Hook in methods.
	 */
	public static function init() {

		add_action('admin_menu', array(__CLASS__, 'wptoolkit_create_menu'), 9999);
		
	}

	public static function wptoolkit_create_menu() {

		
		add_menu_page( 
	        __( 'WP Toolkit Plugin Manager', 'wptoolkit' ),
	        'WP Toolkit',
	        'manage_options',
	        'wptoolkit-plugin-manager',
	        array(__CLASS__, 'wptoolkit_settings_display'),
	        plugins_url( 'wptoolkit/assets/images/wptoolkit-icon.png' ),
	        56
	    );
	    add_submenu_page('wptoolkit-plugin-manager', 'WP Toolkit Plugins', 'Plugins', 'manage_options', 'wptoolkit-plugin-manager' );
		add_submenu_page('wptoolkit-plugin-manager', 'WooCommerce Plugins', 'WooCommerce', 'manage_options', 'wptoolkit-woocommerce-manager', array(__CLASS__, 'wptoolkit_settings_display'));
		add_submenu_page('wptoolkit-plugin-manager', 'WP Tollkit Themes', 'Themes', 'manage_options', 'wptoolkit-theme-manager', array(__CLASS__, 'wptoolkit_settings_display'));



	}

	public static function wptoolkit_settings_display() { 
		
		
		
		switch ($_GET["page"]) {
			case "wptoolkit-woocommerce-manager":
				$type = "woocommerce";
				break;
			case "wptoolkit-theme-manager":
				$type = "theme";
				break;
			default:
				$type = "plugin";
				break;
		}

		if ($type != "theme" && !get_option('wptoolkit_plugins')) {
			WPToolKit_Updates::get_plugin_catalogue(false);
		}elseif($type == "theme" && !get_option('wptoolkit_themes')) {
			WPToolKit_Updates::get_theme_catalogue(false);
		}
	

	?>
 
	    <div class="wrap">

	        <?php if( isset($_GET['settings-updated']) ) { ?>
	            <div id="message" class="updated">
	                <p><strong><?php _e('Settings saved.') ?></strong></p>
	            </div>
	        <?php } ?>
	        
	        <form method="post" action="">
                    <!-- div class="wptoolkit-nav">
                        <div class="gk-section gk-group">
							<div class="gk-col gk-span_1_of_4">
								<a class="filter" class="filter active" data-filter="all">All</a>
								<a class="filter" data-filter=".accounting">Accounting</a>
								<a class="filter" data-filter=".customer-service">Customer Service</a> 
								<a class="filter" data-filter=".import-export">Import and Export</a>	
							</div>
							<div class="gk-col gk-span_1_of_4">
								<a class="filter" data-filter=".inventory-management">Inventory Management</a>
								<a class="filter" data-filter=".marketing">Marketing</a>
								<a class="filter" data-filter=".misc">Misc</a>
								<a class="filter" data-filter=".payment-gateways">Payment Gateways</a>	
							</div>
							<div class="gk-col gk-span_1_of_4">
								<a class="filter" data-filter=".point-of-sale">Point of Sale</a>
								<a class="filter" data-filter=".products">Products</a> 
								<a class="filter" data-filter=".reporting">Reporting</a>
								<a class="filter" data-filter=".shipping-methods">Shipping Methods</a>
							</div>
							<div class="gk-col gk-span_1_of_4">
								<a class="filter" data-filter=".storefront">Storefront</a>
								<a class="filter" data-filter=".subscriptions">Subscriptions</a>
								<a class="filter" data-filter=".tax">Tax</a>
								<a class="filter" data-filter=".widgets">Widgets</a>
							</div>
						</div>
                    </div -->

	                <ul id="Container" class="gkitcontainer">

	                	<?php
						$item_thumbnail = false;
						if($type == "plugin" || $type == "woocommerce"){
							$wptoolkit_items = get_option('wptoolkit_plugins');
							$label = "plugin";
							$dir_root = WP_PLUGIN_DIR;
							$file_key = "Plugin_file";
							$nonce_prefix = "install-plugin_";
							$install_action = "install-plugin&plugin=";
						}elseif($type == "theme"){
							$wptoolkit_items = get_option('wptoolkit_themes');
							$label = "theme";
							$curr_theme = get_current_theme ();
							$dir_root = get_theme_root();
							$file_key = "Theme_file";
							$nonce_prefix = "install-theme_";
							$install_action = "install-theme&theme=";
							$item_thumbnail = "http://api.wptoolkit.com/?request=thumbnail&theme_id=";
						}
		
	                	if(is_array($wptoolkit_items)) {

		                	foreach($wptoolkit_items as $key => $item) {
								
								if( ($type == "woocommerce" && (!preg_match("/\bwoocommerce/i",$item['name']) && !preg_match("/\bwoocommerce/i",$key)))
									|| ($type == "plugin" && (preg_match("/\bwoocommerce/i",$item['name']) || preg_match("/\bwoocommerce/i",$key)))
								){
									continue;
								}
								
		                		$item_description = (isset($item['description']) ? $item['description'] : $item['Description']);
		                		$item_category = $item['category'];
								$maxLength = 200;
								if (strlen($item_description) > $maxLength) {
								    $stringCut = substr($item_description, 0, $maxLength);
								    $item_description = substr($stringCut, 0, strrpos($stringCut, ' ')); 
								}
								$item_description = strip_tags($item_description, '<cite>');
								
								if (!empty($item['wptoolkit_name'])) {
									$item_name = $item['wptoolkit_name'];
								} else {
									$item_name = (isset($item['name']) ? $item['name'] : $item['Name']);
								}
								
								$item_active = false;
								if( ($type == "theme" && $curr_theme == $item_name) || ( ($type == "woocommerce" || $type == "plugin") && in_array( $key, apply_filters('active_plugins', get_option('active_plugins')) ) )){
									$item_active = true;
								}
		                	?>
		                		
		                        <li class="gkititem mix <?php echo implode(' ', (array)$item_category); ?>">
		                            <div class="wpt-plugin-wrapper">
		                                <span class="wpt-plugin-title"><?php echo $item_name; ?></span>
		                                <div class="wpt-plugin-inner"><p><?php if($item_thumbnail) echo "<img style=\"width:100%;position:initial;\" src=\"".$item_thumbnail.$item["theme_id"]."&type=".$type."\"/>"; else echo $item_description; ?></p></div> 

	                            		<?php if ($item_active) { ?>
	                            			<button type="submit" data-plugin="<?php echo $key; ?>" class="button install-plugin pl-activated" value="Activated" disabled>Activated</button>

	                            		<?php } else if (file_exists(trailingslashit($dir_root). $item[$file_key]) ) { ?>
											<button type="submit" data-plugin="<?php echo $key; ?>" class="button install-plugin pl-installed" value="Install" disabled>Installed</button>	
	                            		
	                            		<?php } else if ( $item['free'] == 1 ) { ?>
	                            			<button type="submit" data-plugin="<?php echo $key; ?>" class="button install-plugin" value="Install">Install for free</button>
	                            		
	                            		<?php } else if ( get_option( 'wptoolkit_plugin_manager_activated' ) != 'Activated' ) { ?>
	                            			<button type="submit" id="install" class="button install-plugin pl-licence-required" value="Install" disabled>A License key is required to install this <?php echo $label; ?></button>

	                            		<?php } else { ?>
	                            			<!-- button type="submit" data-plugin="<?php echo $key; ?>" class="button install-plugin type-<?php echo $type; ?>" value="Install">Install</button --> 
											<a href="<?php echo admin_url('update.php')?>?action=<?php echo $install_action; ?><?php echo urlencode($key); ?>&_wpnonce=<?php echo wp_create_nonce($nonce_prefix.$key);?>&type=WPT" class="button install-plugin">New Install</a> 
	                            		<?php } ?>
		                            </div>
		                        </li>
	                    <?php 
	                		}
	                	} ?>
	                </ul>
	        </form>
	    </div>

	    <div id="wptoolkit-notice">
	    	<div class="gk-inner">
	    		<p><strong>Disclaimer: </strong>Woo, WooThemes, WooCommerce, Elegant Themes, StudioPress & iThemes are all Trademarks of their respective owners. WP ToolKit is not associated or endorsed by them in any way. These products are not developed by WP Toolkit and are released & redistributed under the GPL license.</p>
	    	</div>
	    </div>
	    
	    <script type="text/javascript">
	        jQuery(document).ready(function($) {  
	        	// $(".install-plugin").click(function(e) {
	        		// var installButton = jQuery(this);
	        		// e.preventDefault();
					// var data = {};
					// if(installButton.hasClass("type-theme")){
						// data = {
							// 'action': 'wptoolkit_install_theme',
							// 'theme': $(this).attr('data-plugin')
						// };
					// }else{
						// data = {
							// 'action': 'wptoolkit_install_plugin',
							// 'plugin': $(this).attr('data-plugin')
						// };
					// }
					// var spinner = $("<img src='<?php echo plugins_url( '../assets/images/ajax-loader.gif' , __FILE__ ); ?>' />").insertAfter(this);

					// jQuery.post(ajaxurl, data, function(response) {

							// installButton.prop('disabled', true);
				   			// installButton.text(response);
				   			// if (response == 'Installed') {
				   				// installButton.addClass('pl-installed');
				   			// }
						
					    // spinner.remove();  
					// });

	        	// });

	            $(function(){  
	                $('#Container').mixItUp();
	            });
	    	});
	    </script>

	<?php }	
}

WPTAPI_Admin::init();