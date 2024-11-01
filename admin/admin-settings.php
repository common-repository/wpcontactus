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
 * Class Conversations_Admin_Settings.
 * Handle Admin Settings page (general settings).
 */
class Conversations_Admin_Settings {

	/**
	 * The screen html output.
	 *
	 * @var string
	 */
	protected static $html;

	/**
	 * Number of responder logins to create during plugin activation.
	 */
	const DEF_RESPONDERS_COUNT = 1;

	/**
	 * Various general options
	 *
	 * @var array
	 */
	protected static $option_fields = array(

		// Default sort order.
		'conversations_convxns_sort_method' =>
			array(
				'type' => 'text',
				'default' => 'desc',
				'var' => '',
			),

		// Whether to keep the unread conversations on top.
		'conversations_convxns_unread_on_top' =>
			array(
				'type' => 'text',
				'default' => 'false',
				'var' => '',
			),

		// The default folder of the template html files.
		'conversations_template_folder' =>
			array(
				'type' => 'text',
				'default' => 'templates/simple-html5/',
				'var' => '',
			),

		// (Slug #1) Slug of page where end users can log in.
		'conversations_end_users_login_page_slug' =>
			array(
				'type' => 'text',
				'default' => '/wpcontactus-sign-on-end-users',
				'var' => '',
			),

		// (Slug #2) Slug of page where responders (i.e. people in your company who will respond to the form submits), can log in.
		'conversations_responders_login_page_slug' =>
			array(
				'type' => 'text',
				'default' => '/wpcontactus-sign-on-responders',
				'var' => '',
			),

		// (Slug #3) Slug of page where end users can view their conversations.
		'conversations_end_users_view_page_slug' =>
			array(
				'type' => 'text',
				'default' => '/wpcontactus-end-users-conversations',
				'var' => '',
			),

		// (Slug #4) Slug of page where responders (i.e. people in your company who will respond to the form submits), can view their conversations.
		'conversations_responders_view_page_slug' =>
			array(
				'type' => 'text',
				'default' => '/wpcontactus-responders-conversations',
				'var' => '',
			),

		// The From email address in the format name <email-address>. A valid address should be provided, to ensure deliverability.
		'conversations_email_from' =>
			array(
				'type' => 'text',
				'default' => '',
				'var' => '',
			),
	);

	/**
	 * Save the default settings during plugin installation.
	 */
	public static function save_default_settings() {

		self::$option_fields['conversations_email_from']['default'] = 'noreply <noreply@' . str_replace( 'https://', '', str_replace( 'http://', '', get_site_url() ) ) . '>';

		// All the options saved can be viewed using this SQL.
		// SELECT * FROM wp_options where option_name like 'conversations%' order by option_id asc.
		foreach ( self::$option_fields as $option_field_key => $option_field_value ) {

			update_option( $option_field_key, $option_field_value['default'] );
		}

		for ( $i = 1; $i <= self::DEF_RESPONDERS_COUNT; $i++ ) {
			$temp_password = strval( rand( 10000, 99999 ) );

			$admin_email = get_bloginfo( 'admin_email' );
			$domain = substr( $admin_email, 1 + strpos( $admin_email, '@' ) );

			// The new responders get a default 'email' and password for their account.
			// Change these in the WordPress admin panel to your real email addresses and new passwords.
			$userdata  = array(
				'user_login'                => 'responder' . $i,
				'user_email'                => 'responder' . $i . '@' . $domain,
				'first_name'                => 'fname' . $i,
				'last_name'                 => 'lname' . $i,
				'user_pass'                 => $temp_password,
				'role'                      => CONVERSATIONS_RESPONDER_USER_ROLE,
			);

			$user_id = wp_insert_user( $userdata );

			update_option( 'convx_temp_password_responder_' . $i,  $temp_password );
		}
	}

	/**
	 * Get the values of all the options from the database into an array.
	 */
	function get_saved_values() {

		foreach ( self::$option_fields as $option_field_key => $option_field_value ) {
			self::$option_fields[ $option_field_key ]['var'] = get_option( $option_field_key );
		}
	}

	/**
	 * Update the database with the new settings.
	 */
	function update_posted_values() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		if ( ! isset( $_POST['conversations_template_folder'] ) ) {
			return;
		}

		$folder = sanitize_text_field( wp_unslash( $_POST['conversations_template_folder'] ) );

		if ( '/' !== substr( $folder, -1 ) && '\\' !== substr( $folder, -1 ) ) {
			$_POST['conversations_template_folder'] .= '/';
		}

