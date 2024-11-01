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
 * Class Conversations_Ajax_Calls.
 * All the ajax based calls are handled in this class
 */
class Conversations_Ajax_Calls {
	/**
	 * Verify the user's login credentials.
	 * If you are using your own login interface, you may not need to use this function.
	 *
	 * However, note that this function gets the users' local date and time from a field 'local_dt'
	 * in the login form. It stores the difference between the GMT time and the users *current* time in
	 * a WordPress option field conversations_timediff_<userID>.
	 *
	 * This enables the conversation screens to display conversations in the user's local time,
	 * regardless of his or her location during that session.
	 */
	function login_verify_ajax() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['user_email'] ) || ! isset( $_POST['user_password'] ) || ! isset( $_POST['local_dt'] ) || ! isset( $_POST['redirect_success_uri'] ) || ! isset( $_POST['redirect_fail_uri'] ) ) {
			return;
		}

		// Is the user already logged in?
		if ( is_user_logged_in() ) {

			// Redirect to the successful login uri.
			// Over there we will double check whether the user has the right role: to view the end user screen or the responder screen.
			echo sanitize_text_field( wp_unslash( $_POST['redirect_success_uri'] ) ); // WPCS: XSS OK.
			die();
		}

		// Login the user.
		$login_data = array();

		$login_data['user_login'] = sanitize_email( wp_unslash( $_POST['user_email'] ) );
		$login_data['user_password'] = sanitize_text_field( wp_unslash( $_POST['user_password'] ) );

		$login_data['remember'] = false;
		$server_time = current_time( 'mysql' );

		// WordPress will sanitize the login data.
		$wp_user = wp_signon( $login_data, false );

		if ( is_wp_error( $wp_user ) ) {
			// Return the URI to go to if the login fails.
			echo esc_url_raw( wp_unslash( $_POST['redirect_fail_uri'] ) );  // WPCS: XSS OK.
		} else {
			// Store the time difference between the users *current* location and the GMT time. It could be
			// a positive OR negative value. This will be used to display the time of the conversation in the user's current
			// local time.
			$server_client_time_diff = round( ( strtotime( sanitize_text_field( wp_unslash( $_POST['local_dt'] ) ) ) - strtotime( current_time( 'mysql' ) ) ) / 60 );
			update_option( 'conversations_timediff_' . $wp_user->ID, $server_client_time_diff );
			update_option( 'conversations_last_login_' . $wp_user->ID, $server_time );

			// Return the URI to go to if the login succeeds.
			echo esc_url_raw( wp_unslash( $_POST['redirect_success_uri'] ) );  // WPCS: XSS OK.
		}

		die();
	}

	/**
	 * End user hit Send to send a reply.
	 * This is called a 'followup' to the conversation that began with the form submission.
	 */
	function end_users_comments() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['convx_id'] ) || ! isset( $_POST['logged_in_user_id'] ) || ! isset( $_POST['user_message'] ) ) {
			return;
		}

		$convx_id = absint( sanitize_text_field( wp_unslash( $_POST['convx_id'] ) ) );
		$logged_in_user_id = absint( sanitize_text_field( wp_unslash( $_POST['logged_in_user_id'] ) ) );

		$new_str = str_replace( '<br />', '--break--', nl2br( $_POST['user_message'] ) );
		$user_message = str_replace( '--break--', '<br />', sanitize_text_field( wp_unslash( $new_str ) ) );

		$server_time = current_time( 'mysql' );

		$followup = array(
			'convx_id'         => $convx_id,
			'fup_author'       => $logged_in_user_id,
			'fup_text'         => $user_message,
			'fup_inserted'     => $server_time,
		);

		$followup_types = array(
			'%d',
			'%s',
			'%s',
			'%s',
		);

		// WordPress will sanitize the data.
		$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_followups', $followup, $followup_types );  // Db call ok; no-cache ok.
		if ( false === $ret_value ) {
			return;
		}

		// Wordpress will sanitize the data.
		$ret_value = $wpdb->update($wpdb->prefix . 'convxns_forms',
			array(
				'convx_viewedby_r' => '0',
				'convx_updated' => $server_time,
			),
			array(
				'id' => $convx_id,
			),
			array(
				'%d',
				'%s',
			),
			array(
				'%d',
			)
		); // Db call ok; no-cache ok.

		die();
	}

	/**
	 * Responder hit "Send" to send a reply.
	 * This is called a 'followup' to the conversation that began with the form submission.
	 */
	function responders_comments() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['convx_id'] ) || ! isset( $_POST['logged_in_user_id'] ) || ! isset( $_POST['user_message'] ) ) {
			return;
		}

		$convx_id = absint( sanitize_text_field( wp_unslash( $_POST['convx_id'] ) ) );
		$logged_in_user_id = absint( sanitize_text_field( wp_unslash( $_POST['logged_in_user_id'] ) ) );

		$new_str = str_replace( '<br />', '--break--', nl2br( $_POST['user_message'] ) );
		$user_message = str_replace( '--break--', '<br />', sanitize_text_field( wp_unslash( $new_str ) ) );

		$server_time = current_time( 'mysql' );

		$followup = array(
			'convx_id'         => $convx_id,
			'fup_author'       => $logged_in_user_id,
			'fup_text'         => $user_message,
			'fup_inserted'     => $server_time,
		);

		$followup_types = array(
			'%d',
			'%s',
			'%s',
			'%s',
		);

		// WordPress will sanitize the data.
		$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_followups', $followup, $followup_types );
		if ( false === $ret_value ) {
			return;
		}

		// WordPress will sanitize the data.
		$ret_value = $wpdb->update( $wpdb->prefix . 'convxns_forms',
			array(
				'convx_viewedby_eu' => '0',
				'convx_updated' => $server_time,
			),
			array(
				'id' => $convx_id,
			),
			array(
				'%d',
				'%s',
			),
			array(
				'%d',
			)
		); // Db call ok; no-cache ok.





		die();
	}

	/**
	 * When the user clicks on the conversation, this function is invoked to record
	 * the fact that the conversation messages were 'seen' by the user.
	 * This info is used to display tick marks next to the date and time of the conversation
	 * message.
	 */
	function convx_viewed() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['convx_id'] ) || ! isset( $_POST['largest_follow_up_id_i_see'] ) || ! isset( $_POST['user_role'] ) ) {
			return;
		}

		$server_time = current_time( 'mysql' );

		// _r refers to the 'responder'. _eu refers to the end user.
		switch ( sanitize_text_field( wp_unslash( $_POST['user_role'] ) ) ) {
			case CONVERSATIONS_RESPONDER_USER_ROLE:
					$largest_follow_up_id_i_see_field_name = 'convx_last_fup_id_viewedby_r';
					$convx_viewedby_field = 'convx_viewedby_r';
					$convx_last_dt_viewedby_field = 'convx_last_dt_viewedby_r';
					$convx_form_viewedby = 'convx_form_viewedby_r';
				break;
			case CONVERSATIONS_END_USER_ROLE:
					$largest_follow_up_id_i_see_field_name = 'convx_last_fup_id_viewedby_eu';
					$convx_viewedby_field = 'convx_viewedby_eu';
					$convx_last_dt_viewedby_field = 'convx_last_dt_viewedby_eu';
					$convx_form_viewedby = 'convx_form_viewedby_eu';
				break;
			default:
				die();
		}

		$ret_value = $wpdb->update( $wpdb->prefix . 'convxns_forms',
			array(
				$largest_follow_up_id_i_see_field_name => absint( sanitize_text_field( wp_unslash( $_POST['largest_follow_up_id_i_see'] ) ) ),
				$convx_viewedby_field => 1,
				$convx_last_dt_viewedby_field => $server_time,
				$convx_form_viewedby => 1,
			),
			array(
				'id' => absint( sanitize_text_field( wp_unslash( $_POST['convx_id'] ) ) ),
			),
			array(
				'%d',
				'%d',
				'%s',
				'%d',
			),
			array(
				'%d',
			)
		); // Db call ok; no-cache ok.






		die();
	}

	/**
	 * This function is called when a responder deletes a conversation thread.
	 */
	function del_convx() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['convx_id'] ) || ! isset( $_POST['user_role'] ) ) {
			return;
		}

		switch ( sanitize_text_field( wp_unslash( $_POST['user_role'] ) ) ) {
			case CONVERSATIONS_RESPONDER_USER_ROLE:
				break;
			case CONVERSATIONS_END_USER_ROLE:
				die();
			default:
				die();
		}

		// Delete the info of the original form.
		$wpdb->delete( "{$wpdb->prefix}convxns_forms",
			array(
				'id' => absint( sanitize_text_field( wp_unslash( $_POST['convx_id'] ) ) ),
			),
			array(
				'%d',
			)
		); // Db call ok; no-cache ok.

		// Delete the follow up parts.
		$wpdb->delete( "{$wpdb->prefix}convxns_followups",
			array(
				'convx_id' => absint( sanitize_text_field( wp_unslash( $_POST['convx_id'] ) ) ),
			),
			array(
				'%d',
			)
		); // Db call ok; no-cache ok.

		die();
	}

	/**
	 * This function is called when the user clicks on the icon to resort the conversations display.
	 */
	function sort_settings() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['setting'] ) || ! isset( $_POST['user_id'] ) ) {
			return;
		}

		$user_id = absint( wp_unslash( $_POST['user_id'] ) );
		$setting = sanitize_text_field( wp_unslash( $_POST['setting'] ) );

		switch ( $setting ) {
			case 1:
				update_option( 'conversations_convxns_sort_method_' . $user_id, 'asc' );
				break;
			case 2:
				update_option( 'conversations_convxns_sort_method_' . $user_id, 'desc' );
				break;
			case 3:
				update_option( 'conversations_convxns_unread_on_top_' . $user_id, 'true' );
				break;
			case 4:
				update_option( 'conversations_convxns_unread_on_top_' . $user_id, 'false' );
				break;
		}
		echo 'OK'; // WPCS: XSS OK.
		die();
	}
}

