<?php
/**
 * WPContactUs.
 *
 * @package   WPContactUs
 * @author    2018 Programming Minds, Inc.
 * @license   GPL-2.0+
 * @link      http://www.wpcontactus.com
 * @copyright 2018 Programming Minds, Inc.
 */

/**
 * Class Conversations_Post_Calls.
 * Verify the login
 */
class Conversations_Post_Calls {

	/**
	 * Verifies the login for a POST based form (i.e. one not using ajax).
	 */
	public static function login_verify() {

		if ( ! isset( $_POST['convx_action'] ) ) {
			return;
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		$convx_action = sanitize_text_field( wp_unslash( $_POST['convx_action'] ) );

		if ( ! isset( $_POST['user_email'] ) || ! isset( $_POST['user_password'] ) || ! isset( $_POST['redirect_success_uri'] ) || ! isset( $_POST['redirect_fail_uri'] ) || ! isset( $_POST['local_dt'] ) ) {
			die();
		}

		if ( 'login_verify' === $convx_action ) {
			$login_data = array();
			$login_data['user_login'] = sanitize_email( wp_unslash( $_POST['user_email'] ) );
			$login_data['user_password'] = sanitize_text_field( wp_unslash( $_POST['user_password'] ) );

			$login_data['remember'] = false;
			$server_time = current_time( 'mysql' );

			$wp_user = wp_signon( $login_data, false );

			if ( is_wp_error( $wp_user ) ) {
				wp_redirect( esc_url_raw( wp_unslash( $_POST['redirect_fail_uri'] ) ) );
			} else {

				$server_client_time_diff = round( ( strtotime( sanitize_text_field( wp_unslash( $_POST['local_dt'] ) ) ) - strtotime( current_time( 'mysql' ) ) ) / 60 );
				update_option( 'conversations_timediff_' . $wp_user->ID, $server_client_time_diff );
				update_option( 'conversations_last_login_' . $wp_user->ID, $server_time );

				// Redirect to the successful login uri.
				// Over there we will double check whether the user has the right role: to view the end user screen or the responder screen.
				wp_redirect( esc_url_raw( wp_unslash( $_POST['redirect_success_uri'] ) ) );
			}

			exit;
		}
	}
}

// POST Form based calls
// Class method to handle POST form calls.
add_action( 'init', array( 'Conversations_Post_Calls', 'login_verify' ) );
