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
 * Class Conversations_Admin_Settings_Of_A_Form.
 * Handle Settings of a particular type (i.e. category) of a website form.
 * e.g. a 'contact us' form, a 'get a quote' form, a 'get a proposal' form, etc.
 */
class Conversations_Admin_Settings_Of_A_Form {

	/**
	 * The full screen html output.
	 *
	 * @var string
	 */
	protected static $html;

	/**
	 * The html part of a single form.
	 *
	 * @var string
	 */
	protected static $html_of_a_form;

	/**
	 * No of fields to exclude in the conversation, from the form inputs, if the exclude fields option is chosen.
	 */
	const DEF_EXCLUDE_FIELDS_COUNT = 10;

	/**
	 * No of fields to include in the conversation, from the form inputs, if the include fields option is chosen.
	 */
	const DEF_INCLUDE_FIELDS_COUNT = 10;

	/**
	 * A default form is created during installation as an example. This is the assigned category of that form.
	 */
	const DEF_INSTALLED_FORM_CATEGORY = 'contact_us';

	/**
	 * All options stored in the wp_options table are assigned this prefix.
	 */
	const PREFIX = 'conversations_form_settings_';

	/**
	 * Various options for the processing of a form.
	 *
	 * @var array
	 */
	public static $form_options = array(

		// Form Fields.
		'conversations_first_name' =>
			array(
				'type' => 'text',
				'value' => '',
			),

		'conversations_last_name' =>
			array(
				'type' => 'text',
				'value' => '',
			),

		'conversations_email' =>
			array(
				'type' => 'text',
				'value' => '',
			),

		'conversations_form_category' =>
			array(
				'type' => 'text',
				'value' => '',
			),

		// Form Email Options (checkboxes).
		'conversations_email_resp_staff' =>
			array(
				'type' => 'checkbox',
				'value' => 'checked',
			),

		'conversations_email_enduser' =>
			array(
				'type' => 'checkbox',
				'value' => 'checked',
			),

		// End User sees Form Info: 'yes' or 'no'.
		'conversations_show_form_to_user' =>
			array(
				'type' => 'radio',
				'value' => 'yes',
				'values' =>
				array(
					'conversations_show_form_to_user_yes' => 'yes',
					'conversations_show_form_to_user_no' => 'no',
				),
			),

		// Fields Picked method: 'all' or 'chosen'.
		'conversations_field_pick_method' =>
			array(
				'type' => 'radio',
				'value' => 'all',
				'values'  =>
				array(
					'conversations_field_pick_method_all' => 'all',
					'conversations_field_pick_method_chosen' => 'chosen',
				),
			),

		'conversations_form_fields_exclude' =>
			array(
				'type' => 'array',
				'value' => array( '', '', '', '', '', '', '', '', '', '' ),
			),

		'conversations_form_fields_include' =>
			array(
				'type' => 'array',
				'value' => array( '', '', '', '', '', '', '', '', '', '' ),
			),

		'conversations_field_count' =>
			array(
				'type' => 'text',
				'value' => '10',
			),
	);

	/**
	 * Various options for the processing of a form.
	 *
	 * @var string $current_form_category The user defined category the form belongs to.
	 */
	public static $current_form_category;

	/**
	 * Summary of save_default_settings
	 *
	 * @param string $form_category The user defined category the form belongs to.
	 */
	public static function save_default_settings( $form_category = self::DEF_INSTALLED_FORM_CATEGORY ) {

		self::$form_options['conversations_form_category']['value'] = $form_category;
		update_option( self::PREFIX . $form_category, self::$form_options );
	}

	/**
	 * Get the option values for a form category.
	 *
	 * @param string $form_category The user defined category the form belongs to.
	 */
	function get_saved_values( $form_category ) {

		self::$current_form_category = $form_category;
		self::$form_options = (array) get_option( self::PREFIX . $form_category );
	}

	/**
	 * Save new values.
	 *
	 * @param string $form_category The user defined category the form belongs to.
	 */
	function update_posted_values( $form_category ) {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
			return;
		}

