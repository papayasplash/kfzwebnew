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
    
    if (empty($options['mob_slider_option'])) {
        $options['mob_slider_option'] = 'yes';
    }
	
    if ($options['mob_slider_option'] == 'yes' && !is_admin()) {
        wp_enqueue_style('slider_cdn_css',  'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css');
        wp_enqueue_style('slider_cdn_css_theme',  'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css');
        wp_enqueue_script('slider_cdn_js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js', array('jquery'));
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
require_once (ABSPATH . 'wp-includes/class-wpdb.php');
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
    $vehicles = getVehiclesFromApi();
    if (empty($vehicles) || empty($vehicles['intern_adKeys'])) {
        // Handle error or absence of vehicles.
        return;
    }
    
    $apiAdKeys = $vehicles['intern_adKeys'];
    unset($vehicles['intern_adKeys']);
    
    $currentMeta = getCurrentMetaValues();
    if (empty($currentMeta) || empty($currentMeta['intern_mostRecentModificationDate'])) {
        mob_deleteAllPosts(); // Clear and re-import all when there's no meaningful meta.
        importVehicles($vehicles);
        return;
    }
    
    $mostRecentModificationDate = $currentMeta['intern_mostRecentModificationDate'];
    unset($currentMeta['intern_mostRecentModificationDate']);
    $wordpressAdKeys = $currentMeta['intern_adKeys'];
    unset($currentMeta['intern_adKeys']);
    
    $GLOBALS["mob_wordpressAdKeysCount"] = count($wordpressAdKeys);
    
    $adKeysOfSoldVehicles = array_diff($wordpressAdKeys, $apiAdKeys);
    $adKeysOfNewVehicles = array_diff($apiAdKeys, $wordpressAdKeys);
    $adKeysOfRemainingVehicles = array_intersect($apiAdKeys, $wordpressAdKeys);
    
    deleteByAdKeys($currentMeta, $adKeysOfSoldVehicles);
    
    $newVehicles = getVehiclesByAdkeys($vehicles, $adKeysOfNewVehicles);
    $remainingVehicles = getVehiclesByAdkeys($vehicles, $adKeysOfRemainingVehicles);
    
    if (!empty($newVehicles)) {
        importVehicles($newVehicles);
    }
    
    // Fetch modified vehicles only once instead of twice as in the original code.
    $modifiedVehicles = getVehiclesModifiedSince($remainingVehicles, $mostRecentModificationDate);
    if (!empty($modifiedVehicles) && !empty($modifiedVehicles['intern_adKeys'])) {
        deleteByAdKeys($currentMeta, $modifiedVehicles['intern_adKeys']);
        unset($modifiedVehicles['intern_adKeys']);
        importVehicles($modifiedVehicles);
    }
    mob_clean();
}
add_action('mob_cleanup', 'mob_clean');
function deleteByAdKeys($metaValues, $adKeys) {
    $filteredValues = array_filter($metaValues, function ($current) use ($adKeys) {
        return in_array($current['ad_key'], $adKeys);
    });
    $postIds = array_column($filteredValues, 'post_id');
    if (!empty($postIds)) {
        removePostsbyIds($postIds);
    }
}
function getVehiclesFromApi()
{
    set_time_limit(0);
    $options = get_option('MobileDE_option');
    $vehicles = array();
    foreach ($options['mob_username'] as $index => $username) {
        $mob_api = new mob_searchAPI("mob_url", $username, $options['mob_password'][$index], $options['mob_language']);
        $temp = $mob_api->getAllAds();
        if (!empty($temp)) {
            $vehicles = mergeVehicleData($vehicles, $temp);
            // ToDo: Queue Status Message for Backend
        } else {
            // ToDo: Handle error, log or display message.
        }
    }
    return $vehicles;
}
function mergeVehicleData($vehicles, $temp)
{
    if (isset($temp['intern_adKeys'])) {
        $tempInternAdKeys = isset($vehicles['intern_adKeys']) 
            ? array_merge($vehicles['intern_adKeys'], $temp['intern_adKeys']) 
            : $temp['intern_adKeys'];
    }
    $vehicles = array_merge($vehicles, $temp);
    if (isset($tempInternAdKeys)) {
        $vehicles['intern_adKeys'] = $tempInternAdKeys;
    }
    return $vehicles;
}
function getVehiclesByAdkeys($vehicles, $adKeys)
{
    return array_filter($vehicles, function($vehicle) use ($adKeys) {
        return in_array($vehicle['ad_key'], $adKeys);
    });
}
function importVehicles($vehicles)
{
    global $mob_data;
    $post_ids = array();
    // Fetch all existing vehicle ad_keys in one query.
    $existing_vehicles = get_posts(array(
        'post_type' => $mob_data['customType'],
        'fields' => 'ids', // Only get the post IDs to improve performance.
        'posts_per_page' => -1, // Get all posts.
        'meta_key' => 'ad_key',
        'meta_value' => array_column($vehicles, 'ad_key'), // Match against all incoming vehicle ad_keys.
        'meta_compare' => 'IN',
    ));
    
    // Create a map of ad_keys to post IDs for quick lookup.
    $existing_ad_keys = array();
    foreach ($existing_vehicles as $post_id) {
        $existing_ad_keys[get_post_meta($post_id, 'ad_key', true)] = $post_id;
    }
    $total_vehicles = count($vehicles);
    $vehicles_imported = 0;
	foreach ($vehicles as $vehicle) {
		if (!isset($existing_ad_keys[$vehicle['ad_key']])) {
			// Insert if not exists.
			$post_ids[] = writeIntoWp($vehicle);
			$vehicles_imported++;
			update_option('vehicle_import_progress', ($vehicles_imported / $total_vehicles) * 100);
		} else {
			// Update existing vehicle if data has changed.
			$existing_post_id = $existing_ad_keys[$vehicle['ad_key']];
			$existing_vehicle = get_post_meta($existing_post_id);
			
			if (array_diff_assoc($vehicle, $existing_vehicle)) {
				// Update the vehicle data if there are changes.
				writeIntoWp($vehicle, $existing_post_id);
			}
		}
	}
    return $post_ids;
}
add_action('wp_ajax_get_vehicle_import_progress', 'get_vehicle_import_progress_callback');

