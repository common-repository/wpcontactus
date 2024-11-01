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
 * Class Conversations_Forms.
 * Handles creation of the conversation when the form is first submitted on the site.
 */
class Conversations_Forms {

	/**
	 * This is the low value of a random number used for a random password.
	 *
	 * @var    integer
	 */
	const LOW_RAND = 10 ** 8;

	/**
	 * This is the high value of a random number used for a random password.
	 *
	 * @var    integer
	 */
	const HIGH_RAND = 10 ** 13;

	/**
	 * Options for a particular form read from the wp_options table.
	 *
	 * @var    array Holds the category of the form and the options for processing the form.
	 */
	protected static $form_options;

	/**
	 * Initalize the objects for processing different kinds of forms.
	 */
	public static function init_installed_form_packages() {
		/**
		 * Init each form package.
		 */
		Conversation_C7_Forms::get_instance()->init();
		Conversation_Caldera_Forms::get_instance()->init();
		Conversation_WPForms_Forms::get_instance()->init();
		Conversation_Ninja_Forms::get_instance()->init();
		Conversation_Gravity_Forms::get_instance()->init();
	}

	/**
	 * This is called when a form is submitted through a hook provided by the 3rd party form packages.
	 * It saves the form info which is the start of the conversation, and creates a user if one does not
	 * exist already, based on the email address in the form.
	 *
	 * If you are not using a 3rd party form package, but using your own custom PHP forms,
	 * you can simply call do_action directly: do_action( 'conversation_start', $user_args, $conversation );
	 * To see samples of this call, look at the files in the forms folder.
	 *
	 * @param array $user_args User information to be saved.
	 * @param array $conversation Form fields to be saved.
	 */
	function form_submitted( $user_args, $conversation ) {
		global $wpdb;

		// Debug log, turn on /off when required. Makes entires in the convxns_log database table.
		$log_it = true;

		// First check if any errors were encountered in picking up the info from the form.
		if ( ! empty( $conversation['err'] ) ) {
			Conversations_Forms::record_error( $conversation['err'] );
			return;
		}

		// The input $user_args[] holds user information provided in the form.
		$user_first_name = sanitize_text_field( $user_args['user_first_name'] );
		$user_last_name = sanitize_text_field( $user_args['user_last_name'] );
		$user_email = $user_args['user_email'];

		// The input $conversation[] is the conversation info provided in the form.
		$form_category = $conversation['conversations_form_category'];

		// The information that is to be stored in the database is prepared in these arrays
		// structured user information, and form information.
		$form_user_information = array();
		$form_data_structured = array();
		$form_data_string = '';

		$server_time = current_time( 'mysql' );

		$user_id = -1;
		$convx_title = '';
		$is_new_user = '';

		// Check if the user is an existing WordPress user by comparing the email address.
		$existing_user = get_user_by( 'email', $user_email );
		if ( $existing_user ) {

			// Administor's not allowed to become end users so we are not going to add an end-user role to the administator.
			$admin_email = get_option( 'admin_email' );
			if ( 0 === strcasecmp( $admin_email, $user_email ) ) {
				Conversations_Forms::record_error( "Provide a different email address. Admin's email address is not allowed." );
				return;
			}

			$is_new_user = false;

			// Add the conversations user role to this existing user.
			$existing_user->add_role( CONVERSATIONS_END_USER_ROLE );

			$user_id        = $existing_user->ID;
			$user_login     = $existing_user->user_login;

			$user_password = '';

			// Since the user exists, use existing user info from WordPress.
			if ( ! empty( $existing_user->first_name ) ) {
				$user_first_name = $existing_user->first_name;
			}
			if ( ! empty( $existing_user->last_name ) ) {
				$user_last_name = $existing_user->last_name;
			}

			$form_user_information['is_new_user'] = 'Existing User';
		} else {
			// The user is new to WordPress, create it.
			$is_new_user = true;

			if ( empty( $user_email ) ) {
				// Without a user's contact email address, it will not be possible to communicate to the user. No point in continuing.
				Conversations_Forms::record_error( 'Email address missing from the form. Did you create settings for this form in "Form Settings"? Does the field name of the "End users email address" you entered in the Form Settings, match the field from the form? Does the form category specified in the hidden forms field "conversations_form_category" match the value in the "Form Processing Settings" page dropdown?' );
				return;
			}

			// Provide a random login resembling the email address.
			$base_login = strstr( $user_email, '@', true );
			$user_login = $base_login;

			$count = 0;
			while ( username_exists( $user_login ) && $count++ < 99999 ) {
				$user_login = $base_login . rand( 10000, 99999 );
			}

			$user_password = wp_generate_password( 8, false, false );

			$userdata  = array(
				'user_login'          => $user_login,
				'first_name'          => $user_first_name,
				'last_name'           => $user_last_name,
				'user_email'          => $user_email,
				'user_pass'           => $user_password,
				'role'                => CONVERSATIONS_END_USER_ROLE,
			);

			$user_id = wp_insert_user( $userdata );

			if ( is_wp_error( $user_id ) ) {
				// Could not add a new user.
				Conversations_Forms::record_error( 'Could not add a new user.' );
				return;
			}

			$form_user_information['is_new_user'] = 'New User';
		}

		$form_user_information['user_login']         = $user_login;
		$form_user_information['user_first_name']    = $user_first_name;
		$form_user_information['user_last_name']     = $user_last_name;
		$form_user_information['user_email']         = $user_email;

		// Created structure format of the conversation.
		foreach ( $conversation as $key => $value ) {
			$form_data_structured[ $key ] = $value;
		}

		// Insert Form.
		$convx_title = $user_login . '(' . $user_email . ') ' . $user_first_name . ' ' . $user_last_name;

		foreach ( $form_data_structured as $key => $value ) {
			if ( is_array( $value ) ) {
				$form_data_string .= $key . ': ' . implode( ',', $value ) . '\n';
			} else {
				$form_data_string .= $key . ': ' . $value . '\n';
			}
		}

		$convx = array(
			'convx_title'        => wp_strip_all_tags( $convx_title ),
			'convx_status'       => '0',
			'convx_author'       => $user_id,
			'convx_cat'          => $form_category,

			'convx_user_info'       => maybe_serialize( $form_user_information ),
			'convx_data_string'     => maybe_serialize( $form_data_string ),
			'convx_data_structured' => maybe_serialize( $form_data_structured ),

			'convx_viewedby_eu' => '1',
			'convx_viewedby_r'  => '0',

			'convx_last_fup_id_viewedby_eu' => '0',
			'convx_last_fup_id_viewedby_r' => '0',

			'convx_last_dt_viewedby_eu' => '2000-01-01 00:00:00',
			'convx_last_dt_viewedby_r' => '2000-01-01 00:00:00',

			'convx_updated'     => $server_time,
			'convx_inserted'    => $server_time,

			'convx_err'         => '0',
		);

		$convx_types = array(
			'%s',
			'%d',
			'%s',
			'%s',

			'%s',
			'%s',
			'%s',

			'%d',
			'%d',

			'%d',
			'%d',

			'%s',
			'%s',

			'%s',
			'%s',

			'%d',
		);

		// Store the form info to start the conversation in to a table.
		$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_forms', $convx, $convx_types );
		if ( false === $ret_value ) {
			// Could not add a row to the table.
			Conversations_Forms::record_error( 'Could not add a row to the table' );
			return;
		}

		$convx_id = $wpdb->insert_id;

		// Based on the category of the form, get the options for processing it.
		self::$form_options = get_option( PREFIX . $form_category );

		if ( false === self::$form_options ) {
			// The settings for this form category are not provided.
			// Create the settings in the WordPress admin sub-menu 'Form Settings'.
			Conversations_Forms::record_error( "The Form Settings for your form {$form_category} need to be setup" );
			return;
		}

		// The folder for getting the email template file.
		$plugin_dir_path = dirname( __FILE__ ) . '/../';
		$folder = get_option( 'conversations_template_folder' );

		$email_from = get_option( 'conversations_email_from' );
		if ( empty( $email_from ) ) {
			$email_from = 'noreply <noreply@' . str_replace( 'https://', '', str_replace( 'http://', '', get_site_url() ) );
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $email_from );

		// Send email to the end user: New users MUST get an email, so they can login to continue the conversation.
		if ( $is_new_user || ( 'checked' === self::$form_options['conversations_email_enduser']['value'] && is_email( $user_email ) ) ) {

			$template_email = file_get_contents( $plugin_dir_path . $folder . 'email-end-users.html' );

			if ( false !== $template_email ) {
				$email_text = $template_email;

				$end_user_subject = 'Message from ' . get_bloginfo( false );

				$end_user_email_msg   = $convx_title . '<br><br>';
				$end_user_email_msg  .= '<a href="' . get_option( 'siteurl' ) . '/' . get_option( 'conversations_end_users_login_page_slug' ) . '">Please Login to view the response to your submission</a><br><br>';

				if ( $is_new_user ) {
					// A new User is assigned a password above.
					$end_user_email_msg .= 'The login is your email address and the password is ' . $user_password . '<br><br>';
				} else {
					// User already exists. If the user has forgotten his/her password he/she must do a forgot password to get a new one.
					$end_user_email_msg .= 'The login is your email address.<br><br>';
				}

				// Show the form data to the end user?
				if ( 'yes' === self::$form_options['conversations_show_form_to_user']['value'] ) {
					$end_user_email_msg  .= str_replace( '\n', '<br>', $form_data_string );
				}
				$end_user_email_msg .= '<br><br>';

				$email_text = str_replace( '{{message}}', $end_user_email_msg, $email_text );

				$result = wp_mail( $user_email, $end_user_subject, $email_text, $headers );


				if ( $log_it ) {
					// Log it for debugging purposes.
					if ( $result ) {
						$result_int = 1;
					}
					else {
						$result_int = 0;
					}
					$log_text = 'Email result [' . $result_int . ']<br>' . $user_email . '<br>' . $end_user_subject . '<br>' . implode( '/', $headers ) . '<br>' . $email_text;
					$log = array(
						'code' => '2',
						'text_msg' => $log_text,
						'log_inserted' => $server_time,
					);

					$log_types = array(
						'%d',
						'%s',
						'%s',
					);
					$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_log', $log, $log_types );
				}
			}
		}

		// Send email to the responders, informing them of a new conversation they need to respond to.
		if ( 'checked' === self::$form_options['conversations_email_resp_staff']['value'] ) {

			$template_email   = file_get_contents( $plugin_dir_path . $folder . 'email-responders.html' );
			if ( false !== $template_email ) {
				$email_text = $template_email;

				$responders_subject     = 'New conversation: ' . get_bloginfo( false );

				$responders_email_msg  = $convx_title . '<br><br>';
				$responders_email_msg .= '<a href="' . get_option( 'siteurl' ) . '/' . get_option( 'conversations_responders_login_page_slug' ) . '">Login to respond</a><br><br>';
				$responders_email_msg .= str_replace( '\n', '<br>', $form_data_string );
				$responders_email_msg .= '<br><br>';

				$email_text = str_replace( '{{message}}', $responders_email_msg, $email_text );

				$responder_users = get_users(
					array(
						'role' => CONVERSATIONS_RESPONDER_USER_ROLE,
					)
				);

				// Send the email to each responder.
				foreach ( $responder_users as $user ) {
					if ( is_email( $user->data->user_email ) ) {
						$result = wp_mail( $user->data->user_email, $responders_subject, $email_text, $headers );

						if ( $log_it ) {
							// Log it for debugging purposes.
							if ( $result ) {
								// Success
								$result_int = 1;
							}
							else {
								// Failed
								$result_int = 0;
							}
							$log_text = 'Email result [' . $result_int . ']<br>' . $user->data->user_email . '<br>' . $responders_subject . '<br>' . implode( '/', $headers ) . '<br>' . $email_text;
							$log = array(
								'code' => '2',
								'text_msg' => $log_text,
								'log_inserted' => $server_time,
							);

							$log_types = array(
								'%d',
								'%s',
								'%s',
							);
							$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_log', $log, $log_types );
						}
					}
				}
			}
		}
	}

