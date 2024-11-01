// Inserts the users current date and time into the form so conversations can be shown in the current local time.
jQuery( function () {
	var today = new Date();
	var date = today.getFullYear() + '-' + ( today.getMonth() + 1 ) + '-' + today.getDate();
	var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
	var dateTime = date + ' ' + time;
	jQuery( "#id_local_dt" ).val( dateTime );
} );

// Login using ajax, the html used is login-ajax.html.
function convx_login_ajax() {
	jQuery.ajax( {
		url: convx_ajax_request.ajax_url,
		type: 'post',
		data: {
			action: 'login_verify_ajax',
			user_email: jQuery( "#id_user_email" ).val(),
			user_password: jQuery( "#id_user_password" ).val(),
			user_role: jQuery( "#id_user_role" ).val(),
			local_dt: jQuery( "#id_local_dt" ).val(),
			redirect_success_uri: jQuery( "#id_redirect_success_uri" ).val(),
			redirect_fail_uri: jQuery( "#id_redirect_fail_uri" ).val(),
			nonce: convx_ajax_request.nonce
		},
		error: function ( response ) {
			return false;
		},
		success: function ( response ) {
			location.replace( response );
			return false;
		}
	} );

	return false;
}
