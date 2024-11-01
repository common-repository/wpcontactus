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
 * Class Conversations_Login.
 * Sets up the values for the login form
 */
class Conversations_Login {

	/**
	 * Process the shortcode [conversations_login] to generate the login dialog.
	 *
	 * @param array $atts The parameters provided with the short code.
	 * @option string title : The title to use for the login form (e.g. 'Responders Login', 'Users Login')
	 * @option string user_role : Whether you want this to be the login form of the responder ('responder') or end user ('end-user')
	 * @option string redirect_success_uri : Where to get redirected to after a successful login.
	 * @option string redirect_fail_uri : Where to get redirect to after a failed login (perhaps your home page).
	 * @option string ajax : Wether to use ajax or not for the login ('true' or 'false')
	 *
	 * Example of end user login dialog:
	 * [conversations_login user_role="end-user" title="End User's Login"  redirect_failure="/sign-on-end-users-page" redirect_success="/end-users-page-view-page/" ajax="true"]
	 *
	 * Example of responder login dialog:
	 * [conversations_login user_role="responder" title="Responder's Login"  redirect_failure="/sign-on-responders-page" redirect_success="/responders-page-view-page ajax="false"]
	 *
	 * The redirect URI's above are valid URI's of your website.
	 * The success URI will contain the short code [conversations_view_responders] or [conversations_view_end_users] to display the conversations.
	 */
	public static function conversations_login_func( $atts ) {
		global $wp;

		// Pick up the title of the login form from the parameter.
		if ( isset( $atts['title'] ) ) {
			$setup['heading'] = $atts['title'];
		} else {
			$setup['heading'] = '';
		}

		// Pick up the user role of the login form from the parameter.
		switch ( $atts['user_role'] ) {
			case 'responder':
				$setup['user_role'] = CONVERSATIONS_RESPONDER_USER_ROLE;
				break;
			case 'end-user':
				$setup['user_role'] = CONVERSATIONS_END_USER_ROLE;
				break;
			default:
				$setup['user_role'] = CONVERSATIONS_END_USER_ROLE;
				break;
		}

		// The URL to redirect to if login was successful.
		if ( isset( $atts['redirect_success'] ) ) {
			$setup['redirect_success_uri'] = $atts['redirect_success'];
		} else {
			$setup['redirect_success_uri'] = '/';
		}

		// The URL to redirect to if login failed.
		if ( isset( $atts['redirect_failure'] ) ) {
			$setup['redirect_fail_uri'] = $atts['redirect_failure'];
		} else {
			$setup['redirect_fail_uri'] = '/';
		}

		// Whether to use ajax to login or not.
		if ( isset( $atts['ajax'] ) ) {
			$setup['ajax'] = $atts['ajax'];
		} else {
			$setup['ajax'] = 'false';
		}

		// Build the login dialog in classes/conversations-login-view.php.
		do_action( 'conversations_login_view', $setup );
	}
}


