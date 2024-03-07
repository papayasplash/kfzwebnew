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



$mob_data['mob_images']['web'] = 'Remotely get images From mobile.de';
$mob_data['mob_images']['host'] = 'Import images to your server';




$mob_data['mob_slider']['yes'] = 'Yes';
$mob_data['mob_slider']['no'] = 'No';
$mob_data['use_cat_tax']['yes'] = 'Yes';
$mob_data['use_cat_tax']['no'] = 'No';


/* List of supported intervals, you can change the value but please don't change the key name or the feed periodical import function may stop working */

$mob_data['download_interval']['minutely']='Every 5 minutes';
$mob_data['download_interval']['hourly']='Every hour';
$mob_data['download_interval']['daily']='Once a day';
$mob_data['download_interval']['threedays']='Every 3 days';;
$mob_data['download_interval']['weekly']='Weekly';