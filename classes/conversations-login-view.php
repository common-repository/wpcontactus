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
 * Class Conversations_Login_View.
 * This creates the login form from the template file.
 */
class Conversations_Login_View {
	/**
	 * Implements the action 'conversations_login_view' that displays the login dialog.
	 *
	 * @param array $setup values to use to prepare the login form.
	 */
	function conversations_login_view_method( $setup ) {

		// Prepare the login window/dialog from the template.
		// Read the template file from the folder specified in the Admin panel.
		$plugin_dir_path = dirname( __FILE__ ) . '/../';
		$folder = get_option( 'conversations_template_folder' );

		// Use ajax or non-ajax based form template.
		if ( ! empty( $setup['ajax'] ) && 0 === strcasecmp( $setup['ajax'], 'true' ) ) {
			$template_login_page   = @file_get_contents( $plugin_dir_path . $folder . 'login-ajax.html' );
		} else {
			$template_login_page   = @file_get_contents( $plugin_dir_path . $folder . 'login.html' );
		}

		if ( false === $template_login_page ) {
			echo 'Missing template file';
			return;
		}

		$this_login_page = $template_login_page;

		$this_login_page = str_replace( '{{heading}}', $setup['heading'], $this_login_page );

		$this_login_page = str_replace( '{{redirect_success_uri}}', $setup['redirect_success_uri'], $this_login_page );
		$this_login_page = str_replace( '{{redirect_fail_uri}}', $setup['redirect_fail_uri'], $this_login_page );

		$this_login_page = str_replace( '{{user_role}}', $setup['user_role'], $this_login_page );

		$this_login_page = str_replace( '{{nonce}}', wp_create_nonce( 'convxnonce' ), $this_login_page );

		echo $this_login_page;  // WPCS: XSS OK.
	}

	/**
	 * Enqueue styles and scripts
	 */
	function scripts() {
		$folder = get_option( 'conversations_template_folder' );

		wp_enqueue_style( 'convx_login_css_handle', plugins_url( '../' . $folder . 'css/style.css', __FILE__ ), array(), '1', false );

		wp_enqueue_script( 'convx_login_js_handle', plugins_url( '../' . $folder . 'js/login-page.js', __FILE__ ), array(), '1', true );

		wp_localize_script( 'convx_login_js_handle', 'convx_ajax_request',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'convxnonce' ),
			)
		);
	}

	/**
	 * Actions to enqueue the scripts, and create the login form, which is then echoed.
	 */
	public static function add_actions() {
		add_action( 'wp_enqueue_scripts', array( 'Conversations_Login_View', 'scripts' ) );

		add_action( 'conversations_login_view', array( 'Conversations_Login_View', 'conversations_login_view_method' ), 10, 2 );
	}
}

// Perform the actions related to viewing and enabling the custom (optional) login dialogs.
// You may already have a login/logout interface. Please see the additional field required for this plugin.
// in the login dialog, for obtaining the local time and date of the user ('local_dt').
Conversations_Login_View::add_actions();