// Actions related to the Login form.
add_action( 'wp_ajax_login_verify_ajax', array( 'Conversations_Ajax_Calls', 'login_verify_ajax' ) );
add_action( 'wp_ajax_nopriv_login_verify_ajax', array( 'Conversations_Ajax_Calls', 'login_verify_ajax' ) );

// Actions related to Viewing the conversations screen.
add_action( 'wp_ajax_end_users_comments', array( 'Conversations_Ajax_Calls', 'end_users_comments' ) );
add_action( 'wp_ajax_nopriv_end_users_comments', array( 'Conversations_Ajax_Calls', 'end_users_comments' ) );

add_action( 'wp_ajax_responders_comments', array( 'Conversations_Ajax_Calls', 'responders_comments' ) );
add_action( 'wp_ajax_nopriv_responders_comments', array( 'Conversations_Ajax_Calls', 'responders_comments' ) );

add_action( 'wp_ajax_convx_viewed', array( 'Conversations_Ajax_Calls', 'convx_viewed' ) );
add_action( 'wp_ajax_nopriv_convx_viewed', array( 'Conversations_Ajax_Calls', 'convx_viewed' ) );

add_action( 'wp_ajax_nopriv_del_convx', array( 'Conversations_Ajax_Calls', 'del_convx' ) );
add_action( 'wp_ajax_del_convx', array( 'Conversations_Ajax_Calls', 'del_convx' ) );

add_action( 'wp_ajax_nopriv_sort_settings', array( 'Conversations_Ajax_Calls', 'sort_settings' ) );
add_action( 'wp_ajax_sort_settings', array( 'Conversations_Ajax_Calls', 'sort_settings' ) );