		foreach ( self::$option_fields as $option_field => $option_field_props ) {
			if ( isset( $_POST[ $option_field ] ) ) {
				if ( 'conversations_email_from' === $option_field ) {
					$tmp = str_replace( '[', '<', sanitize_text_field( str_replace( '<', '[', $_POST[ $option_field ] ) ) );
					update_option( $option_field, $tmp );
				} else {
					update_option( $option_field, sanitize_text_field( wp_unslash( $_POST[ $option_field ] ) ) );
				}
			}
		}
	}

	/**
	 * Generates the html for the section where the responder's logins are displayed.
	 *
	 * Returns the html string generated.
	 *
	 * @return string
	 */
	function create_responders_block() {
		$conversations_responders_block = '';
		$row = '
						<tr>
							<td>
                                {{field_login}}
							</td>
							<td>
                                {{field_display_name}}
							</td>
							<td>
                                {{field_email}}
							</td>
							<td>
                                {{field_password}}
							</td>

						</tr>';

		$responder_users = get_users(
			array(
				'role' => CONVERSATIONS_RESPONDER_USER_ROLE,
			)
		);

		$i = 1;
		foreach ( $responder_users as $user ) {

			$this_row = $row;
			$this_row = str_replace( '{{field_login}}', $user->data->user_login, $this_row );
			$this_row = str_replace( '{{field_display_name}}', $user->data->display_name, $this_row );
			$this_row = str_replace( '{{field_email}}', $user->data->user_email, $this_row );
			$this_row = str_replace( '{{field_password}}', get_option( 'convx_temp_password_responder_' . $i ), $this_row );
			$conversations_responders_block .= $this_row;
			$i++;
		}

		return $conversations_responders_block;
	}

	/**
	 * Substitute strings in the template html with actual values.
	 */
	function insert_values_in_html() {
		$text = self::$html;
		$text = str_replace( '{{site_url}}', get_site_url(), $text );

		foreach ( self::$option_fields as $option_field => $option_field_props ) {
			if ( 'text' === $option_field_props['type'] ) {
				$text = str_replace( "{{{$option_field}}}", esc_attr( $option_field_props['var'] ), $text );
			} else if ( 'radio' === $option_field_props['type'] ) {
				$str = array_search( $option_field_props['var'], $option_field_props['values'], true );
				foreach ( $option_field_props['values'] as $key => $value ) {
					if ( $str === $key ) {
						$text = str_replace( "{{{$key}}}", 'checked', $text );
					} else {
						$text = str_replace( "{{{$key}}}", '', $text );
					}
				}
			}
		}

		$text = str_replace( '{{nonce}}', wp_create_nonce( 'convxnonce' ), $text );

		self::$html = $text;
	}

	/**
	 * Generates the html for the settings page from the template file.
	 */
	function conv_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		$message_saved = '';

		if ( isset( $_POST['update_settings'] ) ) {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
				return;
			}

			self::update_posted_values();
			self::get_saved_values();

			$message_saved = 'Settings Saved';
		} else {
			self::get_saved_values();
		}

		WP_Filesystem();

		global $wp_filesystem;

		self::$html = $wp_filesystem->get_contents( plugin_dir_path( __FILE__ ) . '/settings-settings.html' );
		self::insert_values_in_html();

		self::$html = str_replace( '{{message_saved}}', $message_saved, self::$html );

		echo self::$html;  // WPCS: XSS OK.
	}

	/**
	 * Display the plugin 'home' page when WPContactUs is clicked in the left side Admin menu.
	 */
	function conv_home_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		WP_Filesystem();

		global $wp_filesystem;

		self::$html = $wp_filesystem->get_contents( plugin_dir_path( __FILE__ ) . '/settings-home.html' );

		self::$html = str_replace( '{{responders_block}}', self::create_responders_block(), self::$html );

		echo self::$html;  // WPCS: XSS OK.
	}

	/**
	 * Create the default pages for the 2 logins (end-user & responder) and 2 conversation view pages (end-user & responder).
	 */
	function create_default_pages() {
		$pages = array (
			'wpcontactus-sign-on-end-users'        => '[conversations_login user_role="end-user"  title="End User Login"   redirect_failure="/wpcontactus-sign-on-end-users"  redirect_success="/wpcontactus-end-users-conversations/"  ajax="false"]',
			'wpcontactus-sign-on-responders'       => '[conversations_login user_role="responder" title="Responders Login" redirect_failure="/wpcontactus-sign-on-responders" redirect_success="/wpcontactus-responders-conversations/" ajax="false"]',
			'wpcontactus-end-users-conversations'  => '[conversations_view_end_users]',
			'wpcontactus-responders-conversations' => '[conversations_view_responders]',
			);

		foreach ( $pages as $title => $content ) {
			$page_data = array(
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => 1,
				'post_name'      => $title,
				'post_title'     => $title,
				'post_content'   => $content,
				'post_parent'    => 0,
				'comment_status' => 'closed'
			);
			$page_id = wp_insert_post( $page_data );
		}
	}
}

