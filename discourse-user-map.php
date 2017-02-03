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

	public function add_template_to_dropdown( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );

		return $posts_templates;
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
		write_log( $data->get_json_params() );
		$user_field_data  = $data->get_json_params()['user']['user_fields'];
		$user_fields_info = $this->get_discourse_user_fields_info( 'http://localhost:3000' );

		$user_location = [];

		foreach ( $user_fields_info as $field_info ) {
			$this->process_user_field( 'city', $field_info, $user_field_data, $user_location );
			$this->process_user_field( 'state/province', $field_info, $user_field_data, $user_location );
			$this->process_user_field( 'country', $field_info, $user_field_data, $user_location );
			$this->process_user_field( 'map', $field_info, $user_field_data, $user_location );
		}

		if ( ! empty( $user_location['city'] ) || ! empty( $user_location['state/province'] ) || ! empty( $user_location['country'] ) ) {
			$location = $user_location['city'] . ' ' . $user_location['state/province'] . ' ' . $user_location['country'];
			write_log($location);
		}

	}

	protected function process_user_field( $field_name, $field_info, $field_data, &$user_location ) {
		if ( $field_name === strtolower( $field_info['name'] ) ) {
			$id                           = $field_info['id'];
			$user_location[ $field_name ] = isset( $field_data[ $id ] ) ? $field_data[ $id ] : '';
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

	protected function get_discourse_user_fields_info( $discourse_url, $force_update = null ) {
		$user_fields_info = get_transient( 'discourse_user_fields' );

		if ( empty( $user_fields_info || $force_update ) ) {
			$discourse_url = $discourse_url . '/site.json';
			$remote        = wp_remote_get( $discourse_url );

			if ( ! $this->validate_remote_get( $remote ) ) {

				return 0;
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );

			if ( ! empty( $remote['user_fields'] ) ) {
				$user_fields_info = $remote['user_fields'];

				set_transient( 'discourse_user_fields', $user_fields_info, DAY_IN_SECONDS );
			} else {

				return new \WP_Error( 'key_not_found', 'The user_field key was not found in the response.' );
			}
		}

		return $user_fields_info;
	}

	protected function validate_remote_get( $response ) {
		if ( empty( $response ) ) {
			error_log( 'Discourse has returned an empty response.' );

			return 0;
		} elseif ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}
}

$discourse_user_map = new \DiscourseUserMap\DiscourseUserMap();
