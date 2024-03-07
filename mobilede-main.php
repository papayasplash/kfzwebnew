<?php
/**********************************
* MOBILE DE
*
* Main file for the neusued mobile.de WordPress plugin.
**********************************/
if (!function_exists('add_action')) {
	die('Access Denied');
}
/**
 * Only for debugging purposes.
 * Please don't delete.
 *
 * --bth 2015-01-27
 *
 * @param unknown $message
 */

function log_me($message)
{
	if (WP_DEBUG === true) {
		if (is_array($message) || is_object($message)) {
			$errormessagehead = "\n \n !!! \n Start of BTH Error Message:";
			$errormessagefoot = "End of BTH Error Message. \n !!! \n \n";
			error_log(print_r($errormessagehead, true));
			error_log(print_r($message, true));
			error_log(print_r($errormessagefoot, true));
		}
		else {
			error_log($message);
		}
	}
}

// Include template checker
include_once dirname(__FILE__) . '/template-checker.php';
// Load Mobile.DE API

include_once dirname(__FILE__) . '/includes/searchAPI.php';

$options = get_option('MobileDE_option');

if (is_array(isset($options['mob_username']))) {
	// Please correct the following Quatsch. --bth 2014-10-31 06:03:56
	$mob_api = new mob_searchAPI("mob_URL", isset($options['mob_username'][0]), isset($options['mob_password'][0]), isset($options['mob_language']));
}
else {
	$mob_api = new mob_searchAPI(isset($options['mob_url']), isset($options['mob_username']), isset($options['mob_password']), isset($options['mob_language']));
}

/*
* Replacement for template-checker.php
*
*/

add_action('wp', 'template_decider');
function template_decider()
{
	registerFrontendStuff();
}
function registerFrontendStuff() {
    $options = get_option('MobileDE_option');
    if (empty($options['mob_bootstrap_option'])) {
        $options['mob_bootstrap_option'] = 'yes';
    }
    if ($options['mob_bootstrap_option'] == 'yes' && !is_admin()) {
        wp_enqueue_style('bootstrap_cdn_css', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css');
        wp_enqueue_script('bootstrap_cdn_js', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array('jquery'));
    }

    if (empty($options['mob_slider_option'])) {
        $options['mob_slider_option'] = 'yes';
    }
    if ($options['mob_slider_option'] == 'yes' && !is_admin()) {
        wp_enqueue_style('slider_cdn_css',  plugin_dir_url(__FILE__) . 'css/slick.min.css');
        wp_enqueue_style('slider_cdn_css_theme',  plugin_dir_url(__FILE__) . 'css/slick-theme.min.css');
        wp_enqueue_script('slider_cdn_js', plugin_dir_url(__FILE__) . 'js/slick.min.js', array('jquery'));
        wp_enqueue_script('slick-init', plugin_dir_url(__FILE__) . 'js/slick-init.js', array('jquery'));
    }

    if (!is_admin()) {
        wp_enqueue_style('cssSearchDE', plugin_dir_url(__FILE__) . 'css/style_16.css');
    }
    
    if (is_singular('fahrzeuge')) {
        add_filter('the_content', 'check_single_fahrzeuge');
    }
}

function registerAdminStuff()
{
	// Register needed stylesheets and JS files
	wp_register_script('admin_settings', plugin_dir_url(__FILE__) . 'js/admin_settings_scripts.js', array(
		'jquery'
	));

	
	wp_register_script('scriptSearchDE', plugin_dir_url(__FILE__) . 'js/script.js', array(
		'jquery'
	));
	wp_enqueue_script('scriptSearchDE');
	wp_enqueue_script('admin_settings');
}

add_action('admin_enqueue_scripts', 'registerAdminStuff');
// Add every 3 days, weekly custom schedules
function mob_custom_cron_schedules($schedules)
{
	$schedules['threedays'] = array(
		'interval' => '1296000',
		'display' => __('Each 3 Days')
	);

	$schedules['weekly'] = array(
		'interval' => '3024000',
		'display' => __('Weekly')
	);

	$schedules['daily'] = array(
		'interval' => '432000',
		'display' => __('Täglich')
	);

	$schedules['hourly'] = array(
		'interval' => '36000',
		'display' => __('Stündlich')
	);

	$schedules['minutely'] = array(
		'interval' => '300',
		'display' => __('Alle 5 Minuten')
	);

	return $schedules;
}

add_filter('cron_schedules', 'mob_custom_cron_schedules');

// Add a new custom type on initialization

add_action('init', 'mob_search_result_init', 1);

// Hook into the 'init' action

add_action('init', 'vehicle_taxonomy', 0);

// ********************************************************************************************************

// ********************************************************************************************************

// **************************** Register custom taxonomy for custom post type *****************************

require_once (ABSPATH . 'wp-config.php');

require_once (ABSPATH . 'wp-includes/wp-db.php');

require_once (ABSPATH . 'wp-admin/includes/taxonomy.php');

// Setup a scheduler on plugin activation

register_activation_hook(__FILE__, 'mob_plugin_activation');

function mob_plugin_activation()
{
	mob_search_result_init();
	$options = get_option('MobileDE_option');
	if (empty($options['mob_download_interval'])) {
		$options['mob_download_interval'] = 'daily';
	}
	wp_schedule_event(time() , $options['mob_download_interval'], 'mob_periodic_event_hook');
}

// Remove the scheduler on plugin deactivation

register_deactivation_hook(__FILE__, 'mob_plugin_deactivation');
function mob_plugin_deactivation()
{
	wp_clear_scheduled_hook('mob_periodic_event_hook');
}

// Helper function used to update schedule

function mob_update_schedule($newScheq)
{
	wp_clear_scheduled_hook('mob_periodic_event_hook');
	wp_schedule_event(time() , $newScheq, 'mob_periodic_event_hook');
}

// Limit Posts
// Not used at the moment.

function checkVehicleCount()
{
	$max = 2;
	global $user_ID, $wpdb;
	$num_posts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'fahrzeuge'", $user_ID));
	if ($num_posts >= $max) {
		wp_die(__("Mit ihrem derzeitigen Abonnement können Sie maximal " . $max . " Fahrzeuge importieren, Sie haben derzeit " . $num_posts . " Fahrzeuge inseriert. Weiter Informationen gibt es auf https://www.mobilede-fahrzeugintegration.de/"));
	}
}

// checkVehicleCount();
// The scheduler function that downloads search feeds

add_action('mob_periodic_event_hook', 'mob_download_import_feed');
function mob_download_import_feed()
{

	/*
	* Check API for new vehicles.
	*/
	$vehicles = getVehiclesFromApi();
	// log_me($vehicles);

	if ((!empty($vehicles)) && (!empty($vehicles['intern_adKeys']))) { // Are vehicles received and is our internal array inserted?
		$apiAdKeys = $vehicles['intern_adKeys'];
		unset($vehicles['intern_adKeys']); // Streamline the array for later comparisons.
		/*
		* Check Wordpress for current vehicles.
		*/
		$currentMeta = getCurrentMetaValues();
		if ((!empty($currentMeta)) && (!empty($currentMeta['intern_mostRecentModificationDate']))) { // Old posts found with ad_key and modification_date and one modification_date is most recent.
			$mostRecentModificationDate = $currentMeta['intern_mostRecentModificationDate'];
			unset($currentMeta['intern_mostRecentModificationDate']); // Streamline the array for later comparisons.
			$wordpressAdKeys = $currentMeta['intern_adKeys'];
			unset($currentMeta['intern_adKeys']); // Streamline the array for later comparisons.
    		$GLOBALS["mob_wordpressAdKeysCount"] = count($wordpressAdKeys);
			// echo "<div class='updated'>Fahrzeuge in WordPress: </div>";
			// echo count($wordpressAdKeys);
			// echo " Letzte Aktualisierung: $mostRecentModificationDate ";
			// * Compute rough differences between API and Wordpress vehicle data.
			$adKeysOfSoldVehicles = array_diff($wordpressAdKeys, $apiAdKeys); // C := A without B
			$adKeysOfNewVehicles = array_diff($apiAdKeys, $wordpressAdKeys); // D := B without A
			// The following line is equivalent to E := A and B, so array_intersect() would do the job. But B without C has less fields to compare.
			$adKeysOfRemainingVehicles = array_diff($apiAdKeys, $adKeysOfNewVehicles); // E := B without C
			/*
			* Delete sold vehicles from Wordpress.
			*/
			// echo "Verkaufte Fahrzeuge zum Entfernen: ";
			// echo count($adKeysOfSoldVehicles);
			deleteByAdKeys($currentMeta, $adKeysOfSoldVehicles);
			$newVehicles = getVehiclesByAdkeys($vehicles, $adKeysOfNewVehicles);
			$remainingVehicles = getVehiclesByAdkeys($vehicles, $adKeysOfRemainingVehicles);
			$modifiedVehicles = getVehiclesModifiedSince($remainingVehicles, $mostRecentModificationDate);
			/*
			* Write new vehicles to wordpress.
			*/
			if (!empty($newVehicles)) {
				//	echo " Neue Fahrzeuge: ";
				//	echo count($newVehicles);
				$post_ids = importVehicles($newVehicles);
			}
			else {
				// echo "Keine neuen Fahrzeuge gefunden. ";
			}
			/*
			* Handle modified vehicles.
			*/
			$modifiedVehicles = getVehiclesModifiedSince($remainingVehicles, $mostRecentModificationDate);
			if (!empty($modifiedVehicles)) {
				/*
				* Delete modified vehicles from Wordpress.
				*/
				// 				echo " Aktualisierte Fahrzeuge werden synchronisiert: ";
				// 				echo count($modifiedVehicles)-1;
				deleteByAdKeys($currentMeta, $modifiedVehicles['intern_adKeys']);
				/*
				* Write modified vehicles to wordpress.
				*/
				unset($modifiedVehicles['intern_adKeys']);
				$post_ids = importVehicles($modifiedVehicles);
			}
			else { // Assume: no modified vehicles from API
				/*
				* Handle new vehicles.
				*/
				//	echo " Keine aktualisierten Fahrzeuge gefunden. ";
			}
		}
		else { // No posts yet or old meta data did not contain ad_key or modification_date.
			//	echo " Bereinige alte Daten. ";
			mob_deleteAllPosts(); // So we delete all currently stored vehicle data.
			$post_ids = importVehicles($vehicles); // And import all vehicles to wordpress.
		}
	}
	else {
		// No Vehicles received.
		// Reasons: Error OR error while packing $vehicles['inter_adKeys'] OR no advertisements at the moment.
		// Add Error handling.
	}
	// End of mob_download_import_feed
	// removed since FacetWP 2.9.1
	// prepare_facetwp(); // Call the function that handles my_facetwp_indexer_query_args // bth, fmh
	 mob_clean();
}

add_action('mob_cleanup', 'mob_clean');
// function prepare_facetwp()
// {
// 	remove_filter('facetwp_indexer_query_args', 'my_facetwp_indexer_query_args');
// }
// function my_facetwp_indexer_query_args($args)
// {
// 	$args['post_status'] = array(
// 		'publish',
// 		'private'
// 	);
// 	return $args;
// }

function deleteByAdKeys($metaValues, $adKeys)
{
	$postIds = array();
	foreach($metaValues as $current) {
		if (in_array($current['ad_key'], $adKeys)) {
			$postIds[] = $current['post_id'];
		}
	}

	if (!empty($postIds)) {
		removePostsbyIds($postIds);
	}
}

// function getAdKeys($vehicles){
//     $adKeys = array();
//     foreach ($vehicles as $vehicle){
//         if (isset($vehicle['ad_key'])) {
//             $adKeys[] = $vehicle['ad_key'];
//         }
//     }
//     return $adKeys;
// }

 function getVehiclesFromApi()
{
	set_time_limit(0);
	$options = get_option('MobileDE_option');
	$vehicles = array();
	// Maybe a if (is_array(mob_username)) is needed here to serve single-account users. --bth 2014-10-31 06:06:34
	for ($i = 0; $i < count($options['mob_username']); $i++) {
		$mob_api = new mob_searchAPI("mob_url", $options['mob_username'][$i], $options['mob_password'][$i], $options['mob_language']);
		$temp = $mob_api->getAllAds();
		if (!empty($temp)) {
		// Merge data from different API accounts
			if (isset($temp['intern_adKeys'])) {
				if (isset($vehicles['intern_adKeys'])) {
					$tempInternAdKeys = array_merge($vehicles['intern_adKeys'], $temp['intern_adKeys']);
				}
				else {
					$tempInternAdKeys = $temp['intern_adKeys'];
				}
			}

			$vehicles = array_merge($vehicles, $temp);
			if (isset($tempInternAdKeys)) {
				// Write tempAdKeys back to array (overwrite).
				$vehicles['intern_adKeys'] = $tempInternAdKeys;
			}

			// ToDo:
			// Equeue Status Message for Backend: "Account .... has ... vehicles"
			// --bth 2014-11-11
		}
		else {
			// $temp was empty!
			// Add error handling.
		}
	}

	//    file_put_contents('testlog.txt', print_r($vehicles, true));

	return $vehicles;
}

function getVehiclesByAdkeys($vehicles, $adKeys)
{
	$result = array();
	foreach($vehicles as $vehicle) {
		if (in_array($vehicle['ad_key'], $adKeys)) {
			$result[] = $vehicle;
		}
	}

	return $result;
}

function importVehicles($vehicles)
{
	global $mob_data;
	$post_ids = array();
	foreach($vehicles as $vehicle) {
		$args = array(
			'post_type' => $mob_data['customType'],
			'meta_query' => array(
				array(
					'key' => 'ad_key',
					'value' => $vehicle['ad_key']
				)
			)
		);
		$query = new WP_Query($args);
		if ($query->have_posts()) { // It is already in our data.
			$post_id = $query->posts[0]->ID;
		}
		else { // We have to insert it.
			$post_ids[] = writeIntoWp($vehicle);
		}
	}
	return $post_ids;
}
/**
 *
 * Returns an array of all vehicles in $vehicles which were modified after
 * $modDate.
 *
 * Inside the array there is another array which contains only the adKeys of
 * modified vehicles:
 *
 * $modifiedVehicles['intern_adKeys'][]
 *
 * @param array $vehicles
 *
 * @param date $modDate
 *
 * @return array
 *
 */
function getVehiclesModifiedSince($vehicles, $modDate)
{
	if (empty($vehicles)) {
		// In case of an empty array $vehicles we can return that empty Array
		return $vehicles;
	}
	else {
		$temp = strtotime($modDate);
		$modifiedVehicles = array();
		foreach($vehicles as $vehicle) {
			if (strtotime($vehicle['modification_date']) > $temp) {
				$modifiedVehicles[] = $vehicle;

				// Insert additional array of adKeys due to performance reasons.

				$modifiedVehicles['intern_adKeys'][] = $vehicle['ad_key'];
			}
		}
		return $modifiedVehicles;
	}
}

/**
 *
 * Returns an array of meta values.
 *
 * $currentMetaResult['intern_mostRecentModificationDate'] contains the most
 * recent modification date.
 *
 * @return array NULL
 */
/*
* Combines "getCurrentMetaValues()" with "getMostRecentModificationDate()".
*/
function getCurrentMetaValues()
{
	$args = array(
		'post_type' => 'fahrzeuge',
		'order' => 'ASC',
		'orderby' => 'title',
		'posts_per_page' => '-1'
	);
	// The Query
	$query = new WP_Query($args);
	$currentMetaResult = array();
	$mostRecent = 0; // Pivot element initialized with 0.
	// The Loop
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$meta_values = get_post_meta(get_the_ID());
			if (isset($meta_values['ad_key'][0]) && (isset($meta_values['modification_date'][0]))) {
				// Store meta data.
				$currentMetaResult[get_the_ID() ] = ["ad_key" => $meta_values['ad_key'][0], "modification_date" => $meta_values['modification_date'][0], "post_id" => get_the_ID() ];
				// Store adKeys in an additional array due to performance reasons.
				$currentMetaResult['intern_adKeys'][] = $meta_values['ad_key'][0];
				// Compute most recent modification date.
				$curDate = strtotime($meta_values['modification_date'][0]);
				if ($curDate > $mostRecent) {
					$mostRecent = $curDate;
					$currentMetaResult['intern_mostRecentModificationDate'] = (string)$meta_values['modification_date'][0];
				}
				/*
				* Array $currentMeta looks like:
				* $currentMeta['234534535345'] =>
				* 'modification_date' => '2014-11-06:17:00'
				* 'id' => '234534535345'
				* 'ad_key' = '123545'
				* $currentMeta['intern_mostRecentModificationDate'] = '2014-11-11T13:31:00+2'
				*/
			}
			else {
				// Problem: No ad_key OR modification_date is set for the post.
				// Should be old data from an old version.
				// Add Error Handling --bth 2014-11-06
			}
		}

		return $currentMetaResult;
	}
	else {
		// No posts yet. First import.
		return null;
	}
}
/**
 *
 * @param unknown $currentMetaValues
 *
 * @return string NULL
 */
