<?php
/*
 * Plugin Name: Discourse User Map
 * Version: 0.1
 * Author: scossar
 */

namespace DiscourseUserMap;

class DiscourseUserMap {

	// Used for development.
	function write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'initialize_location_route' ) );
	}

	public function initialize_location_route() {
		register_rest_route( 'scossar/v1', '/user-location', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'process_discourse_request' ),
		) );
	}

	public function process_discourse_request( $data ) {
		$data = $this->verify_discourse_request( $data );

		if ( is_wp_error( $data ) ) {
			error_log( $data->get_error_message() );

			return null;
		}
		$user_fields = $data->get_json_params()['user']['user_fields'];
		write_log( $user_fields );
	}

	protected function get_discourse_user_field_info( $discourse_url, $force_update = null ) {
		$discourse_user_fields = get_transient( 'discourse_user_fields' );

		if ( empty( $discourse_user_fields || $force_update ) ) {
			$discourse_url = $discourse_url . '/site.json';
			$site_json = wp_remote_get( $discourse_url );
		}

	}

	protected function verify_discourse_request( $data ) {
		if ( $sig = substr( $data->get_header( 'X-Discourse-Event-Signature' ), 7 ) ) {
			$raw    = $data->get_body();
			$secret = 'thisisfortesting';
			if ( $sig === hash_hmac( 'sha256', $raw, $secret ) ) {

				return $data;
			} else {

				return new \WP_Error( 'Authentication Failed', 'Discourse Webhook Request Error: signatures did not match.' );
			}
		} else {
			return new \WP_Error( 'Access Denied', 'Discourse Webhook Request Error: request did not originate from your Discourse webhook.' );
		}
	}

}

$discourse_user_map = new \DiscourseUserMap\DiscourseUserMap();