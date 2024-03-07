<?php
/*
Plugin Name: kfz-web Plugin
Plugin URI: http://www.mobilede-fahrzeugintegration.de/
Description: Importieren Sie Ihren Fahrzeugbestand von mobile.de einfach in Ihre Wordpress Seite.
Version: 2.0
Author: neusued GmbH
Author URI: www.neusued.de 
License: All rights reserved
*/

define( 'KFZ_WEB_STORE', 'https://www.mobilede-fahrzeugintegration.de/' );
define( 'KFZ_WEB_ITEM_NAME', 'mobile.de Wordpress Plugin' );

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_license_link' );
function add_license_link ( $links ) {
 $mylinks = array(
 '<a href="' . admin_url( 'plugins.php?page=kfz-web-license' ) . '">Lizenz</a>',
 );
return array_merge( $links, $mylinks );
}

include_once('mobilede-main.php');
include_once('shortcodes.php');
if (wp_get_schedule('mob_periodic_event_hook') === false){
	wp_schedule_event(time(), 'minutely', 'mob_periodic_event_hook');
}
register_deactivation_hook(__FILE__, 'mob_periodic_event_hook_activation');
function mob_periodic_event_hook_activation() {
	wp_clear_scheduled_hook('mob_periodic_event_hook');
}