// Not used at the moment.
function getMostRecentModDate($currentMetaValues)
{
	if (!empty($currentMetaValues)) {
		$mostRecent = 0;
		$result = "";
		foreach($currentMetaValues as $meta) {
			$curDate = strtotime($meta['modification_date'][0]);
			if ($curDate > $mostRecent) {
				$mostRecent = $curDate;
				$result = (string)$meta['modification_date'][0];
			}
		}
		return $result;
	}
	else {
		return null;
	}
}

/**
 *
 * Checks if the license for the current version is valid.
 * @return boolean
 */
/*
* Discuss what happens if license expired with fmh.
*/
function removePostsByIds($post_ids)
{
	global $mob_data;
	// echo " Fahrzeuge entfernt: ";
	// The section marked with start... end could be deleted. Test this. --bth
	// Start.

	$query = new WP_Query(array(
		'post_type' => $mob_data['customType'],
		'post_status' => 'publish',
		'numberposts' => - 1,
		'posts_per_page' => - 1,
		'post__in' => $post_ids
	));
	// End.
	foreach($query->posts as $post) {
		mob_delete_attachment($post->ID);
		wp_delete_post($post->ID, true);
	}
}
function writeIntoWp($item)
{
	global $mob_data;
	/*
	* Create dummy array to silence
	* array_filter().... Error in post.php
	*/
	$options = get_option('MobileDE_option');

	if (empty(@$options['mob_cat_tax_option'])) {
		@$options['mob_cat_tax_option'] = 'no';
	}

	if (@$options['mob_cat_tax_option'] == 'yes') {
	$class_catid = wp_create_category($item['category']);
	} else {
	$class_catid = '';
	}
	$post_id = wp_insert_post(array(
		'post_status' => 'publish',
		'post_author' => '1',
		'post_type' => $mob_data['customType'],
		'post_title' => $item['make'] . ' ' . $item['model_description'],
		'post_content' => $item['enriched_description'],
		'tags_input' => $item['model_description'],
		'comment_status' => 'closed', // disable comments fmh 24.02.15
		'tax_input' => array(), // Custom Taxonomies that are loaded after? --bth 2014-11-12
		'post_category' => array($class_catid)
	));
	/* Custom Taxonomies */
	$kategorie = array(
		'kategorie' => @$item['category']
	);
	$preis = array(
		'preis' => $item['price']
	);
	wp_set_object_terms($post_id, $preis, 'preis');

	$modell = array(
		'modell' => @$item['model']
	);

	wp_set_object_terms($post_id, $modell, 'modell');
	
	$marke = array(
		'marke' => @$item['make']
	);

	wp_set_object_terms($post_id, $marke, 'marke');
	$zustand = array(
		'zustand' => @$item['condition']
	);

	wp_set_object_terms($post_id, $zustand, 'zustand');
	$klasse = array(
		'klasse' => @$item['class']
	);

	wp_set_object_terms($post_id, $klasse, 'klasse');
	$kraftstoffart = array(
		'kraftstoffart' => @$item['fuel']
	);

	wp_set_object_terms($post_id, $kraftstoffart, 'kraftstoffart');
	$getriebe = array(
		'getriebe' => @$item['gearbox']
	);

	wp_set_object_terms($post_id, $getriebe, 'getriebe');
	$erstzulassung = array(
		'erstzulassung' => @$item['first_registration']
	);

	wp_set_object_terms($post_id, $erstzulassung, 'erstzulassung');
	$standort = array(
		'standort' => @$item['seller']
	);

	wp_set_object_terms($post_id, $standort, 'standort');
	$beschreibung = array(
		'beschreibung' => @$item['model_description']
	);

	wp_set_object_terms($post_id, $beschreibung, 'beschreibung');
	$schaden = array(
		'schaden' => @$item['schaden']
	);

	wp_set_object_terms($post_id, $schaden, 'schaden');
	$emissionsklasse = array(
		'emissionsklasse' => @$item['emission_class']
	);

	wp_set_object_terms($post_id, $emissionsklasse, 'emissionsklasse');
	$co2_emission = array(
		'co2_emission' => @$item['co2_emission']
	);

	wp_set_object_terms($post_id, $co2_emission, 'co2_emission');
	$verbrauch_innerorts = array(
		'verbrauch_innerorts' => @$item['inner']
	);

	wp_set_object_terms($post_id, $verbrauch_innerorts, 'verbrauch_innerorts');
	$verbrauch_ausserorts = array(
		'verbrauch_ausserorts' => @$item['outer']
	);

	wp_set_object_terms($post_id, $verbrauch_ausserorts, 'verbrauch_ausserorts');
	$verbrauch_kombiniert = array(
		'verbrauch_kombiniert' => @$item['combined']
	);

	wp_set_object_terms($post_id, $verbrauch_kombiniert, 'verbrauch_kombiniert');
	$aussenfarbe = array(
		'aussenfarbe' => @$item['aussenfarbe']
	);

	wp_set_object_terms($post_id, $aussenfarbe, 'aussenfarbe');
	$hubraum = array(
		'hubraum' => @$item['cubic_capacity']
	);

	wp_set_object_terms($post_id, $hubraum, 'hubraum');
	$naechste_hu = array(
		'naechste_hu' => @$item['nextInspection']
	);

	wp_set_object_terms($post_id, $naechste_hu, 'naechste_hu');
	$ausstattung = array(
		'ausstattung' => @$item['features']
	);

	wp_set_object_terms($post_id, $ausstattung, 'ausstattung');
	$kilometer = array(
		'kilometer' => @$item['mileage']
	);

	wp_set_object_terms($post_id, $kilometer, 'kilometer');
	$kilometer_unformatiert = array(
		'kilometer_unformatiert' => @$item['mileage_raw']
	);

	wp_set_object_terms($post_id, $kilometer_unformatiert, 'kilometer_unformatiert');
	$kilometer_dropdown = array(
		'kilometer_dropdown' => @$item['kilometer_dropdown']
	);

	wp_set_object_terms($post_id, $kilometer_dropdown, 'kilometer_dropdown');

	$preis_unformatiert = array(
		'preis_unformatiert' => @$item['price_raw_short']
	);

	wp_set_object_terms($post_id, $preis_unformatiert, 'preis_unformatiert');

	$ladekapazitaet = array(
		'ladekapazitaet' => @$item['loadCapacity']
	);

	wp_set_object_terms($post_id, $ladekapazitaet, 'ladekapazitaet');

	$ad_gallery = array(
		'ad_gallery' => @$item['images']
	);

	wp_set_object_terms($post_id, $ad_gallery, 'ad_gallery');

	$baujahr = array(
		'baujahr' => @$item['construction-year']
	);

	wp_set_object_terms($post_id, $baujahr, 'baujahr');

	$anzahl_schlafplaetze = array(
		'anzahl_schlafplaetze' => @$item['number_of_bunks']);
	wp_set_object_terms($post_id, $anzahl_schlafplaetze, 'anzahl_schlafplaetze');


	if(!empty($item['ad_key'])) { add_post_meta($post_id, 'vehicleListingID', $item['ad_key'], true); }
	add_post_meta($post_id, 'dataSource', 'mobile.de_api');

	if(!empty($item['newCars'])) { add_post_meta($post_id, 'newCars', $item['creation-date']); }
	if(!empty($item['class'])) { add_post_meta($post_id, 'class', $item['class']); }
	if(!empty($item['brand'])) { add_post_meta($post_id, 'brand', $item['make']); } // Deprecated.
	if(!empty($item['make'])) { add_post_meta($post_id, 'make', $item['make']); }
	if(!empty($item['model'])) { add_post_meta($post_id, 'model', $item['model']); }
	if(!empty($item['model_variant'])) { add_post_meta($post_id, 'model_variant', $item['model_variant']); }
	if(!empty($item['variant'])) { add_post_meta($post_id, 'variant', $item['model_description']); } // Deprecated.
	if(!empty($item['model_description'])) { add_post_meta($post_id, 'model_description', $item['model_description']); }
	if(!empty($item['damage-and-unrepaired'])) { add_post_meta($post_id, 'damageUnrepaired', $item['damage-and-unrepaired']); } // Deprecated
	if(!empty($item['damage-and-unrepaired'])) { add_post_meta($post_id, 'damage_and_unrepaired', $item['damage-and-unrepaired']); }
	if(!empty($item['accident_damaged'])) { add_post_meta($post_id, 'accidentDamaged', $item['accident_damaged']); }
	if(!empty($item['road_worthy'])) { add_post_meta($post_id, 'road_worthy', $item['road_worthy']); }
	if(!empty($item['category'])) { add_post_meta($post_id, 'category', $item['category']); }
	if(!empty($item['condition'])) { add_post_meta($post_id, 'condition', $item['condition']); }
	if(!empty($item['seller'])) { add_post_meta($post_id, 'seller', $item['seller']); }
	if(!empty($item['seller_id'])) { add_post_meta($post_id, 'seller_id', $item['seller_id']); }
	if(!empty($item['seller_company_name'])) { add_post_meta($post_id, 'seller_company_name', $item['seller_company_name']); }
	if(!empty($item['seller_street'])) { add_post_meta($post_id, 'seller_street', $item['seller_street']); }
	if(!empty($item['seller_zipcode'])) { add_post_meta($post_id, 'seller_zipcode', $item['seller_zipcode']); }
	if(!empty($item['seller_city'])) { add_post_meta($post_id, 'seller_city', $item['seller_city']); }
	if(!empty($item['seller_country'])) { add_post_meta($post_id, 'seller_country', $item['seller_country']); }
	if(!empty($item['seller_email'])) { add_post_meta($post_id, 'seller_email', $item['seller_email']); }
	if(!empty($item['seller_homepage'])) { add_post_meta($post_id, 'seller_homepage', $item['seller_homepage']); }
	if(!empty($item['seller_phone_country_calling_code'])) { add_post_meta($post_id, 'seller_phone_country_calling_code', $item['seller_phone_country_calling_code']); }
	if(!empty($item['seller_phone_area_code'])) { add_post_meta($post_id, 'seller_phone_area_code', $item['seller_phone_area_code']); }
	if(!empty($item['seller_phone_number'])) { add_post_meta($post_id, 'seller_phone_number', $item['seller_phone_number']); }
	if(!empty($item['seller_since'])) { add_post_meta($post_id, 'seller_since', $item['seller_since']); }
	if(!empty($item['sellerType'])) { add_post_meta($post_id, 'sellerType', $item['sellerType']); }
	if(!empty($item['first_registration'])) { add_post_meta($post_id, 'firstRegistration', $item['first_registration']); }
	if(!empty($item['first_registration_year'])) { add_post_meta($post_id, 'firstRegistration_year', $item['first_registration_year']); } // Added 2015-03-16 --bth
	if(!empty($item['emissionClass'])) { add_post_meta($post_id, 'emissionClass', $item['emission_class']); }
	if(!empty($item['emission_class'])) { add_post_meta($post_id, 'emission_class', $item['emission_class']); }
	if(!empty($item['co2_emission'])) { add_post_meta($post_id, 'emissionFuelConsumption_CO2', $item['co2_emission']); }
	if(!empty($item['inner'])) { add_post_meta($post_id, 'emissionFuelConsumption_Inner', $item['inner']); }
	if(!empty($item['outer'])) { add_post_meta($post_id, 'emissionFuelConsumption_Outer', $item['outer']); }
	if(!empty($item['combined'])) { add_post_meta($post_id, 'emissionFuelConsumption_Combined', $item['combined']); }
	if(!empty($item['combined-power-consumption'])) { add_post_meta($post_id, 'combinedPowerConsumption', $item['combined-power-consumption']); }
	if(!empty($item['unit'])) { add_post_meta($post_id, 'emissionFuelConsumption_Unit', $item['unit']); }
	if(!empty($item['emissionSticker'])) { add_post_meta($post_id, 'emissionSticker', $item['emissionSticker']); }
	if(!empty($item['exterior_color'])) { add_post_meta($post_id, 'exteriorColor', $item['exterior_color']); }
	if(!empty($item['fuel'])) { add_post_meta($post_id, 'fuel', $item['fuel']); }
	if(!empty($item['power'])) { add_post_meta($post_id, 'power', $item['power']); }
	if(!empty($item['number_of_previous_owners'])) { add_post_meta($post_id, 'owners', $item['number_of_previous_owners']); }
	if(!empty($item['cubic_capacity'])) { add_post_meta($post_id, 'cubicCapacity', $item['cubic_capacity']); }
	if(!empty($item['gearbox'])) { add_post_meta($post_id, 'gearbox', $item['gearbox']); }
	// add_post_meta($post_id, 'monthsTillInspection', $item['monthsTillInspection']);
	if(!empty($item['nextInspection'])) { add_post_meta($post_id, 'nextInspection', $item['nextInspection']); }
	if(!empty($item['features'])) { add_post_meta($post_id, 'features', $item['features']); }
	if(!empty($item['mileage'])) { add_post_meta($post_id, 'mileage', $item['mileage']); }
	if(!empty($item['mileage_raw'])) { add_post_meta($post_id, 'mileage_raw', $item['mileage_raw']); } // Added 2015-03-16 --bth
	if(!empty($item['mileage_class'])) { add_post_meta($post_id, 'mileage_class', $item['mileage_class']); } // Added 2015-03-16 --bth
	if(!empty($item['price'])) { add_post_meta($post_id, 'price', $item['price']); }
	if(!empty($item['dealer-price-amount'])) { add_post_meta($post_id, 'dealer_price', $item['dealer-price-amount']); }
	//	add_post_meta($post_id, 'price_raw', $item['price_raw']); // Added 2015-03-16 --bth
	if(!empty($item['price_raw_short'])) { add_post_meta($post_id, 'price_raw_short', $item['price_raw_short']); } // Added 2015-03-16 --bth
	if(!empty($item['currency'])) { add_post_meta($post_id, 'currency', $item['currency']); }
	if(!empty($item['vatable'])) { add_post_meta($post_id, 'vatable', $item['vatable']); }
	if(!empty($item['loadCapacity'])) { add_post_meta($post_id, 'loadCapacity', $item['loadCapacity']); }
	if(!empty($item['detail_page'])) { add_post_meta($post_id, 'detailPage', $item['detail_page']); } // Deprecated
	if(!empty($item['detail_page'])) { add_post_meta($post_id, 'detail_page', $item['detail_page']); }
	if(!empty($item['country'])) { add_post_meta($post_id, 'country', $item['country']); }
	if(!empty($item['zipcode'])) { add_post_meta($post_id, 'zipcode', $item['zipcode']); }
	if(!empty($item['ad_key'])) { add_post_meta($post_id, 'ad_key', $item['ad_key']); }
	// Changes for the caravan seller
	if(!empty($item['construction-year'])) { add_post_meta($post_id, 'construction_year', $item['construction-year']); }
	if(!empty($item['number-of-bunks'])) { add_post_meta($post_id, 'number_of_bunks', $item['number-of-bunks']); }
	if(!empty($item['length'])) { add_post_meta($post_id, 'length', $item['length']); }
	if(!empty($item['width'])) { add_post_meta($post_id, 'width', $item['width']); }
	if(!empty($item['height'])) { add_post_meta($post_id, 'height', $item['height']); }
	if(!empty($item['licensed-weight'])) { add_post_meta($post_id, 'licensed_weight', $item['licensed-weight']); }
	// Additional data. --bth 2014-10-29 02:18:13
	/*
	* Delivery date and period.
	*
	*/
	if(!empty($item['delivery-date'])) { add_post_meta($post_id, 'delivery_date', $item['delivery-date']); }
	if(!empty($item['delivery-period'])) { add_post_meta($post_id, 'delivery_period', $item['delivery-period']); }
	/*
	* Contains either the future delivery_date or the string "Sofort".
	* Gets calculated in searchAPI.
	*/
	if(!empty($item['available-from'])) { add_post_meta($post_id, 'available_from', $item['available-from']); }
	if(!empty($item['interior-type'])) { add_post_meta($post_id, 'interior_type', $item['interior-type']); }
	if(!empty($item['interior-color'])) { add_post_meta($post_id, 'interior_color', $item['interior-color']); }
	if(!empty($item['door-count'])) { add_post_meta($post_id, 'door_count', $item['door-count']); }
	if(!empty($item['num-seats'])) { add_post_meta($post_id, 'num_seats', $item['num-seats']); }
	if(!empty($item['number_of_previous_owners'])) { add_post_meta($post_id, 'number_of_previous_owners', $item['number_of_previous_owners']); }
	if(!empty($item['seller-inventory-key'])) { add_post_meta($post_id, 'seller_inventory_key', $item['seller-inventory-key']); }
	if(!empty($item['airbag'])) { add_post_meta($post_id, 'airbag', $item['airbag']); }
	/*
	* Efficiency class and efficiency class image url.
	*/
	if(!empty($item['energy-efficiency-class'])) { add_post_meta($post_id, 'efficiency_class', $item['energy-efficiency-class']); }
	if (!empty($item['energy-efficiency-class'])) {
		// log_me(plugin_dir_url(__FILE__).'images/'.$item['energy-efficiency-class'].'.png');
		// Maybe additional condition
		{
		add_post_meta($post_id, 'efficiency_class_image_url', get_site_url() . '/wp-content/plugins/kfzweb/images/' . $item['energy-efficiency-class'] . '.png');
		}
	}
	// keys used in search
	if(!empty($item['class_key'])) { add_post_meta($post_id, 'class_key', $item['class_key']); }
	if(!empty($item['category_key'])) { add_post_meta($post_id, 'category_key', $item['category_key']); }
	if(!empty($item['brand_key'])) { add_post_meta($post_id, 'brand_key', $item['brand_key']); }
	if(!empty($item['model_key'])) { add_post_meta($post_id, 'model_key', $item['model_key']); }
	if(!empty($item['fuel_key'])) { add_post_meta($post_id, 'fuel_key', $item['fuel_key']); }
	if(!empty($item['power_key'])) { add_post_meta($post_id, 'power_key', $item['power_key']); }
	if(!empty($item['owners_key'])) { add_post_meta($post_id, 'owners_key', $item['owners_key']); }
	if(!empty($item['cubicCapacity_key'])) { add_post_meta($post_id, 'cubicCapacity_key', $item['cubicCapacity_key']); }
	if(!empty($item['gearbox_key'])) { add_post_meta($post_id, 'gearbox_key', $item['gearbox_key']); }
	if(!empty($item['modification_date'])) { add_post_meta($post_id, 'modification_date', $item['modification_date']); }
	if(!empty($item['usage-type'])) { add_post_meta($post_id, 'usage_type', $item['usage-type']); }
	if(!empty($item['addition'])) { add_post_meta($post_id, 'addition', $item['addition']); }
	if(!empty($item['enriched_description'])) { add_post_meta($post_id, 'enriched_description', $item['enriched_description']); } 
	// fmh 01.03.15 added for new template-checker content
	if(!empty($item['identification-number'])) { add_post_meta($post_id, 'identification_number', $item['identification-number']); }
	if(!empty($item['axles'])) { add_post_meta($post_id, 'axles', $item['axles']); }
	if(!empty($item['wheel-formula'])) { add_post_meta($post_id, 'wheel_formula', $item['wheel-formula']); }
	if(!empty($item['hydraulic-installation'])) { add_post_meta($post_id, 'hydraulic_installation', $item['hydraulic-installation']); }
	if(!empty($item['europallet-storage-spaces'])) { add_post_meta($post_id, 'europallet_storage_spaces', $item['europallet-storage-spaces']); }
	if(!empty($item['manufacturer-color-name'])) { add_post_meta($post_id, 'manufacturer_color_name', $item['manufacturer-color-name']); }
	if(!empty($item['shipping-volume'])) { add_post_meta($post_id, 'shipping_volume', $item['shipping-volume']); }
	if(!empty($item['loadCapacity'])) { add_post_meta($post_id, 'load_capacity', $item['loadCapacity']); }

	$numimages = count($item['images']);

	if($numimages > 1) {
		add_post_meta($post_id, 'carousel', '1');
	}
	// add_post_meta($post_id, 'ad_gallery', $item['images']);


$options = get_option('MobileDE_option');

if (empty($options['mob_image_option'])) {

	$options['mob_image_option'] = 'web';

}



if ($options['mob_image_option'] == 'web') {



	foreach($item['images'] as $image) {



		add_post_meta($post_id, 'ad_gallery', (string)$image);



	}



	if (substr($item['images'][0], -6) == '27.JPG') {

		$temp = str_replace('27.JPG', '57.JPG', $item['images'][0]); // 1600x1200 px

		if (getimagesize($temp)) { // This is the FileExists check. Using a dirty side effect, but seems to be fast.
			$i = '';
			$metaData = import_post_image($post_id, $temp, $i == 0);
			// metaData = add_post_meta($post_id, $temp, $i);
		}

		else {
			$metaData = import_post_image($post_id, $item['images'][0], $i == 0); // Original sole API image.
		}
	}

	else {
		$metaData = import_post_image($post_id, $item['images'][0], $i == 0); // Original sole API image.
	}

} else {
		foreach($item['images'] as $i => $image) {
		/***
		* Import bigger image.
		*
		*/
		// log_me('EBAY BILD');
		// log_me((string)$image);

		// add_post_meta($post_id, 'ad_gallery', (string)$image);

		if (substr($image, -6) == '27.JPG') {

			$temp = str_replace('27.JPG', '57.JPG', $image); // 1600x1200 px

			if (getimagesize($temp)) { // This is the FileExists check. Using a dirty side effect, but seems to be fast.

				$metaData = import_post_image($post_id, $temp, $i == 0);

				// metaData = add_post_meta($post_id, $temp, $i);
			}

			else {

				$metaData = import_post_image($post_id, $image, $i == 0); // Original sole API image.

			}

		}

		else {

			$metaData = import_post_image($post_id, $image, $i == 0); // Original sole API image.

		}

	}

}

	// new feature meta_values as single post_meta
	if(!empty($item['ABS'])) { add_post_meta($post_id, 'ABS', $item['ABS']); }
	if(!empty($item['ALLOY_WHEELS'])) { add_post_meta($post_id, 'ALLOY_WHEELS', $item['ALLOY_WHEELS']); }
	if(!empty($item['AUTOMATIC_RAIN_SENSOR'])) { add_post_meta($post_id, 'AUTOMATIC_RAIN_SENSOR', $item['AUTOMATIC_RAIN_SENSOR']); }
	if(!empty($item['AUXILIARY_HEATING'])) { add_post_meta($post_id, 'AUXILIARY_HEATING', $item['AUXILIARY_HEATING']); }
	if(!empty($item['BENDING_LIGHTS'])) {add_post_meta($post_id, 'BENDING_LIGHTS', $item['BENDING_LIGHTS']); }
	if(!empty($item['BIODIESEL_SUITABLE'])) {add_post_meta($post_id, 'BIODIESEL_SUITABLE', $item['BIODIESEL_SUITABLE']); }
	if(!empty($item['BLUETOOTH'])) {add_post_meta($post_id, 'BLUETOOTH', $item['BLUETOOTH']); }
	if(!empty($item['CD_MULTICHANGER'])) {add_post_meta($post_id, 'CD_MULTICHANGER', $item['CD_MULTICHANGER']); }
	if(!empty($item['CD_PLAYER'])) {add_post_meta($post_id, 'CD_PLAYER', $item['CD_PLAYER']); }
	if(!empty($item['CENTRAL_LOCKING'])) {add_post_meta($post_id, 'CENTRAL_LOCKING', $item['CENTRAL_LOCKING']); }
	if(!empty($item['CRUISE_CONTROL'])) {add_post_meta($post_id, 'CRUISE_CONTROL', $item['CRUISE_CONTROL']); }
	if(!empty($item['DAYTIME_RUNNING_LIGHTS'])) {add_post_meta($post_id, 'DAYTIME_RUNNING_LIGHTS', $item['DAYTIME_RUNNING_LIGHTS']); }
	if(!empty($item['E10_ENABLED'])) {add_post_meta($post_id, 'E10_ENABLED', $item['E10_ENABLED']); }
	if(!empty($item['ELECTRIC_ADJUSTABLE_SEATS'])) {add_post_meta($post_id, 'ELECTRIC_ADJUSTABLE_SEATS', $item['ELECTRIC_ADJUSTABLE_SEATS']); }
	if(!empty($item['ELECTRIC_EXTERIOR_MIRRORS'])) {add_post_meta($post_id, 'ELECTRIC_EXTERIOR_MIRRORS', $item['ELECTRIC_EXTERIOR_MIRRORS']); }

	if(!empty($item['AIR_SUSPENSION'])) {add_post_meta($post_id, 'AIR_SUSPENSION', $item['AIR_SUSPENSION']); }
	if(!empty($item['ALARM_SYSTEM'])) {add_post_meta($post_id, 'ALARM_SYSTEM', $item['ALARM_SYSTEM']); }
	if(!empty($item['CARPLAY'])) {add_post_meta($post_id, 'CARPLAY', $item['CARPLAY']); }
	
	if(!empty($item['LANE_DEPARTURE_WARNING'])) {add_post_meta($post_id, 'LANE_DEPARTURE_WARNING', $item['LANE_DEPARTURE_WARNING']); }
	if(!empty($item['SKI_BAG'])) {add_post_meta($post_id, 'SKI_BAG', $item['SKI_BAG']); }
	if(!empty($item['AMBIENT_LIGHTING'])) {add_post_meta($post_id, 'AMBIENT_LIGHTING', $item['AMBIENT_LIGHTING']); }
	if(!empty($item['KEYLESS_ENTRY'])) {add_post_meta($post_id, 'KEYLESS_ENTRY', $item['KEYLESS_ENTRY']); }
	if(!empty($item['DISABLED_ACCESSIBLE'])) {add_post_meta($post_id, 'DISABLED_ACCESSIBLE', $item['DISABLED_ACCESSIBLE']); }
	if(!empty($item['DIGITAL_COCKPIT'])) {add_post_meta($post_id, 'DIGITAL_COCKPIT', $item['DIGITAL_COCKPIT']); }
	if(!empty($item['COLLISION_AVOIDANCE'])) {add_post_meta($post_id, 'COLLISION_AVOIDANCE', $item['COLLISION_AVOIDANCE']); }
	if(!empty($item['ELECTRIC_HEATED_REAR_SEATS'])) {add_post_meta($post_id, 'ELECTRIC_HEATED_REAR_SEATS', $item['ELECTRIC_HEATED_REAR_SEATS']); }
	if(!empty($item['ELECTRIC_BACKSEAT_ADJUSTMENT'])) {add_post_meta($post_id, 'ELECTRIC_BACKSEAT_ADJUSTMENT', $item['ELECTRIC_BACKSEAT_ADJUSTMENT']); }
	if(!empty($item['BLIND_SPOT_MONITOR'])) {add_post_meta($post_id, 'BLIND_SPOT_MONITOR', $item['BLIND_SPOT_MONITOR']); }

	
	

	if(!empty($item['ELECTRIC_HEATED_SEATS'])) {add_post_meta($post_id, 'ELECTRIC_HEATED_SEATS', $item['ELECTRIC_HEATED_SEATS']); }
	if(!empty($item['ELECTRIC_WINDOWS'])) {add_post_meta($post_id, 'ELECTRIC_WINDOWS', $item['ELECTRIC_WINDOWS']); }
	if(!empty($item['ESP'])) {add_post_meta($post_id, 'ESP', $item['ESP']); }
	if(!empty($item['EXPORT'])) {add_post_meta($post_id, 'EXPORT', $item['EXPORT']); }
	if(!empty($item['FRONT_FOG_LIGHTS'])) {add_post_meta($post_id, 'FRONT_FOG_LIGHTS', $item['FRONT_FOG_LIGHTS']); }
	if(!empty($item['FULL_SERVICE_HISTORY'])) {add_post_meta($post_id, 'FULL_SERVICE_HISTORY', $item['FULL_SERVICE_HISTORY']); }
	if(!empty($item['HANDS_FREE_PHONE_SYSTEM'])) {add_post_meta($post_id, 'HANDS_FREE_PHONE_SYSTEM', $item['HANDS_FREE_PHONE_SYSTEM']); }
	if(!empty($item['HEAD_UP_DISPLAY'])) {add_post_meta($post_id, 'HEAD_UP_DISPLAY', $item['HEAD_UP_DISPLAY']); }
	if(!empty($item['HU_AU_NEU'])) {add_post_meta($post_id, 'HU_AU_NEU', $item['HU_AU_NEU']); }
	if(!empty($item['HYBRID_PLUGIN'])) {add_post_meta($post_id, 'HYBRID_PLUGIN', $item['HYBRID_PLUGIN']); }
	if(!empty($item['IMMOBILIZER'])) {add_post_meta($post_id, 'IMMOBILIZER', $item['IMMOBILIZER']); }
	if(!empty($item['ISOFIX'])) {add_post_meta($post_id, 'ISOFIX', $item['ISOFIX']); }
	if(!empty($item['LIGHT_SENSOR'])) {add_post_meta($post_id, 'LIGHT_SENSOR', $item['LIGHT_SENSOR']); }
	if(!empty($item['METALLIC'])) {add_post_meta($post_id, 'METALLIC', $item['METALLIC']); }
	if(!empty($item['MP3_INTERFACE'])) {add_post_meta($post_id, 'MP3_INTERFACE', $item['MP3_INTERFACE']); }
	if(!empty($item['MULTIFUNCTIONAL_WHEEL'])) {add_post_meta($post_id, 'MULTIFUNCTIONAL_WHEEL', $item['MULTIFUNCTIONAL_WHEEL']); }
	if(!empty($item['NAVIGATION_SYSTEM'])) {add_post_meta($post_id, 'NAVIGATION_SYSTEM', $item['NAVIGATION_SYSTEM']); }
	if(!empty($item['NONSMOKER_VEHICLE'])) {add_post_meta($post_id, 'NONSMOKER_VEHICLE', $item['NONSMOKER_VEHICLE']); }
	if(!empty($item['ON_BOARD_COMPUTER'])) {add_post_meta($post_id, 'ON_BOARD_COMPUTER', $item['ON_BOARD_COMPUTER']); }
	if(!empty($item['PANORAMIC_GLASS_ROOF'])) {add_post_meta($post_id, 'PANORAMIC_GLASS_ROOF', $item['PANORAMIC_GLASS_ROOF']); }
	if(!empty($item['PARKING_SENSORS'])) {add_post_meta($post_id, 'PARKING_SENSORS', $item['PARKING_SENSORS']); }
	if(!empty($item['PARTICULATE_FILTER_DIESEL'])) {add_post_meta($post_id, 'PARTICULATE_FILTER_DIESEL', $item['PARTICULATE_FILTER_DIESEL']); }
	if(!empty($item['PERFORMANCE_HANDLING_SYSTEM'])) {add_post_meta($post_id, 'PERFORMANCE_HANDLING_SYSTEM', $item['PERFORMANCE_HANDLING_SYSTEM']); }
	if(!empty($item['POWER_ASSISTED_STEERING'])) {add_post_meta($post_id, 'POWER_ASSISTED_STEERING', $item['POWER_ASSISTED_STEERING']); }
	if(!empty($item['ROOF_RAILS'])) {add_post_meta($post_id, 'ROOF_RAILS', $item['ROOF_RAILS']); }
	if(!empty($item['SKI_BAG'])) {add_post_meta($post_id, 'SKI_BAG', $item['SKI_BAG']); }
	if(!empty($item['SPORT_PACKAGE'])) {add_post_meta($post_id, 'SPORT_PACKAGE', $item['SPORT_PACKAGE']); }
	if(!empty($item['SPORT_SEATS'])) {add_post_meta($post_id, 'SPORT_SEATS', $item['SPORT_SEATS']); }
	if(!empty($item['START_STOP_SYSTEM'])) {add_post_meta($post_id, 'START_STOP_SYSTEM', $item['START_STOP_SYSTEM']); }
	if(!empty($item['SUNROOF'])) {add_post_meta($post_id, 'SUNROOF', $item['SUNROOF']); }
	if(!empty($item['TAXI'])) {add_post_meta($post_id, 'TAXI', $item['TAXI']); }
	if(!empty($item['TRACTION_CONTROL_SYSTEM'])) {add_post_meta($post_id, 'TRACTION_CONTROL_SYSTEM', $item['TRACTION_CONTROL_SYSTEM']); }
	if(!empty($item['TRAILER_COUPLING'])) {add_post_meta($post_id, 'TRAILER_COUPLING', $item['TRAILER_COUPLING']); }
	if(!empty($item['TUNER'])) {add_post_meta($post_id, 'TUNER', $item['TUNER']); }
	if(!empty($item['VEGETABLEOILFUEL_SUITABLE'])) {add_post_meta($post_id, 'VEGETABLEOILFUEL_SUITABLE', $item['VEGETABLEOILFUEL_SUITABLE']); }
	if(!empty($item['WARRANTY'])) {add_post_meta($post_id, 'WARRANTY', $item['WARRANTY']); }
	if(!empty($item['XENON_HEADLIGHTS'])) {add_post_meta($post_id, 'XENON_HEADLIGHTS', $item['XENON_HEADLIGHTS']); }
	if(!empty($item['FOUR_WHEEL_DRIVE'])) {add_post_meta($post_id, 'FOUR_WHEEL_DRIVE', $item['FOUR_WHEEL_DRIVE']); }
	if(!empty($item['DISABLED_ACCESSIBLE'])) {add_post_meta($post_id, 'DISABLED_ACCESSIBLE', $item['DISABLED_ACCESSIBLE']); }
	if(!empty($item['climatisation'])) {add_post_meta($post_id, 'climatisation', $item['climatisation']); }
	if(!empty($item['schwacke-code'])) {add_post_meta($post_id, 'schwacke-code', $item['schwacke-code']); }
	if(!empty($item['enkv-compliant'])) {add_post_meta($post_id, 'enkv-compliant', $item['enkv-compliant']); }
	if(!empty($item['description'])) {add_post_meta($post_id, 'description', $item['description']); }
	if(!empty($item['included-delivery-costs'])) {add_post_meta($post_id, 'included-delivery-costs', $item['included-delivery-costs']); }
	if(!empty($item['exhaust-inspection'])) {add_post_meta($post_id, 'exhaust-inspection', $item['exhaust-inspection']); }
	if(!empty($item['operating-hours'])) {add_post_meta($post_id, 'operating-hours', $item['operating-hours']); }
	if(!empty($item['installation-height'])) {add_post_meta($post_id, 'installation-height', $item['installation-height']); }
	if(!empty($item['lifting-capacity'])) {add_post_meta($post_id, 'lifting-capacity', $item['lifting-capacity']); }
	if(!empty($item['lifting-height'])) {add_post_meta($post_id, 'lifting-height', $item['lifting-height']); }
	if(!empty($item['driving-mode'])) {add_post_meta($post_id, 'driving-mode', $item['driving-mode']); }
	if(!empty($item['driving-cab'])) {add_post_meta($post_id, 'driving-cab', $item['driving-cab']); }
	if(!empty($item['loading-space-length'])) {add_post_meta($post_id, 'loading-space-length', $item['loading-space-length']); }
	if(!empty($item['loading-space-height'])) {add_post_meta($post_id, 'loading-space-height', $item['loading-space-height']); }
	if(!empty($item['loading-space-width'])) {add_post_meta($post_id, 'loading-space-width', $item['loading-space-width']); }
	if(!empty($item['countryVersion'])) {add_post_meta($post_id, 'country-version', $item['countryVersion']); }
	if(!empty($item['videoUrl'])) {add_post_meta($post_id, 'videoUrl', $item['videoUrl']); }
	if(!empty($item['parking-assistants'])) {add_post_meta($post_id, 'parking-assistants', $item['parking-assistants']); }
	if(!empty($item['price_dropdown'])) { add_post_meta($post_id, 'price_dropdown', $item['price_dropdown']); }
	// wltp Data
	//  Combined fuel consumption for all nonelectric vehicles, optional for plugin hybrids, number in l/100km (natural gas (CNG) in kg/100km)
	if(!empty($item['wltp-consumption-fuel-combined'])) { add_post_meta($post_id, 'wltp-consumption-fuel-combined', $item['wltp-consumption-fuel-combined']); }
	//  Amount of carbon dioxide emissions in g/km for all vehicles, optional for plugin hybrids.
	if(!empty($item['wltp-co2-emission-combined'])) { add_post_meta($post_id, 'wltp-co2-emission-combined', $item['wltp-co2-emission-combined']); }
	// Combined power consumption for electric vehicles in in kWh/100km
	if(!empty($item['wltp-consumption-power-combined'])) { add_post_meta($post_id, 'wltp-consumption-power-combined', $item['wltp-consumption-power-combined']); }
	// Electric Range for plugin hybrids and electric vehicles in km
	if(!empty($item['wltp-electric-range'])) { add_post_meta($post_id, 'wltp-electric-range', $item['wltp-electric-range']); }
	// Weighted combined fuel consumption for plugin hybrids
	if(!empty($item['wltp-consumption-fuel-combined-weighted'])) { add_post_meta($post_id, 'wltp-consumption-fuel-combined-weighted', $item['wltp-consumption-fuel-combined-weighted']); }
	// Weighted combined power consumption for plugin hybrids in kWh/100km
	if(!empty($item['wltp-consumption-power-combined-weighted'])) { add_post_meta($post_id, 'wltp-consumption-power-combined-weighted', $item['wltp-consumption-power-combined-weighted']); }
	// Weighted amount of carbon dioxide emissions in g/km for plugin hybrids
	if(!empty($item['wltp-co2-emission-combined-weighted'])) { add_post_meta($post_id, 'wltp-co2-emission-combined-weighted', $item['wltp-co2-emission-combined-weighted']); }

	add_post_meta($post_id, 'is_finished', '1');

	return $post_id;
}

