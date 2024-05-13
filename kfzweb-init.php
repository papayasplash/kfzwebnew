<?php
/*
Plugin Name: kfz-web Plugin
Plugin URI: http://www.mobilede-fahrzeugintegration.de/
Description: Importieren Sie Ihren Fahrzeugbestand von mobile.de einfach in Ihre Wordpress Seite.
Version: 2.0.5
Author: neusued GmbH
Author URI: www.neusued.de 
License: All rights reserved
Text-Domain: kfz-web
Domain Path: /languages
*/

define( 'KFZ_WEB_STORE', 'https://www.mobilede-fahrzeugintegration.de/' );
define( 'KFZ_WEB_ITEM_NAME', 'kfz-web' );
define( 'KFZ_WEB_ITEM_ID', '22162' );
define( 'KFZ_WEB_LICENSE_PAGE', 'kfz-web-license' );

include_once dirname(__FILE__) . '/mobilede-main.php';
include_once dirname(__FILE__) . '/shortcodes.php';
include_once dirname(__FILE__) . '/admin.php';
include_once dirname(__FILE__) . '/license.php';
if(!class_exists('EDD_SL_Plugin_Updater')) {
    include_once dirname(__FILE__) . '/EDD_SL_Plugin_Updater.php';
}

// retrieve our license key from the DB
$license = trim( get_option( 'mob_license_key' ) );
// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( KFZ_WEB_STORE, __FILE__, array(
	'version' 	=> '2.0.5',		// current version number
	'license' 	=> $license,	// license key (used get_option above to retrieve from DB)
	'item_id'       => KFZ_WEB_ITEM_ID,	// id of this plugin
	'author' 	=> 'neusued GmbH',	// author of this plugin
    'beta'          => false                // set to true if you wish customers to receive update notifications of beta releases
) );
function mob_activate() {
    mob_schedule_license_check();
}
register_activation_hook(__FILE__, 'mob_activate');

function mob_deactivate() {
    wp_clear_scheduled_hook('mob_periodic_event_hook');
    wp_clear_scheduled_hook('mob_daily_license_check');
}
register_deactivation_hook(__FILE__, 'mob_deactivate');

function mob_schedule_license_check() {
    if ( ! wp_next_scheduled( 'mob_daily_license_check' ) ) {
        wp_schedule_event( time(), 'daily', 'mob_daily_license_check' );
    }
}

function mob_daily_license_check() {
    mob_check_license_status();
    $status = get_option( 'mob_license_status' );

    if ( $status !== false && $status == 'valid' ) {
        if (wp_get_schedule('mob_periodic_event_hook') === false){
            wp_schedule_event(time(), 'minutely', 'mob_periodic_event_hook');
        }
    } else {
        wp_clear_scheduled_hook('mob_periodic_event_hook');
    }
}
add_action( 'mob_daily_license_check', 'mob_daily_license_check' );