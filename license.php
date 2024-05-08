<?php
function mob_license_menu() {
    add_plugins_page( 'kfz-web Lizenz', 'kfz-web Lizenz', 'manage_options', KFZ_WEB_LICENSE_PAGE, 'mob_license_page' );
}
add_action('admin_menu', 'mob_license_menu');

function mob_license_page() {
    $license  = get_option( 'mob_license_key' );
    $status   = get_option( 'mob_license_status' );
    ?>
     <div class="wrap">
        <h2><?php _e('kfz-web Plugin Lizenz Einstellungen'); ?></h2>
        <?php if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) { ?>
            <div class="<?php echo ( $_GET['sl_activation'] == 'true' ? 'updated' : 'error' ); ?>">
                <p><?php echo $_GET['message']; ?></p>
            </div>
        <?php } ?>
        <form method="post" action="options.php">
            <?php settings_fields('mob_license'); ?>
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row" valign="top">
                            <?php _e('Lizenzschlüssel'); ?>
                        </th>
                        <td>
                            <input id="mob_license_key" name="mob_license_key" type="text" class="regular-text" value="<?php echo esc_attr( $license ); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" valign="top">
                            <?php _e('Lizenzstatus'); ?>
                        </th>
                        <td>
                            <?php if( $status !== false && $status == 'valid' ) { ?>
                                <span style="color:green;"><?php _e('active'); ?></span>
                                <?php wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
                                <input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
                            <?php } else {
                                wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
                                <input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License'); ?>"/>
                            <?php } ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function edd_sample_register_option() {
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

function edd_sample_activate_license() {
    if( isset( $_POST['edd_license_activate'] ) ) {
        if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
            return; // get out if we didn't click the Activate button

			$license = trim( get_option( 'mob_license_key' ) );
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => KFZ_WEB_ITEM_NAME,
				'item_id'	 => KFZ_WEB_ITEM_ID,
				'url'        => home_url()
			);

        $response = wp_remote_post( KFZ_WEB_STORE, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            if ( false === $license_data->success ) {
                switch( $license_data->error ) {
                    case 'expired' :
                        $message = sprintf(
                            __( 'Your license key expired on %s.' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        break;
                    case 'revoked' :
                        $message = __( 'Ihr Lizenzschlüssel wurde deaktiviert.' );
                        break;
                    case 'missing' :
                        $message = __( 'Ungültige Lizenz.' );
                        break;
                    case 'invalid' :
						$message = __( 'Lizenzschlüssel stimmt nicht überein.' );
                    case 'site_inactive' :
                        $message = __( 'Ihre Lizenz ist für diese URL nicht aktiv.' );
                        break;
					case 'invalid_item_id' :
						$message = __( 'Ungültige Artikel-ID' );
                    case 'item_name_mismatch' :
                        $message = sprintf( __( 'Ungültiger Lizenzschlüssel für %s.' ), KFZ_WEB_ITEM_NAME );
                        break;
                    case 'no_activations_left':
                        $message = __( 'Ihr Lizenzschlüssel hat sein Aktivierungslimit erreicht.' );
                        break;
                    default :
                        $message = __( 'Es ist ein Fehler aufgetreten, bitte versuchen Sie es erneut.' );
                        break;
                }
            }
        }

        // Check if anything passed on a message constituting a failure
        if ( ! empty( $message ) ) {
            $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), admin_url( 'plugins.php?page=' . KFZ_WEB_LICENSE_PAGE ) );
            wp_redirect( $redirect );
            exit();
        }

        update_option( 'mob_license_status', $license_data->license );
        $redirect = add_query_arg( array( 'sl_activation' => 'true', 'message' => urlencode( __( 'Lizenz erfolgreich aktiviert.' ) ) ), admin_url( 'plugins.php?page=' . KFZ_WEB_LICENSE_PAGE ) );
        wp_redirect( $redirect );
        exit();
    }
}
add_action('admin_init', 'edd_sample_activate_license');

function edd_sample_deactivate_license() {
    if( isset( $_POST['edd_license_deactivate'] ) ) {
        if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
            return; // get out if we didn't click the Deactivate button

        $license = trim( get_option( 'mob_license_key' ) );
        $api_params = array(
            'edd_action'=> 'deactivate_license',
            'license'   => $license,
            'item_name' => KFZ_WEB_ITEM_NAME // the name of our product in EDD
        );

        $response = wp_remote_post( KFZ_WEB_STORE, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            if ( $license_data->license == 'deactivated' ) {
                delete_option( 'mob_license_status' );
                $message = __( 'Die Lizenz wurde erfolgreich deaktiviert.' );
            }
        }

		if ( ! empty( $message ) ) {
            $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), admin_url( 'plugins.php?page=' . KFZ_WEB_LICENSE_PAGE ) );
            wp_redirect( $redirect );
            exit();
        }

        delete_option( 'mob_license_status' );
        $redirect = add_query_arg( array( 'sl_activation' => 'true', 'message' => urlencode( __( 'Die Lizenz wurde erfolgreich deaktiviert.' ) ) ), admin_url( 'plugins.php?page=' . KFZ_WEB_LICENSE_PAGE ) );
        wp_redirect( $redirect );
        exit();
    }
}
add_action('admin_init', 'edd_sample_deactivate_license');


function mob_check_license_status() {
    $license = trim( get_option( 'mob_license_key' ) );
    $api_params = array(
        'edd_action' => 'check_license',
        'license'    => $license,
        'item_name'  => KFZ_WEB_ITEM_NAME,
        'url'        => home_url()
    );

    $response = wp_remote_post( KFZ_WEB_STORE, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
    update_option( 'mob_license_status', $license_data->license );
}

function mob_admin_notices() {
    $status = get_option( 'mob_license_status' );

    if ( $status !== 'valid' ) {
        echo '<div class="error"><p>' . __( 'Deine kfz-web Plugin Lizenz ist nicht aktiv. Bitte überprüfe deine Lizenz in den <a href="' . admin_url( 'plugins.php?page=' . KFZ_WEB_LICENSE_PAGE ) . '">Plugin-Einstellungen</a>.' ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'mob_admin_notices' );
