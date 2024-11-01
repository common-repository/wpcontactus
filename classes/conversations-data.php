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
 * Class Conversations_Data.
 * The get_data member function of this class retrieves all the conversation and follow up data
 * from the database, and organizes it multi-dimentional array.
 */
class Conversations_Data {

	/**
	 * Array to hold options saved in the database in the Admin panel.
	 * For each type (i.e. category) of form on your website (e.g. Contact Us, Free Quote, etc.)
	 * you will have saved options for it, indicating its category, and how to process it.
	 *
	 * @var      array
	 */
	protected static $form_options;

	/**
	 * The current category of the form being processed.
	 *
	 * @var      string
	 */
	protected static $form_category;

	/**
	 * Gathers all the data for all conversations and organizes it in a multi-dimensional array.
	 *
	 * @param array $setup has info about the current user role, and other general settings.
	 *
	 * @param array $all_convxns this is the array that will finally hold all the organized conversations data.
	 */
	public static function get_data( $setup, &$all_convxns ) {
		global $wpdb;

		// We need the admin-post.php URL so that we can post changes such as sort order change etc, to it.
		$post_form_url = get_admin_url() . 'admin-post.php';

		$convxns_order_by_viewed = '';
		$convxns_order_by_id = '';

		// $setup holds info on how we want to sort the conversations (ASC or DESC) and
		// also whether we want to bunch all the unread conversations on top (true or false).
		$convxns_order_by_id_option = get_option( 'conversations_convxns_sort_method_' . $setup['logged_in_user_info']->ID, 'not_found' );
		if ( 'not_found' === $convxns_order_by_id_option ) {
			$convxns_order_by_id_option = 'desc';
		}

		switch ( $convxns_order_by_id_option ) {
			case 'asc':
				$convxns_order_by_id = ' id ASC ';
				break;
			case 'desc':
				$convxns_order_by_id = ' id DESC ';
				break;
		}

		$convxns_order_by_viewed_option = get_option( 'conversations_convxns_unread_on_top_' . $setup['logged_in_user_info']->ID, 'not_found' );
		if ( 'not_found' === $convxns_order_by_viewed_option ) {
			$convxns_order_by_viewed_option = 'false';
		}

		// We need the ID's of all responders. These will be needed to decide whether to display the message elements as 'mine' or 'his-or-hers'.
		// Since there can be more than one responder in a conversation, we need to know whether the message element was that of *any* responder or the end user.
		$responder_user_ids = array();

		$responder_users = get_users(
			array(
				'role' => CONVERSATIONS_RESPONDER_USER_ROLE,
			)
		);

		if ( ! empty( $responder_users ) ) {
			foreach ( $responder_users as $responder_user ) {
				$responder_user_ids[] = $responder_user->ID;
			}
		}

		// Depending on the current user's role we order them based on the _r (responder) and _eu (end user) table column names.
		if ( CONVERSATIONS_RESPONDER_USER_ROLE === $setup['user_role'] ) {
			switch ( $convxns_order_by_viewed_option ) {
				case 'true':
					$convxns_order_by_viewed = ' convx_viewedby_r ASC, ';
					break;
				case 'false':
					$convxns_order_by_viewed = '';
					break;
			}
			$sql = "SELECT * from {$wpdb->prefix}convxns_forms order by " . $convxns_order_by_viewed . $convxns_order_by_id;
		} else if ( CONVERSATIONS_END_USER_ROLE === $setup['user_role'] ) {
			switch ( $convxns_order_by_viewed_option ) {
				case 'true':
					$convxns_order_by_viewed = ' convx_viewedby_eu ASC, ';
					break;
				case 'false':
					$convxns_order_by_viewed = '';
					break;
			}
			$sql = "SELECT * FROM  {$wpdb->prefix}convxns_forms where convx_author = {$setup['logged_in_user_info']->ID} order by " . $convxns_order_by_viewed . $convxns_order_by_id;
		} else {
			exit;
		}

		// The database stores the GMT time of the conversation. Based on the current time of the user, add or subtract
		// the time difference, so the user sees conversations in his / her the current local time.
		$server_client_time_diff = get_option( 'conversations_timediff_' . $setup['logged_in_user_info']->ID );
		$server_client_time_diff_str = intval( $server_client_time_diff ) . ' minutes';

		$all_convxns = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK. No-cache ok.

		// There are two loops here. One to get the conversation forms that were orignally filled and submitted on the site.
		// The second inner loop is to get the follow ups for each conversation (the text that is sent using the Reply button).
		$count = count( $all_convxns );
		for ( $i = 0; $i < $count; $i++ ) {

			$start = $all_convxns[ $i ]->convx_inserted;
			$all_convxns[ $i ]->convx_inserted_date = date( 'D M j, Y',strtotime( $server_client_time_diff_str, strtotime( $start ) ) );
			$all_convxns[ $i ]->convx_inserted_time = date( 'g:i a',strtotime( $server_client_time_diff_str, strtotime( $start ) ) );

			$user_info = get_user_by( 'id', $all_convxns[ $i ]->convx_author );

			$all_convxns[ $i ]->author_email = $user_info->user_email;
			$all_convxns[ $i ]->author_first_name = $user_info->first_name;
			$all_convxns[ $i ]->author_last_name = $user_info->last_name;

			$all_convxns[ $i ]->convx_user_info = maybe_unserialize( $all_convxns[ $i ]->convx_user_info );
			$all_convxns[ $i ]->convx_data_string = maybe_unserialize( $all_convxns[ $i ]->convx_data_string );
			$all_convxns[ $i ]->convx_data_structured = maybe_unserialize( $all_convxns[ $i ]->convx_data_structured );

			self::$form_category = $all_convxns[ $i ]->convx_data_structured['conversations_form_category'];
			self::$form_options = get_option( PREFIX . self::$form_category );
			$all_convxns[ $i ]->show_form_to_user = self::$form_options['conversations_show_form_to_user']['value'];

			// The follow up messages for each form are stored in a separate table.
			$all_convxns[ $i ]->follow_up_messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}convxns_followups where convx_id = %d order by id asc", "{$all_convxns[ $i ]->id}" ) ); // WPCS: No-cache ok.

			$this_follow_up_messages = $all_convxns[ $i ]->follow_up_messages;
			$count_follow_up_messages = count( $this_follow_up_messages );
			for ( $j = 0; $j < $count_follow_up_messages; $j++ ) {
				$start = $this_follow_up_messages[ $j ]->fup_inserted;
				$this_follow_up_messages[ $j ]->fup_inserted_date = date( 'M, j, Y', strtotime( $server_client_time_diff_str, strtotime( $start ) ) );
				$this_follow_up_messages[ $j ]->fup_inserted_time = date( 'g:i A', strtotime( $server_client_time_diff_str, strtotime( $start ) ) );

				if ( CONVERSATIONS_RESPONDER_USER_ROLE === $setup['user_role'] ) {
					if ( in_array( intval( $this_follow_up_messages[ $j ]->fup_author ), $responder_user_ids, true ) ) {
						// I am logged in as a responder, and this message has been sent by a responder (any responder).
						$this_follow_up_messages[ $j ]->mine = 1;
					} else {
						// I am logged in as an end user, and this message has not been sent by a responder (so by me).
						$this_follow_up_messages[ $j ]->mine = 0;
					}
				} else if ( CONVERSATIONS_END_USER_ROLE === $setup['user_role'] ) {
					if ( in_array( intval( $this_follow_up_messages[ $j ]->fup_author ), $responder_user_ids, true ) ) {
						// I am logged in as an end user, and this message has been sent by a responder (any responder).
						$this_follow_up_messages[ $j ]->mine = 0;
					} else {
						// I am logged in as an end user, and this message has not been sent by a responder (so by me).
						$this_follow_up_messages[ $j ]->mine = 1;
					}
				}
			}

			$follow_up_form_creation_data = new stdclass();
			$follow_up_form_creation_data->post_form_url = $post_form_url;

			$follow_up_form_creation_data->this_convx_id = $all_convxns[ $i ]->id;
			$follow_up_form_creation_data->logged_in_user_id = $setup['logged_in_user_info']->ID;

			$follow_up_form_creation_data->wp_action = $setup['reply_action_value'];

			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$follow_up_form_creation_data->redirect_to = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			}

			$all_convxns[ $i ]->follow_up_form_data = $follow_up_form_creation_data;
		}
	}
}
