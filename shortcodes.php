<?php 
// // Add Shortcode
function getcars( $atts, $content = null ) {

	 ob_start();
	 if(file_exists(get_stylesheet_directory() . '/' . 'mob_vehicle-list.php')) {
     include(get_stylesheet_directory() . '/' . 'mob_vehicle-list.php');
 	} else {
 		include dirname(__FILE__) . '/' . 'mob_vehicle-list.php';
 	}
     $output = ob_get_clean();
     //print $output; // debug
     wp_reset_postdata();
     return $output;

// wp_reset_postdata();
}

add_shortcode( 'fahrzeuge-anzeigen', 'getcars' );

// Add Shortcode

// function getcars( $atts, $content = null ) {


  
// }
// add_shortcode( 'fahrzeuge-anzeigen', 'getcars' );
