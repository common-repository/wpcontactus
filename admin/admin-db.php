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

/**
 * The database version number for the custom tables.
 *
 * @var    string
 */
global $conversations_db_version;

/**
 * Current numeric value of the version number.
 *
 * @var    string
 */
$conversations_db_version = '1.0.0';

/**
 * Class Conversations_Db
 */
class Conversations_Db {

	/**
	 * Install the custom table during plugin activation, or update them if the version number above has changed.
	 */
	public static function conversations_db_install_or_update() {
		global $wpdb;
		global $conversations_db_version;

		$installed_ver = get_option( 'conversations_db_version' );

		if ( $installed_ver !== $conversations_db_version ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$charset_collate = $wpdb->get_charset_collate();

			// The intial form filled by the user is stored in convxns_forms.
			$table_name = $wpdb->prefix . 'convxns_forms';

			$sql = "CREATE TABLE {$table_name} (
		        id int(11) NOT NULL AUTO_INCREMENT,
                convx_title varchar(255) DEFAULT '',
                convx_status int(11) DEFAULT '0',
                convx_author int(11) DEFAULT '0',
                convx_cat varchar(255) DEFAULT '',
                convx_user_info text DEFAULT '',
                convx_data_string text DEFAULT '',
                convx_data_structured text DEFAULT '',
                convx_form_viewedby_eu int(11) DEFAULT '0',
                convx_form_viewedby_r int(11) DEFAULT '0',
                convx_viewedby_eu int(11) DEFAULT '0',
                convx_viewedby_r int(11) DEFAULT '0',
                convx_last_fup_id_viewedby_eu int(11) DEFAULT '0',
                convx_last_fup_id_viewedby_r int(11) DEFAULT '0',
                convx_last_dt_viewedby_eu datetime DEFAULT '2000-01-01 00:00:00',
                convx_last_dt_viewedby_r datetime DEFAULT '2000-01-01 00:00:00',
		      convx_updated datetime DEFAULT '2000-01-01 00:00:00',
		      convx_inserted datetime DEFAULT '2000-01-01 00:00:00',
                convx_err int(11) DEFAULT '0',
		      PRIMARY KEY  (id)
	        ) {$charset_collate};";

			dbDelta( $sql );

			// Any further parts of the conversation are stored in convxns_followups.
			$table_name = $wpdb->prefix . 'convxns_followups';
			$sql = "CREATE TABLE {$table_name} (
		        id int(11) NOT NULL AUTO_INCREMENT,
                convx_id int(11) DEFAULT '0',
                fup_author int(11) DEFAULT '0',
                fup_text text DEFAULT '',
		        fup_inserted datetime DEFAULT '2000-01-01 00:00:00',
		        PRIMARY KEY  (id)
	        ) {$charset_collate};";

			dbDelta( $sql );

			// A Log table for output of any debugging messages.
			$table_name = $wpdb->prefix . 'convxns_log';
			$sql = "CREATE TABLE {$table_name} (
		        id int(11) NOT NULL AUTO_INCREMENT,
                code int(11) DEFAULT '0',
                text_msg text DEFAULT '',
		        log_inserted datetime DEFAULT '2000-01-01 00:00:00',
		        PRIMARY KEY  (id)
	        ) {$charset_collate};";

			dbDelta( $sql );

			update_option( 'conversations_db_version', $conversations_db_version );
		}
	}
}
