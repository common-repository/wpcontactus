<?php

/*
 Plugin Name: WPContactUs
 Plugin URI: http://www.wpcontactus.com
 Description: WPContactUs is enables one-on-one personalized conversation with users who submit a form on your website so you can view and respond to inquiries easily.
 Version: 1.2.1
 Author: Programming Minds Inc.
 Author URI: http://www.wpcontactus.com
 Text Domain: wpcontactus
 License: GPLv2 or later
 Copyright 2017 Programming Minds Inc.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once dirname( __FILE__ ) . '/classes/conversations-forms.php';
require_once dirname( __FILE__ ) . '/classes/conversations-ajax-calls.php';
require_once dirname( __FILE__ ) . '/classes/conversations-post-calls.php';
require_once dirname( __FILE__ ) . '/classes/conversations-data.php';
require_once dirname( __FILE__ ) . '/classes/conversations-login.php';
require_once dirname( __FILE__ ) . '/classes/conversations-login-view.php';
require_once dirname( __FILE__ ) . '/classes/conversations-view-responders.php';
require_once dirname( __FILE__ ) . '/classes/conversations-view-end-users.php';
require_once dirname( __FILE__ ) . '/classes/conversations-view.php';

require_once dirname( __FILE__ ) . '/forms/forms-caldera.php';
require_once dirname( __FILE__ ) . '/forms/forms-contact7.php';
require_once dirname( __FILE__ ) . '/forms/forms-ninja.php';
require_once dirname( __FILE__ ) . '/forms/forms-wpforms.php';
require_once dirname( __FILE__ ) . '/forms/forms-gravity.php';

/**
 * This is the WordPress user role for an end user.
 *
 * @var    string
 */
const CONVERSATIONS_END_USER_ROLE = 'conversations-end-users';

/**
 * This is the WordPress user role for the persons who respond to the end user.
 *
 * @var    string
 */
const CONVERSATIONS_RESPONDER_USER_ROLE = 'conversations-responders';

/**
 * This is a prefix for all the WordPress options used by this plugin.
 *
 * @var    string
 */
const PREFIX = 'conversations_form_settings_';

/**
 * Class Conversations
 */
class Conversations {

	/**
	 * Single instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Ensure single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activate this plugin from the admin panel.
	 */
	public static function conversations_activate() {

		// Add a role for the end users of this site, as far as conversations are concerned.
		add_role( CONVERSATIONS_END_USER_ROLE, __( 'Conversations End Users', 'Conversations End Users' ),
			array(
				'read' => true,
			)
		);

		// Add a role for the person(s) who will be responding to the submitted forms.
		add_role( CONVERSATIONS_RESPONDER_USER_ROLE, __( 'Conversations Responders', 'Conversations Responders' ),
			array(
				'read' => true,
			)
		);

		// Check if the database is up-to-date.
		Conversations_Db::conversations_db_install_or_update();

		// When called the first time, save the set of default general options for this plugin.
		Conversations_Admin_Settings::save_default_settings();

		// When called the first time, save the set of defaults options for a contact-us form.
		Conversations_Admin_Settings_Of_A_Form::save_default_settings();

		// Create the default pages for the 2 logins (end-user & responder) and 2 conversation view pages (end-user & responder).
		Conversations_Admin_Settings::create_default_pages();

	}