function get_vehicle_import_progress_callback() {
    $progress = get_option('vehicle_import_progress', 0); // Standardwert ist 0
    echo json_encode(array('progress' => $progress));
    wp_die();
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
function getVehiclesModifiedSince($vehicles, $modDate) {
    $temp = strtotime($modDate);
    $modifiedVehicles = ['intern_adKeys' => []]; // Initialize the intern_adKeys array from the beginning.
    foreach ($vehicles as $vehicle) {
        if (strtotime($vehicle['modification_date']) > $temp) {
            // Directly add the vehicle's ad_key to the 'intern_adKeys' array.
            $modifiedVehicles['intern_adKeys'][] = $vehicle['ad_key'];
            // Store the complete vehicle details excluding adding to 'intern_adKeys'.
            $vehicleWithoutAdKeys = $vehicle;
            unset($vehicleWithoutAdKeys['ad_key']); // Assuming 'ad_key' should not be with vehicle details, adjust if needed.
            $modifiedVehicles[] = $vehicleWithoutAdKeys;
        }
    }
    return $modifiedVehicles;
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
function getCurrentMetaValues() {
	$args = array(
		'post_type' => 'fahrzeuge',
		'order' => 'ASC',
		'orderby' => 'title',
		'posts_per_page' => '-1',
	);
	$query = new WP_Query($args);
	$currentMetaResult = array();
	$mostRecent = 0;
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$meta_values = get_post_meta(get_the_ID());
			if (isset($meta_values['ad_key'][0]) && isset($meta_values['modification_date'][0])) {
				$currentMetaResult[get_the_ID()] = [
					"ad_key" => $meta_values['ad_key'][0],
					"modification_date" => $meta_values['modification_date'][0],
					"post_id" => get_the_ID(),
				];
				$currentMetaResult['intern_adKeys'][] = $meta_values['ad_key'][0];
				$curDate = strtotime($meta_values['modification_date'][0]);
				if ($curDate > $mostRecent) {
					$mostRecent = $curDate;
					$currentMetaResult['intern_mostRecentModificationDate'] = (string)$meta_values['modification_date'][0];
				}
			} else {
				// Error handling for missing ad_key or modification_date.
			}
		}
		return $currentMetaResult;
	} else {
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
	
	if(!empty($item['ad_key'])) { $meta_data_to_update['vehicleListingID'] = $item['ad_key']; }
	update_post_meta($post_id, 'dataSource', 'mobile.de_api');
	if(!empty($item['newCars'])) { $meta_data_to_update['creation-date'] = $item['creation-date']; }
	if(!empty($item['class'])) { $meta_data_to_update['class'] = $item['class']; }
	if(!empty($item['brand'])) { $meta_data_to_update['make'] = $item['make']; } // Deprecated.
	if(!empty($item['make'])) { $meta_data_to_update['make'] = $item['make']; }
	if(!empty($item['model'])) { $meta_data_to_update['model'] = $item['model']; }
	if(!empty($item['model_variant'])) { $meta_data_to_update['model_variant'] = $item['model_variant']; }
	if(!empty($item['variant'])) { $meta_data_to_update['model_description'] = $item['model_description']; } // Deprecated.
	if(!empty($item['model_description'])) { $meta_data_to_update['model_description'] = $item['model_description']; }
	if(!empty($item['damage-and-unrepaired'])) { $meta_data_to_update['damageUnrepaired'] = $item['damage-and-unrepaired']; } // Deprecated
	if(!empty($item['damage-and-unrepaired'])) { $meta_data_to_update['damage_and_unrepaired'] = $item['damage-and-unrepaired']; }
	if(!empty($item['accident_damaged'])) { $meta_data_to_update['accidentDamaged'] = $item['accident_damaged']; }
	if(!empty($item['road_worthy'])) { $meta_data_to_update['road_worthy'] = $item['road_worthy']; }
	if(!empty($item['category'])) { $meta_data_to_update['category'] = $item['category']; }
	if(!empty($item['condition'])) { $meta_data_to_update['condition'] = $item['condition']; }
	if(!empty($item['seller'])) { $meta_data_to_update['seller'] = $item['seller']; }
	if(!empty($item['seller_id'])) { $meta_data_to_update['seller_id'] = $item['seller_id']; }
	if(!empty($item['seller_company_name'])) { $meta_data_to_update['seller_company_name'] = $item['seller_company_name']; }
	if(!empty($item['seller_street'])) { $meta_data_to_update['seller_street'] = $item['seller_street']; }
	if(!empty($item['seller_zipcode'])) { $meta_data_to_update['seller_zipcode'] = $item['seller_zipcode']; }
	if(!empty($item['seller_city'])) { $meta_data_to_update['seller_city'] = $item['seller_city']; }
	if(!empty($item['seller_country'])) { $meta_data_to_update['seller_country'] = $item['seller_country']; }
	if(!empty($item['seller_email'])) { $meta_data_to_update['seller_email'] = $item['seller_email']; }
	if(!empty($item['seller_homepage'])) { $meta_data_to_update['seller_homepage'] = $item['seller_homepage']; }
	if(!empty($item['seller_phone_country_calling_code'])) { $meta_data_to_update['seller_phone_country_calling_code'] = $item['seller_phone_country_calling_code']; }
	if(!empty($item['seller_phone_area_code'])) { $meta_data_to_update['seller_phone_area_code'] = $item['seller_phone_area_code']; }
	if(!empty($item['seller_phone_number'])) { $meta_data_to_update['seller_phone_number'] = $item['seller_phone_number']; }
	if(!empty($item['seller_since'])) { $meta_data_to_update['seller_since'] = $item['seller_since']; }
	if(!empty($item['sellerType'])) { $meta_data_to_update['sellerType'] = $item['sellerType']; }
	if(!empty($item['first_registration'])) { $meta_data_to_update['firstRegistration'] = $item['first_registration']; }
	if(!empty($item['first_registration_year'])) { $meta_data_to_update['firstRegistration_year'] = $item['first_registration_year']; } // Added 2015-03-16 --bth
	if(!empty($item['emissionClass'])) { $meta_data_to_update['emissionClass'] = $item['emission_class']; }
	if(!empty($item['emission_class'])) { $meta_data_to_update['emission_class'] = $item['emission_class']; }
	if(!empty($item['co2_emission'])) { $meta_data_to_update['emissionFuelConsumption_CO2'] = $item['co2_emission']; }
	if(!empty($item['inner'])) { $meta_data_to_update['emissionFuelConsumption_Inner'] = $item['inner']; }
	if(!empty($item['outer'])) { $meta_data_to_update['emissionFuelConsumption_Outer'] = $item['outer']; }
	if(!empty($item['combined'])) { $meta_data_to_update['emissionFuelConsumption_Combined'] = $item['combined']; }
	if(!empty($item['combined-power-consumption'])) { $meta_data_to_update['combinedPowerConsumption'] = $item['combined-power-consumption']; }
	if(!empty($item['unit'])) { $meta_data_to_update['emissionFuelConsumption_Unit'] = $item['unit']; }
	if(!empty($item['emissionSticker'])) { $meta_data_to_update['emissionSticker'] = $item['emissionSticker']; }
	if(!empty($item['exterior_color'])) { $meta_data_to_update['exteriorColor'] = $item['exterior_color']; }
	if(!empty($item['fuel']) && !empty($item['HYBRID_PLUGIN'])) {
		$meta_data_to_update['fuel'] = $item['HYBRID_PLUGIN'];
		$item['fuel'] = $item['HYBRID_PLUGIN'];
	} else { 
		$meta_data_to_update['fuel'] = $item['fuel']; 
	}
	if(!empty($item['power'])) { $meta_data_to_update['power'] = $item['power']; }
	if(!empty($item['number_of_previous_owners'])) { $meta_data_to_update['owners'] = $item['number_of_previous_owners']; }
	if(!empty($item['cubic_capacity'])) { $meta_data_to_update['cubicCapacity'] = $item['cubic_capacity']; }
	if(!empty($item['gearbox'])) { $meta_data_to_update['gearbox'] = $item['gearbox']; }
	// $meta_data_to_update['monthsTillInspection'] = $item['monthsTillInspection']);
	if(!empty($item['nextInspection'])) { $meta_data_to_update['nextInspection'] = $item['nextInspection']; }
	if(!empty($item['features'])) { $meta_data_to_update['features'] = $item['features']; }
	if(!empty($item['mileage'])) { $meta_data_to_update['mileage'] = $item['mileage']; }
	if(!empty($item['mileage_raw'])) { $meta_data_to_update['mileage_raw'] = $item['mileage_raw']; } // Added 2015-03-16 --bth
	if(!empty($item['mileage_class'])) { $meta_data_to_update['mileage_class'] = $item['mileage_class']; } // Added 2015-03-16 --bth
	if(!empty($item['price'])) { $meta_data_to_update['price'] = $item['price']; }
	if(!empty($item['dealer-price-amount'])) { $meta_data_to_update['dealer_price'] = $item['dealer-price-amount']; }
	//	$meta_data_to_update['price_raw'] = $item['price_raw']); // Added 2015-03-16 --bth
	if(!empty($item['price_raw_short'])) { $meta_data_to_update['price_raw_short'] = $item['price_raw_short']; } // Added 2015-03-16 --bth
	if(!empty($item['currency'])) { $meta_data_to_update['currency'] = $item['currency']; }
	if(!empty($item['vatable'])) { $meta_data_to_update['vatable'] = $item['vatable']; }
	if(!empty($item['loadCapacity'])) { $meta_data_to_update['loadCapacity'] = $item['loadCapacity']; }
	if(!empty($item['detail_page'])) { $meta_data_to_update['detailPage'] = $item['detail_page']; } // Deprecated
	if(!empty($item['detail_page'])) { $meta_data_to_update['detail_page'] = $item['detail_page']; }
	if(!empty($item['country'])) { $meta_data_to_update['country'] = $item['country']; }
	if(!empty($item['zipcode'])) { $meta_data_to_update['zipcode'] = $item['zipcode']; }
	if(!empty($item['ad_key'])) { $meta_data_to_update['ad_key'] = $item['ad_key']; }
	// Changes for the caravan seller
	if(!empty($item['construction-year'])) { $meta_data_to_update['construction_year'] = $item['construction-year']; }
	if(!empty($item['number-of-bunks'])) { $meta_data_to_update['number_of_bunks'] = $item['number-of-bunks']; }
	if(!empty($item['length'])) { $meta_data_to_update['length'] = $item['length']; }
	if(!empty($item['width'])) { $meta_data_to_update['width'] = $item['width']; }
	if(!empty($item['height'])) { $meta_data_to_update['height'] = $item['height']; }
	if(!empty($item['licensed-weight'])) { $meta_data_to_update['licensed_weight'] = $item['licensed-weight']; }
	// Additional data. --bth 2014-10-29 02:18:13
	/*
	* Delivery date and period.
	*
	*/
	if(!empty($item['delivery-date'])) { $meta_data_to_update['delivery_date'] = $item['delivery-date']; }
	if(!empty($item['delivery-period'])) { $meta_data_to_update['delivery_period'] = $item['delivery-period']; }
	/*
	* Contains either the future delivery_date or the string "Sofort".
	* Gets calculated in searchAPI.
	*/
	if(!empty($item['available-from'])) { $meta_data_to_update['available_from'] = $item['available-from']; }
	if(!empty($item['interior-type'])) { $meta_data_to_update['interior_type'] = $item['interior-type']; }
	if(!empty($item['interior-color'])) { $meta_data_to_update['interior_color'] = $item['interior-color']; }
	if(!empty($item['door-count'])) { $meta_data_to_update['door_count'] = $item['door-count']; }
	if(!empty($item['num-seats'])) { $meta_data_to_update['num_seats'] = $item['num-seats']; }
	if(!empty($item['number_of_previous_owners'])) { $meta_data_to_update['number_of_previous_owners'] = $item['number_of_previous_owners']; }
	if(!empty($item['seller-inventory-key'])) { $meta_data_to_update['seller_inventory_key'] = $item['seller-inventory-key']; }
	if(!empty($item['airbag'])) { $meta_data_to_update['airbag'] = $item['airbag']; }
	/*
	* Efficiency class and efficiency class image url.
	*/
	if(!empty($item['energy-efficiency-class'])) { update_post_meta($post_id, 'efficiency_class', $item['energy-efficiency-class']); }
	if (!empty($item['energy-efficiency-class'])) {
		// Maybe additional condition
		{
		update_post_meta($post_id, 'efficiency_class_image_url', get_site_url() . '/wp-content/plugins/kfzweb/images/' . $item['energy-efficiency-class'] . '.png');
		}
	}
	// keys used in search
	if(!empty($item['class_key'])) { $meta_data_to_update['class_key'] = $item['class_key']; }
	if(!empty($item['category_key'])) { $meta_data_to_update['category_key'] = $item['category_key']; }
	if(!empty($item['brand_key'])) { $meta_data_to_update['brand_key'] = $item['brand_key']; }
	if(!empty($item['model_key'])) { $meta_data_to_update['model_key'] = $item['model_key']; }
	if(!empty($item['fuel_key'])) { $meta_data_to_update['fuel_key'] = $item['fuel_key']; }
	if(!empty($item['power_key'])) { $meta_data_to_update['power_key'] = $item['power_key']; }
	if(!empty($item['owners_key'])) { $meta_data_to_update['owners_key'] = $item['owners_key']; }
	if(!empty($item['cubicCapacity_key'])) { $meta_data_to_update['cubicCapacity_key'] = $item['cubicCapacity_key']; }
	if(!empty($item['gearbox_key'])) { $meta_data_to_update['gearbox_key'] = $item['gearbox_key']; }
	if(!empty($item['modification_date'])) { $meta_data_to_update['modification_date'] = $item['modification_date']; }
	if(!empty($item['usage-type'])) { $meta_data_to_update['usage_type'] = $item['usage-type']; }
	if(!empty($item['addition'])) { $meta_data_to_update['addition'] = $item['addition']; }
	if(!empty($item['enriched_description'])) { $meta_data_to_update['enriched_description'] = $item['enriched_description']; } 
	// fmh 01.03.15 added for new template-checker content
	if(!empty($item['identification-number'])) { $meta_data_to_update['identification_number'] = $item['identification-number']; }
	if(!empty($item['axles'])) { $meta_data_to_update['axles'] = $item['axles']; }
	if(!empty($item['wheel-formula'])) { $meta_data_to_update['wheel_formula'] = $item['wheel-formula']; }
	if(!empty($item['hydraulic-installation'])) { $meta_data_to_update['hydraulic_installation'] = $item['hydraulic-installation']; }
	if(!empty($item['europallet-storage-spaces'])) { $meta_data_to_update['europallet_storage_spaces'] = $item['europallet-storage-spaces']; }
	if(!empty($item['manufacturer-color-name'])) { $meta_data_to_update['manufacturer_color_name'] = $item['manufacturer-color-name']; }
	if(!empty($item['shipping-volume'])) { $meta_data_to_update['shipping_volume'] = $item['shipping-volume']; }
	if(!empty($item['loadCapacity'])) { $meta_data_to_update['load_capacity'] = $item['loadCapacity']; }
	$numimages = count($item['images']);
	if($numimages > 1) {
		update_post_meta($post_id, 'carousel', '1');
	}
	// add_post_meta($post_id, 'ad_gallery', (string)$item['images']);
	$options = get_option('MobileDE_option');
	if (empty($options['mob_image_option'])) {
		$options['mob_image_option'] = 'web';
	}
	if ($options['mob_image_option'] == 'web') {
		foreach($item['images'] as $image) {
			add_post_meta($post_id, 'ad_gallery', (string)$image);
			add_post_meta($post_id, 'images_ebay', (string)$image);
		}
		if (substr($item['images'][0], -6) == '27.JPG') {
			$temp = str_replace('27.JPG', '57.JPG', $item['images'][0]); // 1600x1200 px
			if (getimagesize($temp)) { // This is the FileExists check. Using a dirty side effect, but seems to be fast.
				$i = '';
				$metaData = import_post_image($post_id, $temp, $i == 0);
				// metaData = update_post_meta($post_id, $temp, $i);
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
			if (substr($image, -6) == '27.JPG') {
				$temp = str_replace('27.JPG', '57.JPG', $image); // 1600x1200 px
				if (getimagesize($temp)) { // This is the FileExists check. Using a dirty side effect, but seems to be fast.
					$metaData = import_post_image($post_id, $temp, $i == 0);
					// metaData = update_post_meta($post_id, $temp, $i);
				}
				else {
					$metaData = import_post_image($post_id, $image, $i == 0); // Original sole API image.
				}
			}
			else {
				$metaData = import_post_image($post_id, $image, $i == 0); // Original sole API image.
			}
		}
	}	// new feature meta_values as single post_meta	
	if(!empty($item['ABS'])) { $meta_data_to_update['ABS'] = $item['ABS']; }
	if(!empty($item['ALLOY_WHEELS'])) { $meta_data_to_update['ALLOY_WHEELS'] = $item['ALLOY_WHEELS']; }
	if(!empty($item['AUTOMATIC_RAIN_SENSOR'])) { $meta_data_to_update['AUTOMATIC_RAIN_SENSOR'] = $item['AUTOMATIC_RAIN_SENSOR']; }
	if(!empty($item['AUXILIARY_HEATING'])) { $meta_data_to_update['AUXILIARY_HEATING'] = $item['AUXILIARY_HEATING']; }
	if(!empty($item['BENDING_LIGHTS'])) {$meta_data_to_update['BENDING_LIGHTS'] = $item['BENDING_LIGHTS']; }
	if(!empty($item['BIODIESEL_SUITABLE'])) {$meta_data_to_update['BIODIESEL_SUITABLE'] = $item['BIODIESEL_SUITABLE']; }
	if(!empty($item['BLUETOOTH'])) {$meta_data_to_update['BLUETOOTH'] = $item['BLUETOOTH']; }
	if(!empty($item['CD_MULTICHANGER'])) {$meta_data_to_update['CD_MULTICHANGER'] = $item['CD_MULTICHANGER']; }
	if(!empty($item['CD_PLAYER'])) {$meta_data_to_update['CD_PLAYER'] = $item['CD_PLAYER']; }
	if(!empty($item['CENTRAL_LOCKING'])) {$meta_data_to_update['CENTRAL_LOCKING'] = $item['CENTRAL_LOCKING']; }
	if(!empty($item['CRUISE_CONTROL'])) {$meta_data_to_update['CRUISE_CONTROL'] = $item['CRUISE_CONTROL']; }
	if(!empty($item['DAYTIME_RUNNING_LIGHTS'])) {$meta_data_to_update['DAYTIME_RUNNING_LIGHTS'] = $item['DAYTIME_RUNNING_LIGHTS']; }
	if(!empty($item['E10_ENABLED'])) {$meta_data_to_update['E10_ENABLED'] = $item['E10_ENABLED']; }
	if(!empty($item['ELECTRIC_ADJUSTABLE_SEATS'])) {$meta_data_to_update['ELECTRIC_ADJUSTABLE_SEATS'] = $item['ELECTRIC_ADJUSTABLE_SEATS']; }
	if(!empty($item['ELECTRIC_EXTERIOR_MIRRORS'])) {$meta_data_to_update['ELECTRIC_EXTERIOR_MIRRORS'] = $item['ELECTRIC_EXTERIOR_MIRRORS']; }
	if(!empty($item['AIR_SUSPENSION'])) {$meta_data_to_update['AIR_SUSPENSION'] = $item['AIR_SUSPENSION']; }
	if(!empty($item['ALARM_SYSTEM'])) {$meta_data_to_update['ALARM_SYSTEM'] = $item['ALARM_SYSTEM']; }
	if(!empty($item['CARPLAY'])) {$meta_data_to_update['CARPLAY'] = $item['CARPLAY']; }
	if(!empty($item['LANE_DEPARTURE_WARNING'])) {$meta_data_to_update['LANE_DEPARTURE_WARNING'] = $item['LANE_DEPARTURE_WARNING']; }
	if(!empty($item['SKI_BAG'])) {$meta_data_to_update['SKI_BAG'] = $item['SKI_BAG']; }
	if(!empty($item['AMBIENT_LIGHTING'])) {$meta_data_to_update['AMBIENT_LIGHTING'] = $item['AMBIENT_LIGHTING']; }
	if(!empty($item['KEYLESS_ENTRY'])) {$meta_data_to_update['KEYLESS_ENTRY'] = $item['KEYLESS_ENTRY']; }
	if(!empty($item['DISABLED_ACCESSIBLE'])) {$meta_data_to_update['DISABLED_ACCESSIBLE'] = $item['DISABLED_ACCESSIBLE']; }
	if(!empty($item['DIGITAL_COCKPIT'])) {$meta_data_to_update['DIGITAL_COCKPIT'] = $item['DIGITAL_COCKPIT']; }
	if(!empty($item['COLLISION_AVOIDANCE'])) {$meta_data_to_update['COLLISION_AVOIDANCE'] = $item['COLLISION_AVOIDANCE']; }
	if(!empty($item['ELECTRIC_HEATED_REAR_SEATS'])) {$meta_data_to_update['ELECTRIC_HEATED_REAR_SEATS'] = $item['ELECTRIC_HEATED_REAR_SEATS']; }
	if(!empty($item['ELECTRIC_BACKSEAT_ADJUSTMENT'])) {$meta_data_to_update['ELECTRIC_BACKSEAT_ADJUSTMENT'] = $item['ELECTRIC_BACKSEAT_ADJUSTMENT']; }
	if(!empty($item['BLIND_SPOT_MONITOR'])) {$meta_data_to_update['BLIND_SPOT_MONITOR'] = $item['BLIND_SPOT_MONITOR']; }
	if(!empty($item['ELECTRIC_HEATED_SEATS'])) {$meta_data_to_update['ELECTRIC_HEATED_SEATS'] = $item['ELECTRIC_HEATED_SEATS']; }
	if(!empty($item['ELECTRIC_WINDOWS'])) {$meta_data_to_update['ELECTRIC_WINDOWS'] = $item['ELECTRIC_WINDOWS']; }
	if(!empty($item['ESP'])) {$meta_data_to_update['ESP'] = $item['ESP']; }
	if(!empty($item['EXPORT'])) {$meta_data_to_update['EXPORT'] = $item['EXPORT']; }
	if(!empty($item['FRONT_FOG_LIGHTS'])) {$meta_data_to_update['FRONT_FOG_LIGHTS'] = $item['FRONT_FOG_LIGHTS']; }
	if(!empty($item['FULL_SERVICE_HISTORY'])) {$meta_data_to_update['FULL_SERVICE_HISTORY'] = $item['FULL_SERVICE_HISTORY']; }
	if(!empty($item['HANDS_FREE_PHONE_SYSTEM'])) {$meta_data_to_update['HANDS_FREE_PHONE_SYSTEM'] = $item['HANDS_FREE_PHONE_SYSTEM']; }
	if(!empty($item['HEAD_UP_DISPLAY'])) {$meta_data_to_update['HEAD_UP_DISPLAY'] = $item['HEAD_UP_DISPLAY']; }
	if(!empty($item['HU_AU_NEU'])) {$meta_data_to_update['HU_AU_NEU'] = $item['HU_AU_NEU']; }
	if(!empty($item['HYBRID_PLUGIN'])) {$meta_data_to_update['HYBRID_PLUGIN'] = $item['HYBRID_PLUGIN']; }
	if(!empty($item['IMMOBILIZER'])) {$meta_data_to_update['IMMOBILIZER'] = $item['IMMOBILIZER']; }
	if(!empty($item['ISOFIX'])) {$meta_data_to_update['ISOFIX'] = $item['ISOFIX']; }
	if(!empty($item['LIGHT_SENSOR'])) {$meta_data_to_update['LIGHT_SENSOR'] = $item['LIGHT_SENSOR']; }
	if(!empty($item['METALLIC'])) {$meta_data_to_update['METALLIC'] = $item['METALLIC']; }
	if(!empty($item['MP3_INTERFACE'])) {$meta_data_to_update['MP3_INTERFACE'] = $item['MP3_INTERFACE']; }
	if(!empty($item['MULTIFUNCTIONAL_WHEEL'])) {$meta_data_to_update['MULTIFUNCTIONAL_WHEEL'] = $item['MULTIFUNCTIONAL_WHEEL']; }
	if(!empty($item['NAVIGATION_SYSTEM'])) {$meta_data_to_update['NAVIGATION_SYSTEM'] = $item['NAVIGATION_SYSTEM']; }
	if(!empty($item['NONSMOKER_VEHICLE'])) {$meta_data_to_update['NONSMOKER_VEHICLE'] = $item['NONSMOKER_VEHICLE']; }
	if(!empty($item['ON_BOARD_COMPUTER'])) {$meta_data_to_update['ON_BOARD_COMPUTER'] = $item['ON_BOARD_COMPUTER']; }
	if(!empty($item['PANORAMIC_GLASS_ROOF'])) {$meta_data_to_update['PANORAMIC_GLASS_ROOF'] = $item['PANORAMIC_GLASS_ROOF']; }
	if(!empty($item['PARKING_SENSORS'])) {$meta_data_to_update['PARKING_SENSORS'] = $item['PARKING_SENSORS']; }
	if(!empty($item['PARTICULATE_FILTER_DIESEL'])) {$meta_data_to_update['PARTICULATE_FILTER_DIESEL'] = $item['PARTICULATE_FILTER_DIESEL']; }
	if(!empty($item['PERFORMANCE_HANDLING_SYSTEM'])) {$meta_data_to_update['PERFORMANCE_HANDLING_SYSTEM'] = $item['PERFORMANCE_HANDLING_SYSTEM']; }
	if(!empty($item['POWER_ASSISTED_STEERING'])) {$meta_data_to_update['POWER_ASSISTED_STEERING'] = $item['POWER_ASSISTED_STEERING']; }
	if(!empty($item['ROOF_RAILS'])) {$meta_data_to_update['ROOF_RAILS'] = $item['ROOF_RAILS']; }
	if(!empty($item['SKI_BAG'])) {$meta_data_to_update['SKI_BAG'] = $item['SKI_BAG']; }
	if(!empty($item['SPORT_PACKAGE'])) {$meta_data_to_update['SPORT_PACKAGE'] = $item['SPORT_PACKAGE']; }
	if(!empty($item['SPORT_SEATS'])) {$meta_data_to_update['SPORT_SEATS'] = $item['SPORT_SEATS']; }
	if(!empty($item['START_STOP_SYSTEM'])) {$meta_data_to_update['START_STOP_SYSTEM'] = $item['START_STOP_SYSTEM']; }
	if(!empty($item['SUNROOF'])) {$meta_data_to_update['SUNROOF'] = $item['SUNROOF']; }
	if(!empty($item['TAXI'])) {$meta_data_to_update['TAXI'] = $item['TAXI']; }
	if(!empty($item['TRACTION_CONTROL_SYSTEM'])) {$meta_data_to_update['TRACTION_CONTROL_SYSTEM'] = $item['TRACTION_CONTROL_SYSTEM']; }
	if(!empty($item['TRAILER_COUPLING'])) {$meta_data_to_update['TRAILER_COUPLING'] = $item['TRAILER_COUPLING']; }
	if(!empty($item['TUNER'])) {$meta_data_to_update['TUNER'] = $item['TUNER']; }
	if(!empty($item['VEGETABLEOILFUEL_SUITABLE'])) {$meta_data_to_update['VEGETABLEOILFUEL_SUITABLE'] = $item['VEGETABLEOILFUEL_SUITABLE']; }
	if(!empty($item['WARRANTY'])) {$meta_data_to_update['WARRANTY'] = $item['WARRANTY']; }
	if(!empty($item['XENON_HEADLIGHTS'])) {$meta_data_to_update['XENON_HEADLIGHTS'] = $item['XENON_HEADLIGHTS']; }
	if(!empty($item['FOUR_WHEEL_DRIVE'])) {$meta_data_to_update['FOUR_WHEEL_DRIVE'] = $item['FOUR_WHEEL_DRIVE']; }
	if(!empty($item['DISABLED_ACCESSIBLE'])) {$meta_data_to_update['DISABLED_ACCESSIBLE'] = $item['DISABLED_ACCESSIBLE']; }
	if(!empty($item['climatisation'])) {$meta_data_to_update['climatisation'] = $item['climatisation']; }
	if(!empty($item['schwacke-code'])) {$meta_data_to_update['schwacke-code'] = $item['schwacke-code']; }
	if(!empty($item['enkv-compliant'])) {$meta_data_to_update['enkv-compliant'] = $item['enkv-compliant']; }
	if(!empty($item['description'])) {$meta_data_to_update['description'] = $item['description']; }
	if(!empty($item['included-delivery-costs'])) {$meta_data_to_update['included-delivery-costs'] = $item['included-delivery-costs']; }
	if(!empty($item['exhaust-inspection'])) {$meta_data_to_update['exhaust-inspection'] = $item['exhaust-inspection']; }
	if(!empty($item['operating-hours'])) {$meta_data_to_update['operating-hours'] = $item['operating-hours']; }
	if(!empty($item['installation-height'])) {$meta_data_to_update['installation-height'] = $item['installation-height']; }
	if(!empty($item['lifting-capacity'])) {$meta_data_to_update['lifting-capacity'] = $item['lifting-capacity']; }
	if(!empty($item['lifting-height'])) {$meta_data_to_update['lifting-height'] = $item['lifting-height']; }
	if(!empty($item['driving-mode'])) {$meta_data_to_update['driving-code'] = $item['driving-mode']; }
	if(!empty($item['driving-cab'])) {$meta_data_to_update['driving-cab'] = $item['driving-cab']; }
	if(!empty($item['loading-space-length'])) {$meta_data_to_update['loading-space-length'] = $item['loading-space-length']; }
	if(!empty($item['loading-space-height'])) {$meta_data_to_update['loading-space-height'] = $item['loading-space-height']; }
	if(!empty($item['loading-space-width'])) {$meta_data_to_update['loading-space-width'] = $item['loading-space-width']; }
	if(!empty($item['countryVersion'])) {$meta_data_to_update['country-version'] = $item['countryVersion']; }
	if(!empty($item['videoUrl'])) {$meta_data_to_update['videoUrl'] = $item['videoUrl']; }
	if(!empty($item['parking-assistants'])) {$meta_data_to_update['parking-assistants'] = $item['parking-assistants']; }
	if(!empty($item['price_dropdown'])) { $meta_data_to_update['price_dropdown'] = $item['price_dropdown']; }
	// wltp Data
	// Combined fuel consumption for all nonelectric vehicles, optional for plugin hybrids, number in l/100km (natural gas (CNG) in kg/100km)
	if(!empty($item['wltp-consumption-fuel-combined'])) { $meta_data_to_update['wltp-consumption-fuel-combined'] = $item['wltp-consumption-fuel-combined']; }
	// Amount of carbon dioxide emissions in g/km for all vehicles, optional for plugin hybrids.
	if(!empty($item['wltp-co2-emission-combined'])) { $meta_data_to_update['wltp-co2-emission-combined'] = $item['wltp-co2-emission-combined']; }
	// Combined power consumption for electric vehicles in in kWh/100km
	if(!empty($item['wltp-consumption-power-combined'])) { $meta_data_to_update['wltp-consumption-power-combined'] = $item['wltp-consumption-power-combined']; }
	// Electric Range for plugin hybrids and electric vehicles in km
	if(!empty($item['wltp-electric-range'])) { $meta_data_to_update['wltp-electric-range'] = $item['wltp-electric-range']; }
	// Weighted combined fuel consumption for plugin hybrids
	if(!empty($item['wltp-consumption-fuel-combined-weighted'])) { $meta_data_to_update['wltp-consumption-fuel-combined-weighted'] = $item['wltp-consumption-fuel-combined-weighted']; }
	// Weighted combined power consumption for plugin hybrids in kWh/100km
	if(!empty($item['wltp-consumption-power-combined-weighted'])) { $meta_data_to_update['wltp-consumption-power-combined-weighted'] = $item['wltp-consumption-power-combined-weighted']; }
	// Weighted amount of carbon dioxide emissions in g/km for plugin hybrids
	if(!empty($item['wltp-co2-emission-combined-weighted'])) { $meta_data_to_update['wltp-co2-emission-combined-weighted'] = $item['wltp-co2-emission-combined-weighted']; }
	// CO2 emissions
	if(!empty($item['wltp-co2-emission'])) { $meta_data_to_update['wltp-co2-emission'] = $item['wltp-co2-emission']; }
	// CO2 class based on CO2 emissions
	if(!empty($item['wltp-co2-class'])) { $meta_data_to_update['wltp-co2-class'] = $item['wltp-co2-class']; }
	// CO2 class based on CO2 emissions with discharged battery
	if(!empty($item['wltp-co2-class-discharged'])) { $meta_data_to_update['wltp-co2-class-discharged'] = $item['wltp-co2-class-discharged']; }
	// Weighted combined consumption
	if( !empty($item['wltp-weighted-combined-fuel']) ) { $meta_data_to_update['wltp-weighted-combined-fuel'] = $item['wltp-weighted-combined-fuel']; }
	// combined consumption
	if( !empty($item['wltp-combined']) ) { $meta_data_to_update['wltp-combined'] = $item['wltp-combined']; }
	// Weighted combined electricity consumption
	if( !empty($item['wltp-weighted-combined-power']) ) { $meta_data_to_update['wltp-weighted-combined-power'] = $item['wltp-weighted-combined-power']; }
	// Combined electricity consumption
	if( !empty($item['wltp-combined-power']) ) { $meta_data_to_update['wltp-combined-power'] = $item['wltp-combined-power']; }
	// Combined consumption with discharged battery	
	if( !empty($item['wltp-combined-discharged']) ) { $meta_data_to_update['wltp-combined-discharged'] = $item['wltp-combined-discharged']; }

	// CO2-Emissionen
	if(!empty($item['wltp-co2-emission'])) { $meta_data_to_update['wltp-co2-emission'] = $item['wltp-co2-emission']; }
	// CO2-Klasse auf Basis der CO2-Emissionen
	if(!empty($item['wltp-co2-class'])) { $meta_data_to_update['wltp-co2-class'] = $item['wltp-co2-class']; }
	// CO2-Emissionen (bei entladener Batterie)
	if(!empty($item['wltp-co2-emission-discharged'])) { $meta_data_to_update['wltp-co2-emission-discharged'] = $item['wltp-co2-emission-discharged']; }
	// CO2-Klasse auf Grundlage der CO2-Emissionen bei entladener Batterie
	if(!empty($item['wltp-co2-class-discharged'])) { $meta_data_to_update['wltp-co2-class-discharged'] = $item['wltp-co2-class-discharged']; }
	// Elektrische Reichweite	
	if(!empty($item['wltp-electric-range'])) { $meta_data_to_update['wltp-electric-range'] = $item['wltp-electric-range']; }
	// Elektrische Reichweite (EAER)
	if(!empty($item['wltp-electric-range-equivalent-all'])) { $meta_data_to_update['wltp-electric-range-equivalent-all'] = $item['wltp-electric-range-equivalent-all']; }
	// Verbrauch gewichtet, kombiniert
	if(!empty($item['wltp-weighted-combined-fuel'])) { $meta_data_to_update['wltp-weighted-combined-fuel'] = $item['wltp-weighted-combined-fuel']; }
	// Verbrauch kombiniert
	if(!empty($item['wltp-combined-fuel'])) { $meta_data_to_update['wltp-combined-fuel'] = $item['wltp-combined-fuel']; }
	// Verbrauch Innenstadt 
	if(!empty($item['wltp-city-fuel'])) { $meta_data_to_update['wltp-city-fuel'] = $item['wltp-city-fuel']; }
	// Verbrauch Stadtrand
	if(!empty($item['wltp-suburban-fuel'])) { $meta_data_to_update['wltp-suburban-fuel'] = $item['wltp-suburban-fuel']; }
	// Verbrauch Landstraße
	if(!empty($item['wltp-rural-fuel'])) { $meta_data_to_update['wltp-rural-fuel'] = $item['wltp-rural-fuel']; }
	// Verbrauch Autobahn
	if(!empty($item['wltp-highway-fuel'])) { $meta_data_to_update['wltp-highway-fuel'] = $item['wltp-highway-fuel']; }
	// Stromverbrauch kombiniert
	if(!empty($item['wltp-combined-power'])) { $meta_data_to_update['wltp-combined-power'] = $item['wltp-combined-power']; }
	// Stromverbrauch Innenstadt
	if(!empty($item['wltp-city-power'])) { $meta_data_to_update['wltp-city-power'] = $item['wltp-city-power']; }
	// Stromverbrauch Stadtrand
	if(!empty($item['wltp-suburban-power'])) { $meta_data_to_update['wltp-suburban-power'] = $item['wltp-suburban-power']; }
	// Stromverbrauch Landstraße
	if(!empty($item['wltp-rural-power'])) { $meta_data_to_update['wltp-rural-power'] = $item['wltp-rural-power']; }
	// Stromverbrauch Autobahn
	if(!empty($item['wltp-highway-power'])) { $meta_data_to_update['wltp-highway-power'] = $item['wltp-highway-power']; }
	// Verbrauch bei entladener Batterie kombiniert
	if(!empty($item['wltp-empty-combined-fuel'])) { $meta_data_to_update['wltp-empty-combined-fuel'] = $item['wltp-empty-combined-fuel']; }
	// Verbrauch bei entladener Batterie Innenstadt
	if(!empty($item['wltp-empty-city-fuel'])) { $meta_data_to_update['wltp-empty-city-fuel'] = $item['wltp-empty-city-fuel']; }
	// Verbrauch bei entladener Batterie Stadtrand
	if(!empty($item['wltp-empty-suburban-fuel'])) { $meta_data_to_update['wltp-empty-suburban-fuel'] = $item['wltp-empty-suburban-fuel']; }
	// Verbrauch bei entladener Batterie Landstraße
	if(!empty($item['wltp-empty-rural-fuel'])) { $meta_data_to_update['wltp-empty-rural-fuel'] = $item['wltp-empty-rural-fuel']; }
	// Verbrauch bei entladener Batterie Autobahn
	if(!empty($item['wltp-empty-highway-fuel'])) { $meta_data_to_update['wltp-empty-highway-fuel'] = $item['wltp-empty-highway-fuel']; }
	// Kraftstoffpreis [Jahr]
	if(!empty($item['wltp-fuel-price-year'])) { $meta_data_to_update['wltp-fuel-price-year'] = $item['wltp-fuel-price-year']; }
	// Strompreis [Jahr]
	if(!empty($item['wltp-power-price-year'])) { $meta_data_to_update['wltp-power-price-year'] = $item['wltp-power-price-year']; }
	// Jahresdurchschnitt [Jahr]
	if(!empty($item['wltp-consumption-price-year'])) { $meta_data_to_update['wltp-consumption-price-year'] = $item['wltp-consumption-price-year']; }
	// Energiekosten bei 15.000 km Jahresfahrleistung
	if(!empty($item['wltp-consumption-costs'])) { $meta_data_to_update['wltp-consumption-costs'] = $item['wltp-consumption-costs']; }
	// bei einem angenommenen niedrigen durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-low-base'])) { $meta_data_to_update['wltp-co2-costs-low-base'] = $item['wltp-co2-costs-low-base']; }
	// bei einem angenommenen mittleren durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-middle-base'])) { $meta_data_to_update['wltp-co2-costs-middle-base'] = $item['wltp-co2-costs-middle-base']; }
	// bei einem angenommenen hohen durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-high-base'])) { $meta_data_to_update['wltp-co2-costs-high-base'] = $item['wltp-co2-costs-high-base']; }
	// bei einem angenommenen niedrigen durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-low-accumulated'])) { $meta_data_to_update['wltp-co2-costs-low-accumulated'] = $item['wltp-co2-costs-low-accumulated']; }
	// bei einem angenommenen mittleren durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-middle-accumulated'])) { $meta_data_to_update['wltp-co2-costs-middle-accumulated'] = $item['wltp-co2-costs-middle-accumulated']; }
	// bei einem angenommenen hohen durchschnittlichen CO2-Preis von
	if(!empty($item['wltp-co2-costs-high-accumulated'])) { $meta_data_to_update['wltp-co2-costs-high-accumulated'] = $item['wltp-co2-costs-high-accumulated']; }
	// Kraftfahrzeugsteuer
	if(!empty($item['wltp-tax'])) { $meta_data_to_update['wltp-tax'] = $item['wltp-tax']; }
	// Zeitspanne von
	if(!empty($item['wltp-cost-model-from'])) { $meta_data_to_update['wltp-cost-model-from'] = $item['wltp-cost-model-from']; }
	// Zeitspanne bis
	if(!empty($item['wltp-cost-model-till'])) { $meta_data_to_update['wltp-cost-model-till'] = $item['wltp-cost-model-till']; }

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
	$images_ebay = array(
		'images_ebay' => @$item['images']
	);
	wp_set_object_terms($post_id, $images_ebay, 'images_ebay');
	$baujahr = array(
		'baujahr' => @$item['construction-year']
	);
	wp_set_object_terms($post_id, $baujahr, 'baujahr');
	$anzahl_schlafplaetze = array(
		'anzahl_schlafplaetze' => @$item['number_of_bunks']);
	wp_set_object_terms($post_id, $anzahl_schlafplaetze, 'anzahl_schlafplaetze');
	foreach ($meta_data_to_update as $meta_key => $meta_value) {
		update_post_meta($post_id, $meta_key, $meta_value);
	}

		update_post_meta($post_id, 'is_finished', '1');
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
// 	$image_data = wp_remote_get($image_url);
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
function import_post_image($post_id, $image_url, $thumbnail = true)
{
    $re = '/\[0]\s\=\>\s/m';
    $str = '[0] => https://img.classistatic.de/api/v1/mo-prod/images/a4/a44c541e-fcac-48fc-8974-4fb28782ec52?rule=mo-800.jpg';
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
    // Abrufen aller Anhänge des Beitrags
    $attachments = get_children(array(
        'post_parent' => $post_id,
        'post_type' => 'attachment',
        'post_status' => 'inherit', // Anhänge haben den Status 'inherit'
        'numberposts' => -1, // Alle Anhänge abrufen
    ));

    // Überprüfen, ob Anhänge vorhanden sind
    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            // Löschen jedes Anhangs
            wp_delete_attachment($attachment->ID, true);
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
	$options = get_option('MobileDE_option');

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
	$cpt_archive_enabled = isset($options['mob_cpt_archive_option']) && $options['mob_cpt_archive_option'] === 'true';
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
		'has_archive' => $cpt_archive_enabled,
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
		while ($query->have_posts()) {
			$query->the_post();
			$meta_values = get_post_meta( get_the_ID() );
			if(!isset($meta_values['is_finished']) && !isset($meta_values['is_finished'][0])){
					$postIdsToDelete[] = get_the_ID();
			}
			if(!isset($meta_values['ad_key']) && !isset($meta_values['ad_key'][0])){
					$postIdsToDelete[] = get_the_ID();
			}
			else {
					$adKey = $meta_values['ad_key'][0];
					if (in_array($adKey, $adKeys)){ // Compare to predecessors
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
		$query->reset();
}
		// do_action('kfz_web_after_import');
}
function formatCurrency($amount) {
	
    return number_format((float)$amount, 2, ',', '.') . ' EUR';
}
function remove_vehicle_elements() {
    if ( is_singular( 'fahrzeuge' ) ) {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Entferne den Titel
                var title = document.querySelector('.entry-title');
                if (title) title.remove();
                
                // Entferne das Thumbnail
                var thumbnail = document.querySelector('.post-thumbnail');
                if (thumbnail) thumbnail.remove();
                
                // Entferne den Autor, die Kategorie, das Datum und die Tags
                var meta = document.querySelector('.entry-meta');
                if (meta) meta.remove();
                
                // Entferne die Kommentare
                var comments = document.querySelector('#comments');
                if (comments) comments.remove();
            });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'remove_vehicle_elements' );
function remove_vehicle_thumbnail() {
    if ( is_singular( 'fahrzeuge' ) ) {
        add_filter( 'post_thumbnail_html', '__return_empty_string' );
    }
}
add_action( 'wp', 'remove_vehicle_thumbnail' );