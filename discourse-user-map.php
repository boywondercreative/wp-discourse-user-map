<?php
/*
 * Plugin Name: Discourse User Map
 * Version: 0.1
 * Author: scossar
 */

namespace DiscourseUserMap;

use DiscourseUserMap\PageTemplater;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	require_once( __DIR__ . '/pagetemplater.php' );
	PageTemplater\PageTemplater::get_instance();
}

class DiscourseUserMap {

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
		$request_array = $data->get_json_params();

		if ( ! empty( $request_array['user'] ) && ! empty( $request_array['user']['location'] ) ) {
			$user_data = $data->get_json_params()['user'];
			$location = $user_data['location'];
			$username = $user_data['username'];

			$marker_data = array(
				'markername' => $username,
				'geocode' => $location,
				'layer' => 3,
			);

			write_log($marker_data);

			return \MMPAPI::add_marker( $marker_data );
		}

		return null;
	}

	/**
	 * @param $data
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
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

new \DiscourseUserMap\DiscourseUserMap();