	/**
	 * Common function called to uninstall or de-activate plugin to remove plugin data.
	 */
	public static function remove_everything() {
		global $wpdb;

		// Delete all options used by this plugin from the wp_options table.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s",  'conversations_%' ) );   // WPCS: No-cache ok.

		// Remove Conversation Responders.
		// Ensure that the user was created only for the sole purpose of this plugin.
		// That means the user will not have any other roles assigned to it.
		$users = get_users(
			array(
				'role' => CONVERSATIONS_RESPONDER_USER_ROLE,
			)
		);

		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				// Ensure that the user was only created for this plug-in, so it has only one user role.
				if ( 1 === count( $user->roles ) ) {
					wp_delete_user( $user->ID );
				}
			}
		}

		 // Remove Conversation Tables. These tables store the pieces of the conversation.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}convxns_forms" ); // WPCS: Db call is ok, no caching is needed.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}convxns_followups" ); // WPCS: Db call is ok, no caching is needed.

		// Remove the roles used by this plugin.
		remove_role( CONVERSATIONS_END_USER_ROLE );
		remove_role( CONVERSATIONS_RESPONDER_USER_ROLE );
	}

	/**
	 * Call back to deactivate plug-in.
	 */
	public static function conversations_deactivate() {

		// call the method that removes all data.
		self::remove_everything();
	}

	/**
	 * Call back to uninstall the plug-in.
	 */
	public static function conversations_uninstall() {

		// call the method that removes all data.
		self::remove_everything();
	}

	/**
	 * This will hide the WordPress admin bar when the Conversations users are logged in.
	 *
	 * @param bool $show Show or hide.
	 * @return bool
	 */
	function hide_admin_bar( $show ) {
		$user = wp_get_current_user();
		if ( 0 !== $user->ID && ( in_array( CONVERSATIONS_END_USER_ROLE, (array) $user->roles, true ) || in_array( CONVERSATIONS_RESPONDER_USER_ROLE, (array) $user->roles, true ) ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * This prevents the WordPress dashboard from being visible when the Conversations users are logged in and try to go to/dashboard.
	 */
	function block_conversation_users_from_dashboard() {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$user = wp_get_current_user();
			if ( 0 !== $user->ID ) {
				if ( in_array( CONVERSATIONS_END_USER_ROLE, (array) $user->roles, true ) ) {
					wp_redirect( '/' );
					exit;
				} else if ( in_array( CONVERSATIONS_RESPONDER_USER_ROLE, (array) $user->roles, true ) ) {
					wp_redirect( '/' );
					exit;
				}
			}
		}
	}
}

/**
 * Function to perform all necessary initalization.
 */
function conversations_init_plugin() {

	// Activation hook.
	register_activation_hook( __FILE__,
		array(
			'Conversations',
			'conversations_activate',
		)
	);

	// Deactivation hook.
	register_deactivation_hook( __FILE__,
		array(
			'Conversations',
			'conversations_deactivate',
		)
	);

	// Uninstall hook.
	register_uninstall_hook( __FILE__,
		array(
			'Conversations',
			'conversations_uninstall',
		)
	);

	// Create the one and only instance of the Conversations object.
	add_action( 'plugins_loaded',
		array(
			'Conversations',
			'get_instance',
		)
	);

	if ( is_admin() ) {

		// If logged in as the WordPress administrator include additional files.
		// Include the file to handle admin functions.
		require_once dirname( __FILE__ ) . '/admin/admin.php';

		// Include the file to create and update the custom database tables.
		require_once dirname( __FILE__ ) . '/admin/admin-db.php';

		// Include the file that administers the general settings.
		require_once dirname( __FILE__ ) . '/admin/admin-settings.php';

		// Include the file that administers settings of a particular form on your website.
		require_once dirname( __FILE__ ) . '/admin/admin-settings-of-a-form.php';

		// Action to create the one and only administration settings object.
		add_action( 'plugins_loaded',
			array( 'Conversations_Admin', 'get_instance' )
		);

		// Action to create the object that checks and updates the database version.
		add_action( 'plugins_loaded',
			array( 'Conversations_Db', 'conversations_db_install_or_update' )
		);
	}

	// This filter and action will prevent the WordPress admin top bar from showing up when they are logged in.
	$filter_to_remove_admin_bar = 'show_admin_bar';
	add_filter( $filter_to_remove_admin_bar, array( 'Conversations', 'hide_admin_bar' ) );
	add_action( 'init', array( 'Conversations', 'block_conversation_users_from_dashboard' ) );

	// Shortcodes, to display the conversations for the End user and the Responder.
	add_shortcode( 'conversations_view_responders',
		array( 'Conversations_View_Responders', 'conversations_responders_view_func' )
	);
	add_shortcode( 'conversations_view_end_users',
		array( 'Conversations_View_End_Users', 'conversations_end_users_view_func' )
	);

	// A login and logout dialog is provided by this plugin, in case you do not have one already on your site.
	// You can include this in any part of your site.
	add_shortcode( 'conversations_login',
		array( 'Conversations_Login', 'conversations_login_func' )
	);
}

// Invoke all the initializion functions with one call.
conversations_init_plugin();

