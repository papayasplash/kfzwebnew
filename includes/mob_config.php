<?php

/**

 * New, lean version.

 * 

 * Since January 2015

 * 

 * -- bth 2015-26-01

 * 

 * 

 */



/* List of supported languages displayed in options box. 

 Valid values are cz, de, en, es, fr, it, pl, ro or ru 

*/

$mob_data['languages']['en']	='English';
$mob_data['languages']['de']	='German';
$mob_data['languages']['es']	='Spanish';
$mob_data['languages']['fr']	='France';
$mob_data['languages']['it']	='Italian';
$mob_data['languages']['pl']	='Polish';
$mob_data['languages']['ro']	='Romanian';
$mob_data['languages']['ru']	='Russian';



/* custom-type name

*/

$mob_data['customType']			='fahrzeuge';

/* List of styles 

*/

$mob_data['style']['old']	='Old Style';
$mob_data['style']['new']	='New Style';



$mob_data['mob_images']['web'] = 'Bilder abrufen von mobile.de';
$mob_data['mob_images']['host'] = 'Bilder auf den Server importieren';




$mob_data['mob_slider']['yes'] = 'Ja';
$mob_data['mob_slider']['no'] = 'Nein';
$mob_data['use_cat_tax']['yes'] = 'Ja';
$mob_data['use_cat_tax']['no'] = 'Nein';


/* List of supported intervals, you can change the value but please don't change the key name or the feed periodical import function may stop working */

$mob_data['download_interval']['minutely']='Alle 5 Minuten';
$mob_data['download_interval']['hourly']='Stündlich';
$mob_data['download_interval']['daily']='Täglich';
$mob_data['download_interval']['threedays']='Alle 3 Tage';;
$mob_data['download_interval']['weekly']='Wöchentlich';