		foreach ( self::$form_options as $form_options_key => $form_options_value ) {

			if ( 'text' === $form_options_value['type'] ) {

				if ( isset( $_POST[ $form_options_key ] ) ) {
					self::$form_options[ $form_options_key ]['value'] = strtolower( sanitize_text_field( wp_unslash( $_POST[ $form_options_key ] ) ) );
				}
			} else if ( 'checkbox' === $form_options_value['type'] ) {

				if ( isset( $_POST[ $form_options_key ] ) ) {
					$val = strlen( sanitize_text_field( wp_unslash( $_POST[ $form_options_key ] ) ) );
					if ( strlen( $val ) > 0 ) {
						self::$form_options[ $form_options_key ]['value'] = 'checked';
					}
				} else {
					self::$form_options[ $form_options_key ]['value'] = '';
				}
			} else if ( 'radio' === $form_options_value['type'] ) {

				if ( isset( $_POST[ $form_options_key ] ) ) {
					$val = sanitize_text_field( wp_unslash( $_POST[ $form_options_key ] ) );
					if ( strlen( $val ) > 0 ) {
						self::$form_options[ $form_options_key ]['value'] = $val;
					}
				}
			} else if ( 'array' === $form_options_value['type'] ) {

				if ( isset( $_POST['conversations_field_count'] ) ) {
					$count1 = count( self::$form_options[ $form_options_key ]['value'] );

					$count = intval( $_POST['conversations_field_count'] );
					for ( $i = 0; $i < $count; $i++ ) {
						$post_var = $form_options_key . '_' . $i;
						if ( isset( $_POST[ $post_var ] ) ) {
							$option_posted = strtolower( sanitize_text_field( wp_unslash( $_POST[ $post_var ] ) ) );
							self::$form_options[ $form_options_key ]['value'][ $i ] = $option_posted;
						} else {
							self::$form_options[ $form_options_key ]['value'][ $i ] = '';
						}
					}

					$count2 = count( self::$form_options[ $form_options_key ]['value'] );
				}
			}
		}

		self::$form_options['conversations_form_category']['value'] = $form_category;

