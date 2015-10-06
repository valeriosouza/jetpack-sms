<?php
/*
 * Plugin Name: SMS Sharing Button for Jetpack
 * Plugin URI: http://valeriosouza.com.br/portfolio/sms-sharing-button-for-jetpack/
 * Description: Add SMS button to Jetpack Sharing
 * Version: 1.1.3
 * Author: Valerio Souza, WordLab Academy
 * Author URI: http://www.valeriosouza.com.br
 * License: GPLv3 or later
 * Text Domain: jetpack-sms
 * Domain Path: /languages/
 * GitHub Branch: beta
 * GitHub Plugin URI: https://github.com/valeriosouza/jetpack-sms
*/

if( !function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( __FILE__ );
}

add_action( 'admin_init', 'js_check_dependencies' );

//Check if jetpack is active.
function js_check_dependencies() {
	if ( ! is_plugin_active( 'jetpack/jetpack.php' ) ) {
		add_action( 'admin_notices', 'js_dependencies_notice' );
		deactivate_plugins( __FILE__ );

		//I used it to not appear "plugin active" message! We can discuss a better way to do this.
		unset( $_GET['activate'] );
	}
}

//Show error notice if jetpack is NOT active.
function js_dependencies_notice() {
    ?>
    <div class="error">
        <p><strong><?php _e( 'Jetpack has NOT been activated! You need to install and activate the Jetpack plugin to work sharing with SMS.', 'jetpack-sms' ); ?></strong></p>
    </div>
    <?php
}

define( 'jesms__PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'jesms__PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'jesms__PLUGIN_FILE', __FILE__ );
define( 'jesms__VERSION',     '1.1.3' );

add_action( 'init', array( 'Jetpack_SMS_Pack', 'init' ) );

class Jetpack_SMS_Pack {
	static $instance;

	private $data;

	static function init() {
		if( !self::$instance ) {
			if( did_action('plugins_loaded') ) {
				self::plugin_textdomain();
			} else {
				add_action( 'plugins_loaded', array( __CLASS__, 'plugin_textdomain' ) );
			}

			self::$instance = new Jetpack_SMS_Pack;
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts',    	array( &$this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', 	array( &$this, 'admin_menu_assets' ) );
		add_action( 'admin_notices', 		 	array( &$this, 'plugin_donate_notice' ) );
		add_action( 'admin_init', 			 	array( &$this, 'plugin_donate_ignore' ) );
		register_deactivation_hook( __FILE__,	array( &$this, 'update' ));


		if( did_action('plugins_loaded') ) {
			$this->require_services();
		} else {
			add_action( 'plugins_loaded', array( &$this, 'require_services' ) );
		}
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_donate' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_more' ), 10, 2 );
	}

	static function update() {
		global $current_user;
	    $user_id = $current_user->ID;
		delete_user_meta($user_id, 'sms_ignore_notice');
	}

	function register_assets() {
		if( get_option('sharedaddy_disable_resources') ) {
			return;
		}

		if( !Jetpack::is_module_active('sharedaddy') ) {
			return;
		}
		wp_enqueue_script( 'jetpack-sms', jesms__PLUGIN_URL . 'assets/js/count.js', array('jquery','sharing-js'), jesms__VERSION, true );
		wp_enqueue_style( 'jetpack-sms', jesms__PLUGIN_URL . 'assets/css/style.css', array(), jesms__VERSION );
	}

	function admin_menu_assets( $hook ) {
		if( $hook == 'settings_page_sharing' ) {
			wp_enqueue_style( 'jetpack-sms', jesms__PLUGIN_URL . 'assets/css/style.css', array('sharing', 'sharing-admin'), jesms__VERSION );
		}
	}

	function require_services() {
		if( class_exists('Jetpack') ) {
			require_once( jesms__PLUGIN_DIR . 'includes/class.sms-service.php' );
		}
	}

	static function plugin_textdomain() {
		//load_plugin_textdomain( 'jetpack-sms', false, dirname( plugin_basename( jesms__PLUGIN_FILE ) ) . '/languages/' );
		$locale = get_locale();

		load_plugin_textdomain( 'jetpack-sms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	function plugin_row_donate( $links, $file ) {
		if( plugin_basename( jesms__PLUGIN_FILE ) === $file ) {
			$links[] = sprintf(
				'<a target="_blank" href="%s" style="color:#ffa100;font-weight:bold;">%s</a>',
				esc_url('http://wordlab.com.br/donate/?utm_source=plugin&utm_medium=donate&utm_campaign=jetpack-sms'),
				__( 'Donate', 'jetpack-sms' )
			);
		}
		return $links;
	}
	function plugin_row_more( $links, $file ) {
		if( plugin_basename( jesms__PLUGIN_FILE ) === $file ) {
			$links[] = sprintf(
				'<a target="_blank" href="%s" style="color:#ffa100;font-weight:bold;">%s</a>',
				esc_url('http://valeriosouza.com.br/project-tag/jetpack-addons/?utm_source=plugin&utm_medium=more-addons&utm_campaign=jetpack-sms'),
				__( 'More add-ons', 'jetpack-telegram' )
			);
		}
		return $links;
	}
	/* Display a notice that can be dismissed */

	function plugin_donate_notice() {
		global $current_user ;
	        $user_id = $current_user->ID;
	        /* Check that the user hasn't already clicked to ignore the message */
		if ( ! get_user_meta($user_id, 'sms_ignore_notice') ) {
	        echo '<div class="updated"><p>';
	        printf('%s<a target="_blank" href="%s">%s</a>. | <a href="%s">%s</a>',__('Like the plugin to share the SMS Sharing Button for Jetpack? Develop free plugins takes work! Be my boss and make a ', 'jetpack-sms'), 'http://wordlab.com.br/donate/?utm_source=plugin&utm_medium=donate-notice&utm_campaign=jetpack-sms', __('donation of any amount', 'jetpack-sms'), '?sms_nag_ignore=0',__('This plugin does not deserve a donation', 'jetpack-sms'));
	        echo "</p></div>";
		}
	}

	function plugin_donate_ignore() {
		global $current_user;
	        $user_id = $current_user->ID;
	        /* If user clicks to ignore the notice, add that to their user meta */
	        if ( isset($_GET['sms_nag_ignore']) && '0' == $_GET['sms_nag_ignore'] ) {
	             add_user_meta($user_id, 'sms_ignore_notice', 'true', true);
		}
	}
}
