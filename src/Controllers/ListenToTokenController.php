<?php


namespace InstafeedHub\Controllers;


use InstafeedHub\Helpers\Message;
use InstafeedHub\Helpers\Option;
use InstafeedHub\Helpers\Session;
use InstafeedHub\Helpers\User;

class ListenToTokenController {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'adminScripts' ] );
		add_action( 'wp_ajax_instafeedhub_save_tokens', [ $this, 'saveInstafeedHubTokens' ] );
		add_action( 'wp_ajax_instafeedhub_get_access_tokens', [ $this, 'getAccessToken' ] );
	}

	public function getAccessToken() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return Message::error( [ 'msg' => 'You do not have permission to access this page' ], 403 );
		}

		$aTokens = Option::getTokens();
		if ( empty( $aTokens['refreshToken'] ) ) {
			return Message::error( [ 'msg' => esc_html__( 'The refresh token is emptied', 'instafeedhub-wp' ) ], 401 );
		}

		$response = wp_remote_post( 'https://instafeedhub.com/wp-json/instafeedhub/v1/renew-token', [
			'body' => [
				'refreshToken' => $aTokens['refreshToken']
			]
		] );

		if ( empty( $response ) || is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
			return Message::error( [ 'msg' => esc_html__( 'Server Error', 'instafeedhub-wp' ) ], 503 );
		}

		$aResponse = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $aResponse['status'] == 'error' ) {
			return Message::error( [ 'msg' => $aResponse['msg'] ], isset( $aResponse['code']) ? $aResponse['code'] :
				401 );
		}

		return Message::success( [ 'accessToken' => $aResponse['accessToken'] ] );
	}

	public function adminScripts() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( ! isset( $_GET['post_type'] ) && ! isset( $_GET['post'] ) ) {
			return false;
		}

		$aTokens = Option::getTokens();
		if ( isset( $_GET['post'] ) ) {
			$aArgs['id'] = abs( $_GET['post'] );
		}

		$aData = [
			'accessToken'    => $aTokens['accessToken'],
			'email'          => get_option( 'admin_email' ),
			'variation'      => 'instafeedhub',
			'nickname'       => User::getUserNickname(),
			'whitelistedUrl' => home_url( '/' ),
			'createdAt'      => time(),
			'version'        => IFH_VERSION
		];
		if ( isset( $_GET['post'] ) ) {
			$aData['id'] = abs( $_GET['post'] );
		}

		wp_localize_script( 'jquery', 'InstafeedHubTokens', $aData );
	}

	public function saveInstafeedHubTokens() {
		if ( ! isset( $_POST['payload']['accessToken'] ) || ! isset( $_POST['payload']['refreshToken'] ) ) {
			wp_send_json_error( [ 'msg' => 'The tokens are required' ] );
		}

		$aTokens = [
			'accessToken'  => $_POST['payload']['accessToken'],
			'refreshToken' => $_POST['payload']['refreshToken'],
		];

		Option::saveTokens( $aTokens );

		wp_send_json_success( esc_html__( 'The data has been updated', 'wiloke-instafeed-hub' ) );
	}
}