		update_option( self::PREFIX . $form_category, self::$form_options );
	}

	/**
	 * Replace template strings to with actual values to create html to output.
	 */
	function insert_values_in_html() {

		$this_forms_html = self::$html_of_a_form;
		$this_forms_html = str_replace( '{{conversations_form_category_title}}', self::$current_form_category, $this_forms_html );

		if ( '' !== self::$form_options['conversations_form_category']['value'] ) {
			foreach ( self::$form_options as $form_options_key => $form_options_value ) {

				if ( 'text' === $form_options_value['type'] ) {
					$this_forms_html = str_replace( "{{{$form_options_key}}}", esc_attr( $form_options_value['value'] ), $this_forms_html );
				} else if ( 'checkbox' === $form_options_value['type'] ) {
					if ( strlen( $form_options_value['value'] ) > 0 ) {
						$this_forms_html = str_replace( "{{{$form_options_key}}}", 'checked', $this_forms_html );
					} else {
						$this_forms_html = str_replace( "{{{$form_options_key}}}", '', $this_forms_html );
					}
				} else if ( 'radio' === $form_options_value['type'] ) {
					$str = array_search( $form_options_value['value'], $form_options_value['values'], true );
					foreach ( $form_options_value['values'] as $key => $value ) {
						if ( $str === $key ) {
							$this_forms_html = str_replace( "{{{$key}}}", 'checked', $this_forms_html );
						} else {
							$this_forms_html = str_replace( "{{{$key}}}", '', $this_forms_html );
						}
					}
				} else if ( 'array' === $form_options_value['type'] ) {
					$block = '';
					$i = 0;
					$no = sprintf( '%02d', $i + 1 );

					$count1 = count( self::$form_options[ $form_options_key ]['value'] );

					foreach ( $form_options_value['value'] as $field ) {
						$block .= "{$no}. <input type='text' name='{$form_options_key}_{$i}' size='25' value='{$field}'/><br />";
						$i++;
						$no = sprintf( '%02d', $i + 1 );
					}
					$this_forms_html = str_replace( "{{{$form_options_key}}}", $block, $this_forms_html );
				}
			}
		} else {
			// All forms were deleted.
			$this_forms_html = '<br /><br /><br /><h2>No forms defined! Add settings for a new form.<h2>';
		}

		self::$html_of_a_form  = $this_forms_html;
	}

	/**
	 * To select the first category name in the drop down.
	 */
	function get_first_form_category_name() {
		global $wpdb;

		$forms_records = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}options where option_name like '%s' order by option_name", PREFIX . '%' ) ); // WPCS: unprepared SQL OK. No-cache ok.

		if ( count( $forms_records ) > 0 ) {
			return str_replace( PREFIX, '', $forms_records[0]->option_name );
		} else {
			return '';
		}
	}

	/**
	 * Gets the custom form categories created by admin.
	 *
	 * @param string $selected_form_category The category selected from the dropdown.
	 */
	function get_saved_form_categories( $selected_form_category ) {
		global $wpdb;

		$forms_records = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}options where option_name like '%s' order by option_name", PREFIX . '%' ) ); // WPCS: unprepared SQL OK. No-cache ok.

		$select_form_categories = '';
		$marked = false;

		$count = count( $forms_records );
		for ( $i = 0; $i < $count; $i++ ) {
			$this_cat = str_replace( PREFIX, '', $forms_records[ $i ]->option_name );

			$selected = '';

			if ( ! $marked ) {
				if ( $this_cat === $selected_form_category || '' === $selected_form_category ) {
					$selected = ' selected ';
					$marked = true;
				}
			}

			$select_form_categories .= '<option' . $selected . '>' . $this_cat . '</option>';
		}

		return $select_form_categories;
	}

	/**
	 * Process the action to take: save, update, delete, display.
	 */
	function conv_settings_of_a_form_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		$message_saved = '';

		if ( isset( $_POST['add_new_form_settings'] ) ) {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
				return;
			}

			if ( 'Y' === $_POST['add_new_form_settings'] && isset( $_POST['new_form_category'] ) ) {

				// Add settings for a new form button (settings-of-all-forms.html).
				self::save_default_settings( sanitize_text_field( wp_unslash( $_POST['new_form_category'] ) ) );
				self::get_saved_values( sanitize_text_field( wp_unslash( $_POST['new_form_category'] ) ) );
			}

			$selected_form_category = sanitize_text_field( wp_unslash( $_POST['new_form_category'] ) );

		} else if ( isset( $_POST['save_form_settings'] ) ) {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
				return;
			}

			if ( 'Y' === $_POST['save_form_settings'] && isset( $_POST['form_category'] ) ) {

				// Save settings button (settings-of-a-form.html).
				self::update_posted_values( sanitize_text_field( wp_unslash( $_POST['form_category'] ) ) );
				self::get_saved_values( sanitize_text_field( wp_unslash( $_POST['form_category'] ) ) );

				$selected_form_category = sanitize_text_field( wp_unslash( $_POST['form_category'] ) );
				$message_saved = 'Settings Saved';
			}
		} else if ( isset( $_POST['show_form_settings'] ) ) {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
				return;
			}

			if ( 'Y' === $_POST['show_form_settings'] && isset( $_POST['select_form_category'] ) ) {

				// Value changed in drop down (settings-fo-all-forms.html).
				self::get_saved_values( sanitize_text_field( wp_unslash( $_POST['select_form_category'] ) ) );
				$selected_form_category = sanitize_text_field( wp_unslash( $_POST['select_form_category'] ) );
			}
		} else if ( isset( $_POST['delete_form_settings'] ) ) {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'convxnonce' ) ) {
				return;
			}

			if ( 'Y' === $_POST['delete_form_settings'] && isset( $_POST['form_category'] ) ) {

				delete_option( self::PREFIX . sanitize_text_field( wp_unslash( $_POST['form_category'] ) ) );

				$selected_form_category = self::get_first_form_category_name();

				if ( '' === $selected_form_category ) {
					self::$form_options['conversations_form_category']['value'] = '';
				} else {
					self::get_saved_values( $selected_form_category );
				}
			}
		} else {

			// Come here from by default to display.
			$selected_form_category = self::get_first_form_category_name();
			if ( '' === $selected_form_category ) {
				self::$form_options['conversations_form_category']['value'] = '';
			} else {
				self::get_saved_values( $selected_form_category );
			}
		}

		WP_Filesystem();

		global $wp_filesystem;

		self::$html = $wp_filesystem->get_contents( plugin_dir_path( __FILE__ ) . '/settings-of-all-forms.html' );
		self::$html_of_a_form = $wp_filesystem->get_contents( plugin_dir_path( __FILE__ ) . '/settings-of-a-form.html' );

		self::$html_of_a_form = str_replace( '{{message_saved}}', $message_saved, self::$html_of_a_form );
		self::insert_values_in_html();

		$select_form_categories = self::get_saved_form_categories( $selected_form_category );

		self::$html = str_replace( '{{select_form_categories}}', $select_form_categories, self::$html );
		self::$html = str_replace( '{{settings_of_a_form}}', self::$html_of_a_form, self::$html );
		self::$html = str_replace( '{{form_category}}', $selected_form_category, self::$html );

		// This will replace {{nonce}} in all forms.
		self::$html = str_replace( '{{nonce}}', wp_create_nonce( 'convxnonce' ), self::$html );

		echo self::$html;  // WPCS: XSS OK.
	}
}