function updateTemporaryFields($post_id, $metaValues)
{

	// Available from: 07.01.2019 or Sofort
	if (!empty($metavalues[available_from]) && $metavalues[available_from] != "Sofort") {
		$availableFrom = new DateTime($metavalues[available_from]);
		$now = new DateTime();
		if ($availableFrom < $now) {
			$mob_data['available-from'] = "Sofort";
		}
		else {
			$mob_data['available-from'] = $availableFrom->format('d.m.Y');
		}
	}
}

do_action( 'kfz_web_meta' );

// function import_post_image($post_id, $image_url, $thumbnail = false)
// {
// 	$upload_dir = wp_upload_dir();
// 	$image_data = file_get_contents($image_url);
// 	$filename = uniqid($post_id . '-') . basename($image_url);
// 	if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
// 	else $file = $upload_dir['subdir'] . '/' . $filename;
// 	file_put_contents($file, $image_data);
// 	$wp_filetype = wp_check_filetype($filename, null);
// 	$attachment = array(
// 		'post_mime_type' => $wp_filetype['type'],
// 		'post_title' => sanitize_file_name($filename) ,
// 		'post_content' => '',
// 		'post_status' => 'inherit'
// 	);
// 	$attach_id = wp_insert_attachment($attachment, $file, $post_id);
// 	require_once (ABSPATH . 'wp-admin/includes/image.php');
// 	$attach_data = wp_generate_attachment_metadata($attach_id, $file);
// 	// Generate thumbnails and different sizes of images.
// 	wp_update_attachment_metadata($attach_id, $attach_data);
// 	if ($thumbnail) {
// 		set_post_thumbnail($post_id, $attach_id);
// 	}
// 	return $attach_data;
// }

