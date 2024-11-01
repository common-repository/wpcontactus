/**
 * Display / Hide the conversation which was clicked upon.
 */
function convx_show( convx_id ) {
	cur_value = jQuery( "#panel_no_" + convx_id ).css( 'display' );
	if ( "block" === cur_value.toLowerCase() ) {
		jQuery( "#panel_no_" + convx_id ).css( 'display', 'none' );
	}
	else if ( "none" === cur_value.toLowerCase() ) {
		jQuery( "#panel_no_" + convx_id ).css( 'display', 'block' );
	}
}

/**
 * Display / Hide the follow up (i.e. reply) form.
 */
function show_reply_form( convx_id ) {
	cur_value = jQuery( "#response_to_convx_id_" + convx_id ).css( 'display' );
	if ( "block" === cur_value.toLowerCase() ) {
		jQuery( "#response_to_convx_id_" + convx_id ).css( 'display', 'none' );
	}
	else if ( "none" === cur_value.toLowerCase() ) {
		jQuery( "#response_to_convx_id_" + convx_id ).css( 'display', 'block' );
	}

}

/**
 * Triggered when the conversation's header line is clicked on. 
 * Records that fact that the conversation has now been viewed by the user. 
 * Processed in conversations-ajax-calls.php: function convx_viewed().
 */
function convx_viewed( convx_id, largest_follow_up_id_i_see, user_role ) {

	jQuery( "#id_currently_viewing_convx" ).val( convx_id );

	class_used_for_unviewed_convxn = jQuery( "#id_class_used_for_unviewed_convxn" ).val();

	has_not_been_viewed = jQuery( "#convx_single_" + convx_id ).hasClass( class_used_for_unviewed_convxn );

	if ( has_not_been_viewed ) {
		class_used_for_viewed_convxn = jQuery( "#id_class_used_for_viewed_convxn" ).val();
		jQuery( "#convx_single_" + convx_id ).addClass( class_used_for_viewed_convxn ).removeClass( class_used_for_unviewed_convxn );

		largest_follow_up_id_i_see = jQuery( "#id_largest_follow_up_id_i_see_convx_" + convx_id ).val();

		jQuery.ajax( {
			url: convx_ajax_request.ajax_url,
			type: 'post',
			data: {
				action: 'convx_viewed',
				convx_id: convx_id,
				largest_follow_up_id_i_see: largest_follow_up_id_i_see,
				user_role: user_role,
				nonce: convx_ajax_request.nonce
			},
			success: function ( response ) {
			}
		} )
	}
}

/**
 * Delete a conversation and all its follow ups.
 * Processed in conversations-ajax-calls.php: function del_convx().
 */
function convx_delete( convx_id, user_role ) {

	ret = confirm( "Are you sure?" );
	if ( ret == false ) {
		return;
	}

	jQuery.ajax( {
		url: convx_ajax_request.ajax_url,
		type: 'post',
		data: {
			action: 'del_convx',
			convx_id: convx_id,
			user_role: user_role,
			nonce: convx_ajax_request.nonce
		},
		success: function ( response ) {
		}
	} );

	jQuery( "#convx_single_" + convx_id ).remove();

	return false;
}

/**
 * Triggered when the Send button is hit for the reply to the conversation. 
 * The action to take for the ajax call is provided in conversations-view-end-users.php or conversations-view-responders.php,
 * depending on which user role is logged in.
 * The action is either 'end-users-comments' or 'responders_comments' which are the triggered functions in conversations-ajax-calls.php. 
 */
function convx_reply( convx_id, logged_in_user_id, wp_action ) {

	user_message = jQuery( "#id_user_message_" + convx_id ).val();

	if ( user_message.length == 0 ) {
		return false;
	}

	template_mine = jQuery( "#id_template_mine" ).html();

	new_message = template_mine;
	new_message = new_message.replace( "{{convx_text}}", user_message.replace( /\n/g, '<br />' ) );
	new_message = new_message.replace( "{{follow_up_viewed}}", "none" );
	new_message = new_message.replace( "{{convx_date}}", "Today" );
	new_message = new_message.replace( "{{convx_time}}", "Now" );

	jQuery( "#id_insertion_point_next_message_" + convx_id ).before( new_message );

	jQuery( "#response_to_convx_id_" + convx_id ).css( 'display', 'none' );
	jQuery( "#id_user_message_" + convx_id ).val( '' );

	jQuery( "#panel_no_" + convx_id ).css( 'display', 'none' );

	jQuery.ajax( {
		url: convx_ajax_request.ajax_url,
		type: 'post',
		data: {
			action: wp_action,
			convx_id: convx_id,
			logged_in_user_id: logged_in_user_id,
			user_message: user_message,
			nonce: convx_ajax_request.nonce
		},
		success: function ( response ) {

		}
	} );

	return false;
}

/**
 * Triggred when the sorting icons are clicked on. There are 4 values for 'setting'.
 * These are processed in conversations-ajax-calls.php: function sort_settings().
 * 1: sort conversations in ascending date order.
 * 2: sort conversations in decending date order.
 * 3: always move unread conversations to the top.
 * 4. do not move unread conversations to the top.
 */
function convx_sort_settings( setting, logged_in_user_id ) {
	jQuery( "#id_loader_settings" ).css( "display", "inline" );

	jQuery.ajax( {
		url: convx_ajax_request.ajax_url,
		type: 'post',
		data: {
			action: 'sort_settings',
			user_id: logged_in_user_id,
			setting: setting,
			nonce: convx_ajax_request.nonce
		},
		success: function ( response ) {
			location.reload();
		}
	} );

}

function refresh_screen() {
	jQuery( "#id_loader_settings" ).css( "display", "inline" );
	location.reload();
}


