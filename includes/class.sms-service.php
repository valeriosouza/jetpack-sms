<?php

if( !function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( did_action('jetpack_modules_loaded') ) {
	jesms_Sharing_Service::init();
} else {
	add_action( 'jetpack_modules_loaded', array( 'jesms_Sharing_Service', 'init' ) );
}

class jesms_Sharing_Service {
	static $instance;

	static function init() {
		if( !Jetpack::is_module_active('sharedaddy') ) {
			return false;
		}

		if( !self::$instance ) {
			self::$instance = new jesms_Sharing_Service;
		}

		return self::$instance;
	}

	function __construct() {
		add_filter( 'sharing_services', array( &$this, 'add_sharing_services' ) );
	}

	function add_sharing_services( $services ) {
		include_once jesms__PLUGIN_DIR . 'includes/class.sms-source.php';

		if( !array_key_exists( 'sms', $services ) ) {
			$services['sms'] = 'jesms_Share_SMS';
		}

		return $services;
	}
}
