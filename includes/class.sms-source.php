<?php

if( !function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
class jesms_Share_SMS extends Sharing_Source {
	var $shortname = 'sms';

	function __construct( $id, array $settings ) {
		parent::__construct( $id, $settings );

		if( 'official' == $this->button_style )
			$this->smart = true;
		else
			$this->smart = false;
	}

	function get_name() {
		return __( 'SMS', 'jetpack-sms' );
	}

	function has_custom_button_style() {
		return $this->smart;
	}

	private function guess_locale_from_lang( $lang ) {
		if( strpos( $lang, 'ja' ) === 0 )
			return 'ja';

		if( strpos( $lang, 'zh' ) === 0 )
			return 'zh-hant';

		return 'en';
	}

	function get_display( $post ) {
		$locale = $this->guess_locale_from_lang( get_locale() );
			if( $this->smart )
				return sprintf(
					'<div class="sms_button"><a href="sms::?body=%s: %s - %s%s" class="share-sms %s" title="%s"></a></div>',
					__('Look at this','jetpack-sms'),
					rawurlencode( $this->get_share_title( $post->ID ) ),
					rawurlencode( $this->get_share_url( $post->ID ) ),
					rawurlencode( '?utm_source=jetpack-sharing&utm_medium=sms&utm_campaign=mobile' ),
					esc_attr( $locale ),
					esc_attr__( 'SMS it!', 'jetpack-sms' )
				);
			else
				return $this->get_link( get_permalink( $post->ID ), _x( 'SMS', 'share to', 'jetpack-sms' ), __( 'Click to share on SMS', 'jetpack-sms' ), 'share=sms' );
	}

	function display_header() {
	}

	function display_footer() {
		$this->js_dialog( $this->shortname );
	}

	function process_request( $post, array $post_data ) {

		$url = add_query_arg( array(
		    'utm_source' => 'jetpack-sharing',
		    'utm_medium' => 'sms',
		    'utm_campaign' => 'mobile'
		), $this->get_share_url( $post->ID ) );

		$params = array(
		    'body' => __( 'Look at this', 'jetpack-sms' ) . ': ' . $this->get_share_title( $post->ID ) .' - ' . $url,
		);

		$sms_url = 'sms:?' . http_build_query( $params );

		// Record stats
		parent::process_request( $post, $post_data );

		// Redirect to SMS
		wp_redirect( $sms_url );
		die();
	}
}