	/**
	 * Save the error as a conversation (convxns_forms), and also in the log table (convxns_log). Useful during initial setup and debugging.
	 *
	 * @param string $errstr The error string to store in the conversation.
	 */
	function record_error( $errstr ) {
		global $wpdb;

		$server_time = current_time( 'mysql' );

		$form_user_information['user_login']        = '-';
		$form_user_information['user_first_name']   = '-';
		$form_user_information['user_last_name']    = '-';
		$form_user_information['user_email']        = '-';

		$form_data_structured['Error message'] = $errstr;

		$form_data_string = $errstr;

		$convx = array(
			'convx_title'        => 'Error Message',
			'convx_status'       => '0',
			'convx_author'       => '0',
			'convx_cat'          => 'Error Message',

			'convx_user_info'       => maybe_serialize( $form_user_information ),
			'convx_data_string'     => maybe_serialize( $form_data_string ),
			'convx_data_structured' => maybe_serialize( $form_data_structured ),

			'convx_viewedby_eu' => '1',
			'convx_viewedby_r'  => '0',

			'convx_last_fup_id_viewedby_eu' => '0',
			'convx_last_fup_id_viewedby_r' => '0',

			'convx_last_dt_viewedby_eu' => '2000-01-01 00:00:00',
			'convx_last_dt_viewedby_r' => '2000-01-01 00:00:00',

			'convx_updated'     => $server_time,
			'convx_inserted'    => $server_time,

			'convx_err'         => '1',
		);

		$convx_types = array(
			'%s',
			'%d',
			'%s',
			'%s',

			'%s',
			'%s',
			'%s',

			'%d',
			'%d',

			'%d',
			'%d',

			'%s',
			'%s',

			'%s',
			'%s',

			'%d',
		);

		// Store the error form.
		$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_forms', $convx, $convx_types );

		// Also record the error in the convxns_log table.
		$log = array(
			'code' => '1',
			'text_msg' => maybe_serialize( $form_data_string ),
			'log_inserted' => $server_time,
		);

		$log_types = array(
			'%d',
			'%s',
			'%s',
		);
		$ret_value = $wpdb->insert( $wpdb->prefix . 'convxns_log', $log, $log_types );
	}
}

add_action( 'init', array( 'Conversations_Forms', 'init_installed_form_packages' ) );
add_action( 'conversation_start', array( 'Conversations_Forms', 'form_submitted' ), 1, 2 );
