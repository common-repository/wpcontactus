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
 * Class Conversation_Ninja_Forms.
 * Process a Ninja form submission.
 */
class Conversation_Ninja_Forms {
	/**
	/**
	 * Single instance of object
	 *
	 * @var object
	 */

	protected static $instance = null;
	/**
	 * Prefix of the form options variable in the wp_options table
	 *
	 * @const string
	 */
	const PREFIX = 'conversations_form_settings_';

	/**
	 * Array form processing options are retrieved here
	 *
	 * @var $form_options
	 */
	protected static $form_options;

	/**
	 * The category of the form being processed
	 *
	 * @var $form_category string
	 */
	protected static $form_category;

	/**
	 * Create a single one and only instance of this object.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook into form submission.
	 */
	public static function init() {
		// Use the ninja_forms_after_submission hook.
		add_action( 'ninja_forms_after_submission', array( __CLASS__, 'conversation_record_it_ninja' ), 1, 1 );
	}

	/**
	 * Extract info from the form that is to be start the conversation.
	 *
	 * @param array $form_data from hook specification.
	 */
	public static function conversation_record_it_ninja( $form_data ) {

		$found_category = false;

		$user_args = array();
		$conversation = array();

		foreach ( $form_data['fields'] as $field_id => $field_ary ) {
			if ( 'conversations_form_category' === $field_ary['label'] ) {
				self::$form_category = $field_ary['value'];
				$found_category = true;
				break;
			}
		}

		if ( ! $found_category ) {
			$conversation['err'] = 'A hidden field conversations_form_category must be present in the form to indicate its category (e.g. contact us, get a quote, etc.)';
			do_action( 'conversation_start', $user_args, $conversation );
			return;
		}

		self::$form_options = get_option( self::PREFIX . self::$form_category );
		if ( false === self::$form_options ) {
			$temp_var = self::$form_category;
			$conversation['err'] = "The settings for this form category ({$temp_var}) are not provided. Create the settings in the WordPress admin WPContactUs sub-menu Form Settings";
			do_action( 'conversation_start', $user_args, $conversation );
			return;
		}

		// Category will always be included in $conversation[], regardless of the exclude/include setting.
		$conversation['conversations_form_category'] = self::$form_category;
		$user_args = array();

		// Here we are looking to pick up the Email address, First Name and Last Name fields from the form.
		foreach ( $form_data['fields'] as $field_id => $field_ary ) {
			$label = $field_ary['label'];
			$key = $field_ary['key'];
			$lkey = strtolower( $key );

			$value  = $field_ary['value'];

			switch ( $lkey ) {
				case self::$form_options['conversations_first_name']['value']:
					$user_args['user_first_name'] = $value;
					break;

				case self::$form_options['conversations_last_name']['value']:
					$user_args['user_last_name'] = $value;
					break;

				case self::$form_options['conversations_email']['value']:
					// This field is Required, to identify a current user or a new user.
					$user_args['user_email'] = $value;
					break;

				default:
					switch ( self::$form_options['conversations_field_pick_method']['value'] ) {
						case 'all':
							// Pick all fields for the conversation, except the ones specified.
							// The field selection method is configured in WordPress admin sub-menu 'Conversations > Settings'.
							$do_not_include = false;
							foreach ( self::$form_options['conversations_form_fields_exclude']['value'] as $dni_field ) {
								if ( '' !== $dni_field && 1 === preg_match( "/$dni_field/", $lkey ) ) {
									$do_not_include = true;
									break;
								}
							}

							if ( ! $do_not_include && isset( $value ) ) {
								$conversation[ $label ] = $value;
							}
							break;
						case 'chosen':
							// Pick only specified fields for the conversation.
							// The field selection method is configured in WordPress admin sub-menu 'Conversations > Settings'.
							$include_it = false;

							foreach ( self::$form_options['conversations_form_fields_include']['value'] as $inc_field ) {
								if ( '' !== $inc_field && 1 === preg_match( "/$inc_field/", $lkey ) ) {
									$include_it = true;
									break;
								}
							}

							if ( true === $include_it && isset( $value ) ) {
								$conversation[ $label ] = $value;
							}
							break;
					}
			}
		}

		// Now that $user_args (user info) and $conversation (conversation info) are ready, record the conversation.
		do_action( 'conversation_start', $user_args, $conversation );
	}
}