function import_post_image($post_id, $image_url, $thumbnail = false)
{
    $re = '/\[0]\s\=\>\s/m';
    $str = '[0] => https://img.classistatic.de/api/v1/mo-prod/images/a4/a44c541e-fcac-48fc-8974-4fb28782ec52?rule=mo-640.jpg';
    $subst = '';
    $image_url = preg_replace($re, $subst, $image_url);
  
    $upload_dir = wp_upload_dir();
    $url = $image_url;
    $image_data = file_get_contents($image_url);
    $filename = uniqid($post_id . '-') . basename($image_url);
    $re = '/\?|\=/m';
    $str = $filename;
    $subst = '-';
    $filename = preg_replace($re, $subst, $str);
    if (wp_mkdir_p($upload_dir['path']))
    {$file = $upload_dir['path'] . '/' . $filename;}
    else $file = $upload_dir['subdir'] . '/' . $filename;
    $isFilePut = file_put_contents($file, $image_data);
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename) ,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once (ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    // Generate thumbnails and different sizes of images.
    wp_update_attachment_metadata($attach_id, $attach_data);
    if ($thumbnail) {
        set_post_thumbnail($post_id, $attach_id);
    }
    return $attach_data;
}

/**
 * Deletes all posts from 'fahrzeuge'.
 */
