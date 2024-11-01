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
 * Class Conversations_View_End_Users.
 * Prepares arguments in $setup[] to display the screen for end users.
 */
class Conversations_View_End_Users {
	/**
	 * Process the shortcode [conversations_view_end_users] to display the conversations for end users.
	 *
	 * @param mixed $args not used right now.
	 */
	public static function conversations_end_users_view_func( $args = array() ) {
		// Check if the user is logged in.
		if ( is_user_logged_in() ) {
			$logged_in_user_info = get_userdata( wp_get_current_user()->ID );

			// Double check that the user has a responder role.
			if ( in_array( CONVERSATIONS_END_USER_ROLE, $logged_in_user_info->roles, true ) ) {

				$setup['user_role'] = CONVERSATIONS_END_USER_ROLE;
				$setup['logged_in_user_info'] = $logged_in_user_info;

				// Internal: the ajax form action to perform when a follow up (i.e. reply) is sent.
				// This also becomes the name of the function call in conversations-ajax-calls.php.
				$setup['reply_action_value'] = 'end_users_comments';

				// Get all the conversations data.
				$all_convxns = array();
				Conversations_Data::get_data( $setup, $all_convxns );

				// Build the conversations screen in conversations-view.php.
				do_action( 'conversations_plugin_users_view', $setup, $all_convxns );
			}
		}
	}
}
