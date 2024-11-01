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
 * Javascript functions for the WordPress Admin screen.
 */
function load_new_form_settings() {
	// To display / hide loading in progress wheel.
	jQuery( "#id_loader_settings" ).css( "display", "inline" );
}

function validate_add_settings_for_a_new_form_input() {
	// Submit button hit in settings-of-all-forms.html in form where name is top_common_section.
	// Add settings for a new form.
	form_name = jQuery( "#id_new_form_category" ).val();
	form_name = form_name.trim();
	form_name = form_name.replace( / /g, '_' );
	form_name = form_name.match( /[A-Za-z0-9_ -]+/ );

	if (!form_name) {
		alert( "Provide a category name for your form that identifies its purpose\r\ne.g. 'Contact Us', 'Get a Quote', 'Request Proposal',\r\n'Request A Quote', 'Support Request', etc.)" );
		return false;
	}

	if ( form_name.length > 25) {
		alert( "Provide a shorter name, less than 25 characters" );
		return false;
	}
	
	ret = confirm( 'Create settings for this type of form?\r\n' + form_name);
	if ( ret == true ) {
		jQuery( "#id_new_form_category" ).val( form_name );
		return true;
	}
	else {
		return false;
	}
}