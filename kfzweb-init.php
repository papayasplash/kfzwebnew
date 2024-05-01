<?php
/*
Plugin Name: kfz-web Plugin
Plugin URI: http://www.mobilede-fahrzeugintegration.de/
Description: Importieren Sie Ihren Fahrzeugbestand von mobile.de einfach in Ihre Wordpress Seite.
Version: 2.0
Author: neusued GmbH
Author URI: www.neusued.de 
License: All rights reserved
Text-Domain: kfz-web
Domain Path: /languages
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
include_once('license.php');

if (wp_get_schedule('mob_periodic_event_hook') === false){
	wp_schedule_event(time(), 'minutely', 'mob_periodic_event_hook');
}
if (wp_get_schedule('kfzweb_daily_license_check') === false){
	wp_schedule_event(time(), 'minutely', 'kfzweb_daily_license_check');
}

register_deactivation_hook(__FILE__, 'mob_periodic_event_hook_deactivation');
function mob_periodic_event_hook_deactivation() {
	wp_clear_scheduled_hook('mob_periodic_event_hook');
	wp_clear_scheduled_hook('kfzweb_daily_license_check');
}
// Funktion zum Planen des täglichen Lizenzchecks
function kfzweb_schedule_license_check() {
    if (!wp_next_scheduled('kfzweb_daily_license_check')) {
        wp_schedule_event(time(), 'daily', 'kfzweb_daily_license_check');
		error_log('kfzweb_schedule_license_check wp_schedule_event wurde aufgerufen');
    }
	error_log('kfzweb_schedule_license_check wurde aufgerufen');
}

// Funktion zur Durchführung des Lizenzchecks
function kfzweb_check_license() {
    $license_status = mob_license_check(); // Funktion zur Überprüfung des Lizenzstatus
    if ($license_status !== 'active') {
        add_action('admin_notices', 'kfzweb_license_error_notice');
    }
}

// WordPress-Hook, der die Lizenzprüffunktion aufruft
add_action('kfzweb_daily_license_check', 'kfzweb_check_license');

// Funktion zur Anzeige einer Fehlermeldung im Admin-Bereich
function kfzweb_license_error_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Die Lizenz für das KFZWeb Plugin ist abgelaufen oder deaktiviert. Bitte erneuern Sie die Lizenz, um weiterhin alle Funktionen nutzen zu können.', 'kfzweb'); ?></p>
    </div>
    <?php
}