function mob_deleteAllPosts()
{
	global $mob_data;
	set_time_limit(0);
	$query = new WP_Query(array(
		'post_type' => $mob_data['customType'],
		'post_status' => 'publish',
		'numberposts' => - 1,
		'posts_per_page' => - 1
	));
	$ids = array();
	foreach($query->posts as $post) {
		mob_delete_attachment($post->ID);
		wp_delete_post($post->ID, true);
	}
}

/**
 *
 *
 *
 *
 * Deletes post attachments (images).
 *
 *
 *
 *
 *
 * @param unknown $post_id
 *
 *
 *
 */

function mob_delete_attachment($post_id)
{
	$args = array(
		'post_type' => 'attachment',
		'numberposts' => - 1,
		'post_status' => null,
		'post_parent' => $post_id
	);
	$attachments = get_posts($args);
	if ($attachments) {
		foreach($attachments as $attachment) {
			if (wp_delete_attachment($attachment->ID, true)) {
			}
		}
	}
}

// On deactivation, remove all functions from the scheduled action hook. // ? --bth 2014-11-11

register_deactivation_hook(__FILE__, 'mob_deactivation');

function mob_deactivation()
{
	wp_clear_scheduled_hook('mob_periodic_event_hook');
}

function more_fields($resetIndex = false)
{ // Name? --bth 20
	static $attachments, $index = 0;
	// To reset the index.
	if ($resetIndex) {
		$index = 0;
		return;
	}

	$uploadDir = wp_upload_dir();
	$attachURL = $uploadDir['baseurl'];
	$subDir = $uploadDir['subdir'];
	if (empty($attachments)) {
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => - 1,
			'post_status' => null,
			'order' => 'ASC',
			'orderby' => 'modified',
			'post_parent' => get_the_ID()
		);
		$attachments = get_posts($args);
	}

	if (isset($attachments[$index])) {
		$metaData = wp_get_attachment_metadata($attachments[$index]->ID);
		$index++;
		if (!empty($metaData)) {
			$metaData['file'] = $attachURL . '/' . $metaData['file'];
			$metaData['sizes']['thumbnail']['file'] = $attachURL . $subDir . '/' . $metaData['sizes']['thumbnail']['file'];
			return $metaData;
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}

/**
 * Loads the admin settings page and handlers used with it.
 */
/*

* Find out why license is checked here. --bth 2014-11-11

*/

// load admin settings page and handlers used with it

// global $wp_version;
// $license = trim(get_option('edd_sample_license_key'));
// $api_params = array(
// 	'edd_action' => 'check_license',
// 	'license' => $license,
// 	'item_name' => urlencode(KFZ_WEB_ITEM_NAME)
// );

// Call the custom API.



//
// $response = wp_remote_get(add_query_arg($api_params, KFZ_WEB_STORE) , array(
// 	'timeout' => 15,
// 	'sslverify' => false
// ));



// if (is_wp_error($response)) return false;

// $license_data = json_decode(wp_remote_retrieve_body($response));

include_once dirname(__FILE__) . '/admin.php';

include_once dirname(__FILE__) . '/license.php';


// if ($license_data->license == 'valid') {

// 	include_once dirname(__FILE__) . '/license.php';

// 	// include_once dirname(__FILE__) . '/widget.php';

// 	// this license is still valid

// }
// else {
// 	include_once dirname(__FILE__) . '/license.php';
// 	// this license is no longer valid
// }
/*



* ??? --bth 2014-11-11



*/



// function start_el(&$output, $category, $depth, $args)

// {

// 	$pad = str_repeat(' ', $depth * 3);

// 	$cat_name = apply_filters('list_cats', $category->name, $category);

// 	$output.= "\t<option class=\"level-$depth\" value=\"" . $category->slug . "\"";

// 	if ($category->term_id == $args['selected']) $output.= ' selected="selected"';

// 	$output.= '>';

// 	$output.= $pad . $cat_name;

// 	if ($args['show_count']) $output.= '  (' . $category->count . ')';

// 	if ($args['show_last_update']) {

// 		$format = 'Y-m-d';

// 		$output.= '  ' . gmdate($format, $category->last_update_timestamp);

// 	}



// 	$output.= "</option>\n";

// }



/*



* In order to list taxonomies.



*/



// Get taxonomies terms links.



// function custom_taxonomies_terms_links()

// {



// 	// get post by post id



// 	$post = get_post($post->ID);



// 	// get post type by post



// 	$post_type = $post->post_type;



// 	// get post type taxonomies



// 	$taxonomies = get_object_taxonomies($post_type, 'fahrzeuge');

// 	$out = array();

// 	foreach($taxonomies as $taxonomy_slug => $taxonomy) {



// 		// get the terms related to post



// 		$terms = get_the_terms($post->ID, $taxonomy_slug);

// 		if (!empty($terms)) {

// 			$out[] = "<div class='mobilede-taxlist'><p>" . $taxonomy->label . ":&nbsp</p>\n";

// 			foreach($terms as $term) {

// 				$out[] = '  <a href="' . get_term_link($term->slug, $taxonomy_slug) . '">' . $term->name . "</a><br />\n";

// 			}



// 			$out[] = "</div>\n";

// 		}

// 	}



// 	return implode('', $out);

// }



// include CPT in query



add_filter('pre_get_posts', 'query_post_type');



function query_post_type($query)

{

	if (is_category() || is_tag()) {

		$post_type = get_query_var('fahrzeuge');

		if ($post_type) $post_type = $post_type;

		else $post_type = array(

			'post',

			'fahrzeuge',

			'nav_menu_item'

		);

		$query->set('post_type', $post_type);

		return $query;

	}

}



function mob_search_result_init()

{

	global $mob_data;



	// create custom type for search result



	$posts_labels = array(

		'name' => _x('Fahrzeuge', 'post type general name') ,

		'singular_name' => _x('Fahrzeuge', 'post type singular name') ,

		'add_new' => _x('Neues Fahrzeug', 'vehicles') ,

		'add_new_item' => __('Neues Fahrzeug hinzufügen') ,

		'edit_item' => __('Fahrzeuge bearbeiten') ,

		'new_item' => __('Neues Fahrzeug') ,

		'view_item' => __('Fahrzeugdetails ansehen') ,

		'search_items' => __('Fahrzeuge durchsuchen') ,

		'not_found' => __('Kein Fahrzeug gefunden') ,

		'not_found_in_trash' => __('Kein Fahrzeug im Papierkorb gefunden') ,

		'_builtin' => false,

		'parent_item_colon' => '',

		'menu_name' => 'Fahrzeuge'

	);



	$posts_args = array(

		'labels' => $posts_labels,

		'public' => true,

		'publicly_queryable' => true,

		'exclude_from_search' => false,

		'show_ui' => true,

		'show_in_menu' => true,

		'query_var' => true,

		'rewrite' => array(

			'slug' => $mob_data['customType'],

			'with_front' => true

		) ,

		'capability_type' => 'post',

		'has_archive' => true,

		'hierarchical' => true,

		'menu_position' => 8,

		'menu_icon' => 'data:image/svg+xml;base64,PHN2ZyBpZD0iRWJlbmVfMSIgZGF0YS1uYW1lPSJFYmVuZSAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxOCAxOCI+PGRlZnM+PHN0eWxlPi5jbHMtMXtmaWxsOiNmZmY7ZmlsbC1vcGFjaXR5OjAuODU7fTwvc3R5bGU+PC9kZWZzPjx0aXRsZT5aZWljaGVuZmzDpGNoZSAxPC90aXRsZT48ZyBpZD0ibGF5ZXIxIj48cGF0aCBpZD0icGF0aDkzNDciIGNsYXNzPSJjbHMtMSIgZD0iTTUuNDcsMi4zMUExLjY4LDEuNjgsMCwwLDAsMy43MiwzLjM5TDIuMzEsNi45NEExLjg3LDEuODcsMCwwLDAsLjgxLDguNnY0LjY2SDIuMTd2MS41N2MtLjA3LDEuNDksMi4zNiwxLjU1LDIuNDIsMGwwLTEuNTRoOC43OGwwLDEuNTRjLjA1LDEuNTcsMi40OCwxLjUxLDIuNDIsMFYxMy4yNWgxLjM1VjguNmExLjg3LDEuODcsMCwwLDAtMS41LTEuNjVMMTQuMjgsMy4zOWExLjY4LDEuNjgsMCwwLDAtMS43Ni0xLjA3Wm0uMTYsMS4yM2guMTlsNi4zNywwYy41OCwwLC44MywwLDEuMDguNTZsMSwyLjc2SDMuNzJsMS0yLjY4YS44LjgsMCwwLDEsLjkxLS42NFpNMy40Miw4LjI2QTEuMTUsMS4xNSwwLDEsMSwyLjI3LDkuNDEsMS4xNSwxLjE1LDAsMCwxLDMuNDIsOC4yNlptMTEuMjMsMEExLjE1LDEuMTUsMCwxLDEsMTMuNSw5LjQxLDEuMTUsMS4xNSwwLDAsMSwxNC42NSw4LjI2Wm0tLjc5LTEuNTJaIi8+PC9nPjwvc3ZnPg==',

		'supports' => array(

			'title',

			'editor',

			'thumbnail',

			'excerpt'

		) ,

		'taxonomies' => array(

			'category'

			// 'post_tag',

			// 'marke',

			// 'modell',

			// 'preis'

		)

	);

	register_post_type($mob_data['customType'], $posts_args);

}



// ********************************************************************************************************

// ********************************************************************************************************

// **************************** Register custom taxonomy for custom post type *****************************

// Register Custom Taxonomy



function vehicle_taxonomy()

{

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** MARKE *******************************

	 */

	$labels = array(

		'name' => _x('Marke', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Marke', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Marken', 'text_domain') ,

		'all_items' => __('Alle Marken', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neue Marke', 'text_domain') ,

		'add_new_item' => __('Neue Marke hinzufügen', 'text_domain') ,

		'edit_item' => __('Marke bearbeiten', 'text_domain') ,

		'update_item' => __('Marke aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Marken durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Marken durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Marken hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Marken zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('marke', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** MODELL *******************************

	 */

	$labels = array(

		'name' => _x('Modell', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Modell', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Modell', 'text_domain') ,

		'all_items' => __('Alle Modelle', 'text_domain') ,

		'parent_item' => __('Parent Marke', 'text_domain') ,

		'parent_item_colon' => __('Parent Marke:', 'text_domain') ,

		'new_item_name' => __('Neues Modell', 'text_domain') ,

		'add_new_item' => __('Neues Modell hinzufügen', 'text_domain') ,

		'edit_item' => __('Modell bearbeiten', 'text_domain') ,

		'update_item' => __('Modell aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Modelle durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Modelle durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Modell hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Modelle zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('modell', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** ZUSTAND *******************************

	 */

	$labels = array(

		'name' => _x('Zustand', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Zustand', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Zustand', 'text_domain') ,

		'all_items' => __('Alle Zustände', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Zustand', 'text_domain') ,

		'add_new_item' => __('Neuen zustand hinzufügen', 'text_domain') ,

		'edit_item' => __('Zustand bearbeiten', 'text_domain') ,

		'update_item' => __('Zustand aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Zustände durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Zustände durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Zustand hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Zustände zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('zustand', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** PREIS *******************************

	 */

	$labels = array(

		'name' => _x('Preis', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Preis', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Preis', 'text_domain') ,

		'all_items' => __('Alle Preise', 'text_domain') ,

		'parent_item' => __('Parent Modell', 'text_domain') ,

		'parent_item_colon' => __('Parent Modell:', 'text_domain') ,

		'new_item_name' => __('Neuer Preis', 'text_domain') ,

		'add_new_item' => __('Neuen Preis hinzufügen', 'text_domain') ,

		'edit_item' => __('Preis bearbeiten', 'text_domain') ,

		'update_item' => __('Preis aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Preise durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Preise durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Preis hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Preise zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('preis', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** KLASSE *******************************

	 */

	$labels = array(

		'name' => _x('Klasse', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Klasse', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Klasse', 'text_domain') ,

		'all_items' => __('Alle Klassen', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neue Klasse', 'text_domain') ,

		'add_new_item' => __('Neue Klasse hinzufügen', 'text_domain') ,

		'edit_item' => __('Klasse bearbeiten', 'text_domain') ,

		'update_item' => __('Klasse aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Klassen durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Klassen durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Klasse hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Klassen zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('klasse', array(

		'fahrzeuge'

	) , $args);

	/**

	 * **********************************************************************

	 *

	 *

	 *

	 * *************************** KRAFTSTOFF *******************************

	 */

	$labels = array(

		'name' => _x('Kraftstoffart', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Kraftstoffart', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Kraftstoffart', 'text_domain') ,

		'all_items' => __('Alle Kraftstoffarten', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neue Kraftstoffart', 'text_domain') ,

		'add_new_item' => __('Neue Kraftstoffart hinzufügen', 'text_domain') ,

		'edit_item' => __('Kraftstoffart bearbeiten', 'text_domain') ,

		'update_item' => __('Kraftstoffart aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Kraftstoffarten durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Kraftstoffarten durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Kraftstoffart hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Kraftstoffarten zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('kraftstoffart', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** GETRIEBE *******************************

	 */

	$labels = array(

		'name' => _x('Getriebe', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Getriebe', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Getriebe', 'text_domain') ,

		'all_items' => __('Alle Getriebe', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neues Getriebe', 'text_domain') ,

		'add_new_item' => __('Neues Getriebe hinzufügen', 'text_domain') ,

		'edit_item' => __('Getriebe bearbeiten', 'text_domain') ,

		'update_item' => __('Getriebe aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Getriebe durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Getriebe durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Getriebe hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Getriebe zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('getriebe', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** ERSTZULASSUNG *******************************

	 */

	$labels = array(

		'name' => _x('Erstzulassung', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Erstzulassung', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Erstzulassung', 'text_domain') ,

		'all_items' => __('Alle Erstzulassungen', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neue Erstzulassung', 'text_domain') ,

		'add_new_item' => __('Neue Erstzulassung hinzufügen', 'text_domain') ,

		'edit_item' => __('Erstzulassung bearbeiten', 'text_domain') ,

		'update_item' => __('Erstzulassung aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Erstzulassungen durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Erstzulassung durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Erstzulassung hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Erstzulassungen zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('erstzulassung', array(

		'fahrzeuge'

	) , $args);

	/**

	 * ****************************************************************

	 *

	 *

	 *

	 * *************************** Standort *******************************

	 */

	$labels = array(

		'name' => _x('Standort', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Standort', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Standort', 'text_domain') ,

		'all_items' => __('Alle Standorte', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Standort', 'text_domain') ,

		'add_new_item' => __('Neuen Standort hinzufÃ¼gen', 'text_domain') ,

		'edit_item' => __('Standort bearbeiten', 'text_domain') ,

		'update_item' => __('Standort aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Standort durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Standorte durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Standorte hinzufÃ¼gen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am hÃ¤ufigsten genutzte Standorte zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('standort', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Beschreibung', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Beschreibung', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Beschreibung', 'text_domain') ,

		'all_items' => __('Alle Beschreibungen', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neue Beschreibung', 'text_domain') ,

		'add_new_item' => __('Neue Beschreibung hinzufügen', 'text_domain') ,

		'edit_item' => __('Beschreibung bearbeiten', 'text_domain') ,

		'update_item' => __('Beschreibung aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Beschreibungen durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Beschreibungen durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Beschreibungen hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Beschreibungen zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('beschreibung', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Schaden', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Schaden', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Schaden', 'text_domain') ,

		'all_items' => __('Alle Schaden', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Schaden', 'text_domain') ,

		'add_new_item' => __('Neuen Schaden hinzufügen', 'text_domain') ,

		'edit_item' => __('Schaden bearbeiten', 'text_domain') ,

		'update_item' => __('Schaden aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Schaden durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Schaden durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Schaden hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Schaden zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('schaden', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Emissionsklasse', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Emissionsklasse', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Emissionsklasse', 'text_domain') ,

		'all_items' => __('Alle Emissionsklasse', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Emissionsklasse', 'text_domain') ,

		'add_new_item' => __('Neuen Emissionsklasse hinzufügen', 'text_domain') ,

		'edit_item' => __('Emissionsklasse bearbeiten', 'text_domain') ,

		'update_item' => __('Emissionsklasse aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Emissionsklasse durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Emissionsklasse durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Emissionsklasse hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Emissionsklasse zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('emissionsklasse', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('CO2 Emission', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('CO2 Emission', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('CO2 Emission', 'text_domain') ,

		'all_items' => __('Alle CO2 Emission', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer CO2 Emission', 'text_domain') ,

		'add_new_item' => __('Neuen CO2 Emission hinzufügen', 'text_domain') ,

		'edit_item' => __('CO2 Emission bearbeiten', 'text_domain') ,

		'update_item' => __('CO2 Emission aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('CO2 Emission durch Kommas trennen', 'text_domain') ,

		'search_items' => __('CO2 Emission durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('CO2 Emission hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte CO2 Emission zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('co2_emission', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Verbrauch Innerorts', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Verbrauch Innerorts', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Verbrauch Innerorts', 'text_domain') ,

		'all_items' => __('Alle Verbrauch Innerorts', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Verbrauch Innerorts', 'text_domain') ,

		'add_new_item' => __('Neuen Verbrauch Innerorts hinzufügen', 'text_domain') ,

		'edit_item' => __('Verbrauch Innerorts bearbeiten', 'text_domain') ,

		'update_item' => __('Verbrauch Innerorts aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Verbrauch Innerorts durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Verbrauch Innerorts durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Verbrauch Innerorts hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Verbrauch Innerorts zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('verbrauch_innerorts', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Verbrauch Ausserorts', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Verbrauch Ausserorts', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Verbrauch Ausserorts', 'text_domain') ,

		'all_items' => __('Alle Verbrauch Ausserorts', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Verbrauch Ausserorts', 'text_domain') ,

		'add_new_item' => __('Neuen Verbrauch Ausserorts hinzufügen', 'text_domain') ,

		'edit_item' => __('Verbrauch Ausserorts bearbeiten', 'text_domain') ,

		'update_item' => __('Verbrauch Ausserorts aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Verbrauch Ausserorts durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Verbrauch Ausserorts durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Verbrauch Ausserorts hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Verbrauch Ausserorts zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('verbrauch_ausserorts', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Verbrauch Kombiniert', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Verbrauch Kombiniert', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Verbrauch Kombiniert', 'text_domain') ,

		'all_items' => __('Alle Verbrauch Kombiniert', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Verbrauch Kombiniert', 'text_domain') ,

		'add_new_item' => __('Neuen Verbrauch Kombiniert hinzufügen', 'text_domain') ,

		'edit_item' => __('Verbrauch Kombiniert bearbeiten', 'text_domain') ,

		'update_item' => __('Verbrauch Kombiniert aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Verbrauch Kombiniert durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Verbrauch Kombiniert durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Verbrauch Kombiniert hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Verbrauch Kombiniert zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('verbrauch_kombiniert', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Aussenfarbe', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Aussenfarbe', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Aussenfarbe', 'text_domain') ,

		'all_items' => __('Alle Aussenfarbe', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Aussenfarbe', 'text_domain') ,

		'add_new_item' => __('Neuen Aussenfarbe hinzufügen', 'text_domain') ,

		'edit_item' => __('Aussenfarbe bearbeiten', 'text_domain') ,

		'update_item' => __('Aussenfarbe aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Aussenfarbe durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Aussenfarbe durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Aussenfarbe hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Aussenfarbe zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('aussenfarbe', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Hubraum', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Hubraum', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Hubraum', 'text_domain') ,

		'all_items' => __('Alle Hubraum', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Hubraum', 'text_domain') ,

		'add_new_item' => __('Neuen Hubraum hinzufügen', 'text_domain') ,

		'edit_item' => __('Hubraum bearbeiten', 'text_domain') ,

		'update_item' => __('Hubraum aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Hubraum durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Hubraum durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Hubraum hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Hubraum zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('hubraum', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Nächste HU', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Nächste HU', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Nächste HU', 'text_domain') ,

		'all_items' => __('Alle Nächste HU', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Nächste HU', 'text_domain') ,

		'add_new_item' => __('Neuen Nächste HU hinzufügen', 'text_domain') ,

		'edit_item' => __('Nächste HU bearbeiten', 'text_domain') ,

		'update_item' => __('Nächste HU aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Nächste HU durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Nächste HU durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Nächste HU hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Nächste HU zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('naechste_hu', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Ausstattung', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Ausstattung', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Ausstattung', 'text_domain') ,

		'all_items' => __('Alle Ausstattung', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Ausstattung', 'text_domain') ,

		'add_new_item' => __('Neuen Ausstattung hinzufügen', 'text_domain') ,

		'edit_item' => __('Ausstattung bearbeiten', 'text_domain') ,

		'update_item' => __('Ausstattung aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Ausstattung durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Ausstattung durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Ausstattung hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Ausstattung zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('ausstattung', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Kilometer', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Kilometer', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Kilometer', 'text_domain') ,

		'all_items' => __('Alle Kilometer', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Kilometer', 'text_domain') ,

		'add_new_item' => __('Neuen Kilometer hinzufügen', 'text_domain') ,

		'edit_item' => __('Kilometer bearbeiten', 'text_domain') ,

		'update_item' => __('Kilometer aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Kilometer durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Kilometer durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Kilometer hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Kilometer zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('kilometer', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Kilometer Unformatiert', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Kilometer Unformatiert', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Kilometer Unformatiert', 'text_domain') ,

		'all_items' => __('Alle Kilometer Unformatiert', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Kilometer Unformatiert', 'text_domain') ,

		'add_new_item' => __('Neuen Kilometer Unformatiert hinzufügen', 'text_domain') ,

		'edit_item' => __('Kilometer Unformatiert bearbeiten', 'text_domain') ,

		'update_item' => __('Kilometer Unformatiert aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Kilometer Unformatiert durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Kilometer Unformatiert durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Kilometer Unformatiert hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Kilometer Unformatiert zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('kilometer_unformatiert', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Kilometer Dropdown', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Kilometer Dropdown', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Kilometer Dropdown', 'text_domain') ,

		'all_items' => __('Alle Kilometer Dropdown', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Kilometer Dropdown', 'text_domain') ,

		'add_new_item' => __('Neuen Kilometer Dropdown hinzufügen', 'text_domain') ,

		'edit_item' => __('Kilometer Dropdown bearbeiten', 'text_domain') ,

		'update_item' => __('Kilometer Dropdown aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Kilometer Dropdown durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Kilometer Dropdown durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Kilometer Dropdown hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Kilometer Dropdown zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('kilometer_dropdown', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Preis Unformatiert', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Preis Unformatiert', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Preis Unformatiert', 'text_domain') ,

		'all_items' => __('Alle Preis Unformatiert', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Preis Unformatiert', 'text_domain') ,

		'add_new_item' => __('Neuen Preis Unformatiert hinzufügen', 'text_domain') ,

		'edit_item' => __('Preis Unformatiert bearbeiten', 'text_domain') ,

		'update_item' => __('Preis Unformatiert aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Preis Unformatiert durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Preis Unformatiert durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Preis Unformatiert hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Preis Unformatiert zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('preis_unformatiert', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Ladekapazität', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Ladekapazität', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Ladekapazität', 'text_domain') ,

		'all_items' => __('Alle Ladekapazität', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Ladekapazität', 'text_domain') ,

		'add_new_item' => __('Neuen Ladekapazität hinzufügen', 'text_domain') ,

		'edit_item' => __('Ladekapazität bearbeiten', 'text_domain') ,

		'update_item' => __('Ladekapazität aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Ladekapazität durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Ladekapazität durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Ladekapazität hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Ladekapazität zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('ladekapazitaet', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Baujahr', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Baujahr', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Baujahr', 'text_domain') ,

		'all_items' => __('Alle Baujahr', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Baujahr', 'text_domain') ,

		'add_new_item' => __('Neuen Baujahr hinzufügen', 'text_domain') ,

		'edit_item' => __('Baujahr bearbeiten', 'text_domain') ,

		'update_item' => __('Baujahr aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Baujahr durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Baujahr durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Baujahr hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Baujahr zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => true,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('baujahr', array(

		'fahrzeuge'

	) , $args);

	$labels = array(

		'name' => _x('Anzahl Schlafplätze', 'Taxonomy General Name', 'text_domain') ,

		'singular_name' => _x('Anzahl Schlafplätze', 'Taxonomy Singular Name', 'text_domain') ,

		'menu_name' => __('Anzahl Schlafplätze', 'text_domain') ,

		'all_items' => __('Alle Anzahl Schlafplätze', 'text_domain') ,

		'parent_item' => __('Parent Item', 'text_domain') ,

		'parent_item_colon' => __('Parent Item:', 'text_domain') ,

		'new_item_name' => __('Neuer Anzahl Schlafplätze', 'text_domain') ,

		'add_new_item' => __('Neuen Anzahl Schlafplätze hinzufügen', 'text_domain') ,

		'edit_item' => __('Anzahl Schlafplätze bearbeiten', 'text_domain') ,

		'update_item' => __('Anzahl Schlafplätze aktualisieren', 'text_domain') ,

		'separate_items_with_commas' => __('Anzahl Schlafplätze durch Kommas trennen', 'text_domain') ,

		'search_items' => __('Anzahl Schlafplätze durchsuchen', 'text_domain') ,

		'add_or_remove_items' => __('Anzahl Schlafplätze hinzufügen/entfernen', 'text_domain') ,

		'choose_from_most_used' => __('Am häufigsten genutzte Anzahl Schlafplätze zeigen', 'text_domain') ,

		'not_found' => __('Nichts gefunden', 'text_domain')

	);

	$args = array(

		'labels' => $labels,

		'hierarchical' => true,

		'public' => true,

		'show_ui' => false,

		'show_admin_column' => false,

		'show_in_nav_menus' => false,

		'show_tagcloud' => false

	);

	register_taxonomy('baujahr', array(

		'fahrzeuge'

	) , $args);

}



function mob_clean(){

	global $mob_data;



	$args = array(

			'post_type' => $mob_data['customType'],

			'posts_per_page' => -1,

		);

		$query = new WP_Query($args);



		$adKeys = array();

		$postIds = array();

		$postIdsToDelete = array();

		log_me($adKeys);



		while ($query->have_posts()) {

			$query->the_post();

			$meta_values = get_post_meta( get_the_ID() );

			if(!isset($meta_values['is_finished']) && !isset($meta_values['is_finished'][0])){

					log_me("MISSING IS_FINISHED STUB DETECTED! " . get_the_ID());

					$postIdsToDelete[] = get_the_ID();

					log_me($postIdsToDelete);

			}

			if(!isset($meta_values['ad_key']) && !isset($meta_values['ad_key'][0])){

					log_me("MISSING AD_KEY STUB DETECTED! " . get_the_ID());

					$postIdsToDelete[] = get_the_ID();

					log_me($postIdsToDelete);

			}

			else {

					$adKey = $meta_values['ad_key'][0];



					if (in_array($adKey, $adKeys)){ // Compare to predecessors

						log_me("DUPLICATE DETECTED! " . $adKey);

						$postIdsToDelete[] = get_the_ID(); // Push post_id

					}

					else {

						$adKeys[] = $adKey; // Log ad_key

						$postIds[] = get_the_ID(); // Log post_id (same index)

					}

			}

		}

		if(!empty($postIdsToDelete)){

			removePostsbyIds($postIdsToDelete);

			log_me(count($postIdsToDelete) . " ERRONEOUS POSTS DELETED.");



		$query->reset();



}

		log_me('adKeys');

		log_me($adKeys);

		log_me('dieIDs');

		log_me($postIdsToDelete);

		// do_action('kfz_web_after_import');

}
