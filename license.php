<?php
/************************************
kfz-web Plugin License
*************************************/
function mob_license_menu() {
	add_plugins_page( 'kfz-web Lizenz', 'kfz-web Lizenz', 'manage_options', 'kfz-web-license', 'mob_license_page' );
}
add_action('admin_menu', 'mob_license_menu');

function mob_license_page() {
	$license 	= get_option( 'mob_license_key' );
	$status 	= get_option( 'mob_license_status' );
	?>
	<div class="wrap">
		<h2><?php _e('kfz-web Plugin Lizenz Einstellungen'); ?></h2>
		<form method="post" action="options.php">
		
			<?php settings_fields('mob_license'); ?>
			<p>Tragen Sie hier den Lizenzschlüssel des kfz-web Wordpress Plugins ein. Dieser wurde Ihnen in Ihrer Bestellbestätigungs E-Mail zugesandt.</p>
			<table class="form-table">
				<tbody>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('Lizenzschlüssel'); ?>
						</th>
						<td>
							<input id="mob_license_key" name="mob_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="mob_license_key"><?php _e('Lizenzschlüssel Eintragen'); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">	
							<th scope="row" valign="top">
								<?php _e('Lizenz Aktivieren'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<?php wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Lizenz Deaktivieren'); ?>"/>
								<?php } else {
									wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Lizenz Aktivieren'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		<?php //mob_license_check(); ?>

		</form>
		
	<?php
}





/************************************
* this illustrates how to check if 
* a license key is still valid
* the updater does this for you,
* so this is only needed if you
* want to do something custom
*************************************/





function edd_sample_register_option() {
	// creates our settings in the options table
	register_setting('mob_license', 'mob_license_key', 'edd_sanitize_license' );
}
add_action('admin_init', 'edd_sample_register_option');

function edd_sanitize_license( $new ) {
	$old = get_option( 'mob_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'mob_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}



/************************************
* this illustrates how to activate 
* a license key
*************************************/

function edd_sample_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_activate'] ) ) {

		// run a quick security check 
	 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) ) 	
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'mob_license_key' ) );
			

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'activate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( KFZ_WEB_ITEM_NAME ) // the name of our product in EDD
		);
		
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, KFZ_WEB_STORE ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// $license_data->license will be either "active" or "inactive"

		update_option( 'mob_license_status', $license_data->license );

	}
}
add_action('admin_init', 'edd_sample_activate_license');


/***********************************************
* Illustrates how to deactivate a license key.
* This will descrease the site count
***********************************************/

function edd_sample_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_deactivate'] ) ) {

		// run a quick security check 
	 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) ) 	
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'mob_license_key' ) );
			

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'deactivate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( KFZ_WEB_ITEM_NAME ) // the name of our product in EDD
		);
		
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, KFZ_WEB_STORE ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'mob_license_status' );

	}
}
add_action('admin_init', 'edd_sample_deactivate_license');