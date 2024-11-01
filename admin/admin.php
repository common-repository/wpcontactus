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
 * Class Conversations_Admin.
 * Handle Admin screen menus
 */
class Conversations_Admin {
	/**
	 * Single instance of this class.
	 *
	 * @var      object $instance
	 */
	protected static $instance = null;

	/**
	 * Create one and only object.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Action for the hook is set in the constructor.
	 */
	function __construct() {
		add_action( 'admin_menu', array( 'Conversations_Admin', 'add_admin_menu' ) );
	}

	/**
	 * Add the menu items in the WordPress left panel
	 */
	public static function add_admin_menu() {

		add_menu_page( 'Home', 'WPContactUs', 'manage_options', 'conversations-home',
			array(
				'Conversations_Admin_Settings',
				'conv_home_page',
			),
			'dashicons-randomize',
			59
		);

		add_submenu_page( 'conversations-home', 'Integration', 'Integration', 'manage_options', 'conversations-home',
			array(
				'Conversations_Admin_Settings',
				'conv_home_page',
			)
		);

		add_submenu_page( 'conversations-home', 'Settings', 'Settings', 'manage_options', 'conversations-settings',
			array(
				'Conversations_Admin_Settings',
				'conv_settings_page',
			)
		);

		add_submenu_page( 'conversations-home', 'Form Settings', 'Form Settings', 'manage_options', 'conversations-settings-of-a-form',
			array(
				'Conversations_Admin_Settings_Of_A_Form',
				'conv_settings_of_a_form_page',
			)
		);

		wp_enqueue_style( 'convx_admin_css_handle', plugins_url( 'css/style.css', __FILE__ ), array(), '1', false );

		wp_enqueue_script( 'convx_admin_js_handle', plugins_url( 'js/settings.js', __FILE__ ), array(), '1', true );

	}

}

