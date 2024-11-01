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
 * Class Conversations_View_Page.
 * Displays the conversations page for both responders and end users.
 */
class Conversations_View_Page {

	/**
	 * Implements the action 'conversations_plugin_users_view', that displays the conversations list.
	 *
	 * @param array $setup Options for the display.
	 * @option object logged_in_user_info Contains the current users info.
	 * @option string user_role Contains whether the user is a Responder or an End User.
	 *
	 * @param array $all_convxns This is the nested array produced in Conversations_Data that holds all the conversations and follow ups.
	 */
	function view_page( $setup, $all_convxns ) {

		// Get the template files.
		$plugin_dir_path = dirname( __FILE__ ) . '/../';
		$folder = get_option( 'conversations_template_folder' );

		$template_conversation_single   = '';
		$template_conversation_group    = '';

		$template_conversation_page     = '';
		$template_conversation_delete   = '';

		$template_response_form         = '';

		$template_his_or_hers           = '';
		$template_mine                  = '';

		$template_settings              = '';

		$template_conversation_single   = @file_get_contents( $plugin_dir_path . $folder . 'conversation-single.html' );
		$template_conversation_group    = @file_get_contents( $plugin_dir_path . $folder . 'conversation-group.html' );

		$template_conversation_page     = @file_get_contents( $plugin_dir_path . $folder . 'conversation-page.html' );
		$template_conversation_delete   = @file_get_contents( $plugin_dir_path . $folder . 'conversation-delete.html' );

		$template_response_form         = @file_get_contents( $plugin_dir_path . $folder . 'conversation-response-form.html' );

		$template_his_or_hers           = @file_get_contents( $plugin_dir_path . $folder . 'conversation-element-his-or-hers.html' );
		$template_mine                  = @file_get_contents( $plugin_dir_path . $folder . 'conversation-element-mine.html' );

		$template_settings              = @file_get_contents( $plugin_dir_path . $folder . 'conversation-settings.html' );

		if ( false === $template_conversation_single || false === $template_conversation_group ||
			false === $template_conversation_page || false === $template_conversation_delete ||
			false === $template_response_form || false === $template_his_or_hers ||
			false === $template_mine || false === $template_settings ) {

			echo 'Missing template file!';  // WPCS: XSS OK.
			return;
		}

		// Start replacing strings in the template files.
		$convnxs_group = '';

		$this_settings = $template_settings;
		$this_settings = str_replace( '{{images_folder}}', plugin_dir_url( __FILE__ ) . '../' . $folder . 'images/', $this_settings );
		$this_settings = str_replace( '{{logged_in_user_id}}', $setup['logged_in_user_info']->ID, $this_settings );

		$template_conversation_single = str_replace( '{{images_folder}}', plugin_dir_url( __FILE__ ) . $folder . 'images/', $template_conversation_single );
		$template_his_or_hers = str_replace( '{{images_folder}}', plugin_dir_url( __FILE__ ) . $folder . 'images/', $template_his_or_hers );
		$template_mine = str_replace( '{{images_folder}}', plugin_dir_url( __FILE__ ) . $folder . 'images/', $template_mine );

		$class_viewed_template_str = '';
		$class_viewed = '';

		$class_not_viewed_template_str = '';
		$class_not_viewed = '';

		// Remove the first part of the string leaving the actual class name there.
		if ( 1 === preg_match( '/{{class_viewed=[a-z\-}]*/', $template_conversation_single, $matches ) ) {
			$class_viewed_template_str = $matches[0];
			$class_viewed = str_replace( '}}', '', str_replace( '{{class_viewed=', '', $matches[0] ) );
		}

		if ( 1 === preg_match( '/{{class_not_viewed=[a-z\-}]*/', $template_conversation_single, $matches ) ) {
			$class_not_viewed_template_str = $matches[0];
			$class_not_viewed = str_replace( '}}', '', str_replace( '{{class_not_viewed=', '', $matches[0] ) );
		}

		$count = count( $all_convxns );
		for ( $ci = 0; $ci < $count; $ci++ ) {

			// Main loop: for all conversations.
			$this_conv = $template_conversation_single;

			$largest_follow_up_seen_by_other_party = 0;

			$convx_form_viewedby_r = 0;

			switch ( $setup['user_role'] ) {
				case CONVERSATIONS_RESPONDER_USER_ROLE:
					if ( 0 === intval( $all_convxns[ $ci ]->convx_viewedby_r ) ) {
						$class_viewed_or_not = $class_not_viewed;
						$class_viewed_or_not_template_str_to_replace = $class_not_viewed_template_str;
						$class_template_str_to_remove = $class_viewed_template_str;
					} else {
						$class_viewed_or_not = $class_viewed;
						$class_viewed_or_not_template_str_to_replace = $class_viewed_template_str;
						$class_template_str_to_remove = $class_not_viewed_template_str;
					}
					$largest_follow_up_seen_by_other_party = $all_convxns[ $ci ]->convx_last_fup_id_viewedby_eu;

					break;

				case CONVERSATIONS_END_USER_ROLE:
					if ( 0 === intval( $all_convxns[ $ci ]->convx_viewedby_eu ) ) {
						$class_viewed_or_not = $class_not_viewed;
						$class_viewed_or_not_template_str_to_replace = $class_not_viewed_template_str;
						$class_template_str_to_remove = $class_viewed_template_str;
					} else {
						$class_viewed_or_not = $class_viewed;
						$class_viewed_or_not_template_str_to_replace = $class_viewed_template_str;
						$class_template_str_to_remove = $class_not_viewed_template_str;
					}
					$largest_follow_up_seen_by_other_party = $all_convxns[ $ci ]->convx_last_fup_id_viewedby_r;

					$convx_form_viewedby_r = $all_convxns[ $ci ]->convx_form_viewedby_r;

					break;

				default:
					die();
			}

			$this_conv = str_replace( $class_viewed_or_not_template_str_to_replace, $class_viewed_or_not, $this_conv );
			$this_conv = str_replace( $class_template_str_to_remove, '', $this_conv );

			$this_conv = str_replace( '{{user_role}}', "'" . $setup['user_role'] . "'", $this_conv );

			$this_conv = str_replace( '{{convx_title}}', $all_convxns[ $ci ]->convx_title, $this_conv );
			$this_conv = str_replace( '{{this_convx_id}}', $all_convxns[ $ci ]->id, $this_conv );

			$this_conv = str_replace( '{{convx_start_date}}', $all_convxns[ $ci ]->convx_inserted_date, $this_conv );
			$this_conv = str_replace( '{{convx_start_time}}', $all_convxns[ $ci ]->convx_inserted_time, $this_conv );

			if ( CONVERSATIONS_RESPONDER_USER_ROLE === $setup['user_role'] ) {
				$this_conversation_delete = $template_conversation_delete;
				$this_conversation_delete = str_replace( '{{this_convx_id}}', $all_convxns[ $ci ]->id, $this_conversation_delete );
				$this_conversation_delete = str_replace( '{{user_role}}', "'" . $setup['user_role'] . "'", $this_conversation_delete );

				$this_conv = str_replace( '{{convx_delete}}', $this_conversation_delete, $this_conv );
			} else {
				$this_conv = str_replace( '{{convx_delete}}', '', $this_conv );
			}

			$convx_form_data = '';
			foreach ( $all_convxns[ $ci ]->convx_data_structured as $key => $value ) {
				if ( 'conversations_form_category' === $key ) {
					$key = 'Form';
				}

				if ( is_array( $value ) ) {
					$convx_form_data .= $key . ': ' . implode( ', ', $value ) . '<br>';
				} else {
					$convx_form_data .= $key . ': ' . $value . '<br>';
				}
			}

			if ( CONVERSATIONS_RESPONDER_USER_ROLE === $setup['user_role'] ) {
				$this_conv = str_replace( '{{convx_form_data}}', $convx_form_data, $this_conv );
				$this_conv = str_replace( '{{form-viewed}}', 'none', $this_conv );
			} else if ( CONVERSATIONS_END_USER_ROLE === $setup['user_role'] ) {
				if ( 'yes' === $all_convxns[ $ci ]->show_form_to_user ) {
					$this_conv = str_replace( '{{convx_form_data}}', $convx_form_data, $this_conv );
				} else {
					$this_conv = str_replace( '{{convx_form_data}}', 'Form submitted', $this_conv );
				}

				if ( 0 === intval( $convx_form_viewedby_r ) ) {
					$this_conv = str_replace( '{{form-viewed}}', 'none', $this_conv );
				} else {
					$this_conv = str_replace( '{{form-viewed}}', 'inline', $this_conv );
				}
			} else {
				exit;
			}

			$follow_ups = '';
			$prev_ones_date = '';

			$largest_follow_id_up_i_see = 0;

			$this_follow_up_messages = $all_convxns[ $ci ]->follow_up_messages;
			$count_follow_ups = count( $this_follow_up_messages );

			for ( $j = 0; $j < $count_follow_ups; $j++ ) {

				// Inner loop: For all follow ups of this conversation.
				$this_ones_date = $this_follow_up_messages[ $j ]->fup_inserted_date;
				$this_ones_time = $this_follow_up_messages[ $j ]->fup_inserted_time;

				if ( $this_ones_date === $prev_ones_date ) {
					$insert_new_date = '';
				} else {
					$insert_new_date = $this_ones_date;
				}
				$prev_ones_date = $this_ones_date;

				if ( 1 === intval( $this_follow_up_messages[ $j ]->mine ) ) {
					// Messages I sent are shown on the right.
					$this_text = $template_mine;

					$this_text = str_replace( '{{convx_text}}', $this_follow_up_messages[ $j ]->fup_text, $this_text );

					if ( $largest_follow_up_seen_by_other_party >= $this_follow_up_messages[ $j ]->id ) {
						$this_text = str_replace( '{{follow_up_viewed}}', 'inline', $this_text );
					} else {
						$this_text = str_replace( '{{follow_up_viewed}}', 'none', $this_text );
					}
				} else {
					// Messages received are shown on the left.
					$this_text = $template_his_or_hers;

					$this_text = str_replace( '{{convx_text}}', $this_follow_up_messages[ $j ]->fup_text, $this_text );

					$this_text = str_replace( '{{follow_up_viewed}}', 'none', $this_text );
				}

				$this_text = str_replace( '{{convx_date}}', $insert_new_date, $this_text );
				$this_text = str_replace( '{{convx_time}}', $this_ones_time, $this_text );

				$follow_ups .= $this_text;

				if ( $this_follow_up_messages[ $j ]->id > $largest_follow_id_up_i_see ) {
					$largest_follow_id_up_i_see = $this_follow_up_messages[ $j ]->id;
				}
			}

			// Create an insertion point for new dynamically sent messages by me.
			$follow_ups .= "<div style='display:none' id='id_insertion_point_next_message_{$all_convxns[ $ci ]->id}'></div>";

			$this_conv = str_replace( '{{follow_ups}}', $follow_ups, $this_conv );

			$this_conv = str_replace( '{{largest_follow_up_id_i_see}}', $largest_follow_id_up_i_see, $this_conv );

			if ( 0 === intval( $all_convxns[ $ci ]->convx_err ) ) {
				$this_conv = str_replace( '{{display-footer}}', 'inline', $this_conv );
			} else {
				$this_conv = str_replace( '{{display-footer}}', 'none', $this_conv );
			}

			$this_follow_up_form_data = $all_convxns[ $ci ]->follow_up_form_data;

			$this_form = $template_response_form;
			foreach ( $this_follow_up_form_data as $name_field => $value ) {
				$this_form = str_replace( "{{{$name_field}}}", $value, $this_form );
			}

			if ( CONVERSATIONS_RESPONDER_USER_ROLE === $setup['user_role'] ) {
				$this_form = str_replace( 'wp_action', 'responders_comments', $this_form );
			} else {
				$this_form = str_replace( 'wp_action', 'end_users_comments', $this_form );
			}

			$this_conv = str_replace( '{{response_form}}', $this_form, $this_conv );

			$convnxs_group .= $this_conv;
		}

		$conversation_group = str_replace( '{{conversation_group}}', $convnxs_group, $template_conversation_group );

		$conversation_page = str_replace( '{{conversation_page}}', $conversation_group, $template_conversation_page );

		$conversation_page = str_replace( '{{class_used_for_viewed_convxn}}', $class_viewed, $conversation_page );
		$conversation_page = str_replace( '{{class_used_for_unviewed_convxn}}', $class_not_viewed, $conversation_page );

		$conversation_page = str_replace( '{{settings}}', $this_settings, $conversation_page );

		$conversation_page = str_replace( '{{first_name}}', $setup['logged_in_user_info']->first_name, $conversation_page );
		$conversation_page = str_replace( '{{last_name}}', $setup['logged_in_user_info']->last_name, $conversation_page );
		$conversation_page = str_replace( '{{user_email}}', $setup['logged_in_user_info']->user_email, $conversation_page );

		// Store the template used to display my text message, it so it can be used to insert my new messages dynamically.
		$conversation_page = str_replace( '{{conversation_text_template}}', "<div id='id_template_mine' style='display:none'>{$template_mine}</div>", $conversation_page );

		$conversation_page = apply_filters( 'conversations_view_filter', $conversation_page, $setup, $all_convxns );

		echo $conversation_page;  // WPCS: XSS OK.
	}

	/**
	 * Enqueue styles and scripts
	 */
	function ajax_interaction_enqueue_scripts() {
		$folder = get_option( 'conversations_template_folder' );

		wp_enqueue_style( 'convx_css_handle', plugins_url( '../' . $folder . 'css/style.css', __FILE__ ), array(), '1', false );

		wp_enqueue_script( 'convx_ajax_handle', plugins_url( '../' . $folder . 'js/conversation-view-page.js', __FILE__ ), array(), '1', true );
		wp_localize_script( 'convx_ajax_handle', 'convx_ajax_request',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'convxnonce' ),
			)
		);
	}

	/**
	 * Actions to enqueue the scripts, create the page to display from the template.
	 */
	public static function add_actions() {
		add_action( 'wp_enqueue_scripts', array( 'Conversations_View_Page', 'ajax_interaction_enqueue_scripts' ) );

		add_action( 'conversations_plugin_users_view', array( 'Conversations_View_Page', 'view_page' ), 10, 3 );
	}
}

// Perform actions related to viewing of the Conversations page by the end user or the responder.
Conversations_View_Page::add_actions();

