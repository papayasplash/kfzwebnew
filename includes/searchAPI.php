<?php
/*
* New API 
* Version 0.2.0
*/
/**
 * Only for debugging purposes.
 * 
 * @param unknown $message
*/

include_once  dirname( __FILE__ ) . "/mob_config.php";
function mob_htmlToOptions($array, $includeAny=true, $selected=''){
	$html='<option value="" >Any</option>'."\r\n";
	foreach($array as $key=>$value){
		$html.='<option value="'.$key.'"'. ($selected==$key? ' selected="selected"':'') .'>'.$value.'</option>'."\r\n";
	}
	return $html;
}

class mob_searchAPI {
	var $url, $username, $password;
	var $language;
	var $xml;
	function __construct($url, $username, $password, $language){
		// $this->url=$url; // Replaced for new API 2016
		$license 	= get_option( 'mob_license_key' );
		$status 	= get_option( 'mob_license_status' );
		if( $status !== false && $status == 'valid' ) { 
		$this->url 		= 'https://services.mobile.de/'; // Inserted for new API 2016
		}
		$this->username = $username;
		$this->password = $password;
		$this->language = $language;
	}
	function mob_searchAPI($url, $username, $password, $language){
		self::__construct();
	}
	/*****************************************************************************
 	* 
 	* From Joomla! (Globals proudly inherited from John)
 	* 
 	***************/
	public function getAllAds ()
	{
		/*
		 * WARNING: Retrieving too many ads at once can cause problems. If the
		 * script stops without being finished, you should increase $x in
		 * set_time_limit($x);
		 */
		$adKeys = $this->getAdKeys();
		$allAds = $this->getAds($adKeys);
		// Insert array of adKeys due to performance reasons.
		$allAds['intern_adKeys'] = $adKeys;
		return $allAds;
	}
	public function getAdKeys ()
	{
		$adKeys = array();
		$this->sendRequest('search-api/search?page.size=100'); // Inserted for new API 2016
		$adList = $this->xml;
		if ($adList != NULL) {
			$adKeys = $this->treeToArray($adKeys, $adList[0]->xpath('//ad:ad'));
			/************************
			 * Inserted to obtain "max-pages"
			 * New API 2016
			 * 
			 * Start
			 */
			$maxP = $adList->xpath ( '//search:max-pages' );
			if (isset($maxP[0])) {
				$maxPages = $maxP[0];
			} else {
				$maxPages = 1;
			}
			/*
			 * Inserted to obtain "max-pages"
			 * New API 2016
			 * 
			 * End
			 ****************/
			for ($i = 2; $i <= $maxPages; $i ++) {
				$this->sendRequest('search-api/search?page.size=100&page.number=' . $i);
				$adList = $this->xml;
				if ($adList != NULL) {
					$adKeys = $this->treeToArray($adKeys,
							$adList[0]->xpath('//ad:ad'));            
				}
			}
		} else {
			$this->error = 'Internal Error: sendRequest delivered no result!';
		}
		return $adKeys;
	}
	public function getAds ($adKeys)
	{
		$vehicles = array();
		if ($adKeys != NULL) {
			$ads = array();
			$j = 1;
			foreach ($adKeys as $adKey) {
				$vehicle = $this->getAdByKey($adKey); // Using Johns Function here.
				//array_push($vehicles, $vehicle);
				$vehicles[] = $vehicle; 
			}
			return $vehicles;
		} else {
			// echo " ERROR: $adKeys == NULL";
			echo '<div class="notice notice-warning"><p>
			<strong>Error</strong> connecting. '. print_r($adkey) .'
			</p></div>';
			$this->error = 'Internal Error: No adKeys to retrieve advertisements from!';
		}
	}
	private function treeToArray ($array, $tree)
	{
		foreach ($tree as $node) {
			// array_push($array, (string) $node['key']);
			// Faster alternative:
			$array[] = (string)$node['key'];
		}
		return $array;
	}
	function getAirbags(){
		global $mob_data;
		return $mob_data['airbags'];
	}
	function getClasses(){
		global $mob_data;
		return $mob_data['classes'];
	}
	function getCategories($class){
		global $mob_data;
		return $mob_data['categories'][$class];
	}
	function getBrands($class){
		global $mob_data;
		return $mob_data['brands'][$class];
	}
	function getModels($class, $brandKey){
		global $mob_data;
		return $mob_data['models'][$class][$brandKey];
	}
	function getFuels(){
		global $mob_data;
		return $mob_data['fuels'];
	}
	function getGearboxes(){
		global $mob_data;
		return $mob_data['gearboxes'];
	}
	function getAirbags_(){
		$this->sendRequest('refdata/airbags');
		return $this->getReference();
	}
	function getCategories_(){
		$this->sendRequest('refdata/classes/Car/categories'); // Inserted API 2016

		return $this->getReference();
	}
	function getBrands_(){
		$this->sendRequest('refdata/classes/Car/makes'); // Inserted API 2016
		return $this->getReference();
	}
	function getModels_($brandKey){
		$this->sendRequest('refdata/classes/Car/makes/'.$brandKey.'/models'); // Inserted API 2016
		return $this->getReference();
	}
	function getFuels_(){
		$this->sendRequest('refdata/fuels'); // Inserted API 2016
		return $this->getReference();
	}
	function getGearboxes_(){
		$this->sendRequest('refdata/gearboxes'); // Inserted API 2016
		return $this->getReference();
	}
	function getCarSeals_(){
		$this->sendRequest('refdata/sites/GERMANY/classes/Car/usedcarseals'); // Inserted API 2016
		return $this->getReference();
	}
	function doSearch($data){
		$this->sendRequest($this->buildSearchQuery($data));
		$data=$this->getResult();
		return $data;
	}
	function getAdByKey($key){
		$this->sendRequest ( 'search-api/ad/'.$key ); // Inserted new API 2016
		return $this->getAd($key);
	}
	function buildSearchQuery($data){
		//build search query
		$query='';
		return 'search-api/search?' .$query; // Inserted API 2016
	}

	function sendRequest($query){
		global $mob_data;
		$b64 = base64_encode($this->username.':'.$this->password);
		$auth = "Authorization: Basic $b64";
		$opts = array (
			'http' => array (
				'method' => "GET",
					'header' => $auth . "\r\n".'User-Agent: PHP'."\r\nAccept-Language: ".$this->language
			)
		);

		$context = stream_context_create($opts);
		$fp = @fopen($this->url.$query, 'r', false, $context);
		if($fp!=NULL){
			$result = "";
			while ($str = fread($fp,1024)) {
				$result .= $str;
			}
			fclose($fp);
			$strip_result =  preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $result);
			$this->xml = new SimpleXMLElement($strip_result);
		}else{
			$this->error='Connection Error: could not connect to '.$this->url;
		}
	}	
	function getReference(){
		$namespaces = $this->xml->getDocNamespaces();
		$ns="";
		foreach($namespaces as $prefix => $namespace){
			$this->xml->registerXPathNamespace($prefix,$namespace);
			if($namespace=="http://services.mobile.de/schema/reference"){
				$reference_ns=$prefix;
			}
			if($namespace=="http://services.mobile.de/schema/resource"){
				$resource_ns=$prefix;
			}
		}
		$data=array();
		foreach($this->xml->xpath('/'.$reference_ns.':reference/'.$reference_ns.':item') as $item) {		
			$key=(string)$item['key'];
			$desc=$item->xpath($resource_ns.':local-description')[0];
			$data[$key]=(string)$desc;
		}
		return $data;
	}
	//Method to parse result XML and extract required data into associative array.
	function getResult(){
		if($this->xml != NULL) {
			$namespaces = $this->xml->getDocNamespaces();
			foreach($namespaces as $prefix => $namespace){
				$this->xml->registerXPathNamespace($prefix,$namespace);
				if($namespace=="http://services.mobile.de/schema/search"){
					$search_ns=$prefix;
				}
				if($namespace=="http://services.mobile.de/schema/ad"){
					$ad_ns=$prefix;
				}
			}
			$this->maxPages=$this->xml->xpath('/'.$search_ns.':result')[0]['max-pages'];
			$this->currentPage=$this->xml->xpath('/'.$search_ns.':result')[0]['current-page'];
			$list=array();
			foreach($this->xml->xpath('/'.$search_ns.':result/'.$ad_ns.':ad') as $item) {	
				$key=(string)$item['key'];
				$list[$key]=$this->extractAdData($item);
			}
			return $list;
		}
	}
	function getAd($adKey){
		$this->xml->registerXPathNamespace('ad','"http://services.mobile.de/schema/ad');
		$vehicles = $this->extractAdData($this->xml->xpath('/'.'ad'.':ad')[0]);
		$vehicles['ad_key'] = $adKey;
		return $vehicles;
	}
	function extractAdData($item){
		$namespaces = $this->xml->getDocNamespaces();
		foreach($namespaces as $prefix => $namespace){
			$item->registerXPathNamespace($prefix,$namespace);
			if($namespace=="http://services.mobile.de/schema/resource"){
				$resource_ns=$prefix;
			}
			if($namespace=="http://services.mobile.de/schema/ad"){
				$ad_ns=$prefix;
			}
			if($namespace=="http://services.mobile.de/schema/seller"){
				$seller_ns=$prefix;
			}
		}
		$mob_data=array();
		$temp = $item->xpath('//ad:creation-date');
		if (isset($temp[0]['value'])) {
				$mob_data['creation-date'] = (string) $temp[0]['value'];
		} else {
			$mob_data['creation-date'] = "";
		}
		$temp = $item->xpath('//ad:modification-date');
		if (isset($temp[0]['value'])) {
			$mob_data['modification_date'] = (string) $temp[0]['value'];
		} else {
			$mob_data['modification_date'] = "";
		}
		$temp = $item->xpath($ad_ns.':images'.'/'.$ad_ns.':image'.'/'.$ad_ns.':representation');
		if (!empty($temp)){
			foreach($item->xpath($ad_ns.':images'.'/'.$ad_ns.':image'.'/'.$ad_ns.':representation') as $image){
				if((string)$image['size'] == 'XXXL') {
					$mob_data['images'][]=$image['url'];
				}
			}
		}
		else{
			$mob_data['images']= "";
		}
		$temp = $item->xpath($ad_ns . ':vehicle/' . $ad_ns . ':features' . '/' . $ad_ns . ':feature' . '/' . $resource_ns . ':local-description');
		if (!empty($temp)) {
			foreach ($temp as $feature) {
				$mob_data['features'][] = (string) $feature[0];
			}
			if (!empty($mob_data['features'])) {
				$mob_data['features'] = implode("</li><li>", $mob_data['features']);
			}
		} else {
			$mob_data['features'] = "";
		}	

		$feature_key = $item->xpath($ad_ns . ':vehicle/' . $ad_ns . ':features' . '/' . $ad_ns . ':feature');
		$feature_value = $item->xpath($ad_ns . ':vehicle/' . $ad_ns . ':features' . '/' . $ad_ns . ':feature' . '/' . $resource_ns . ':local-description');
		if (!empty($temp)) {
			foreach ($feature_key as $index => $feature) {
				$key = (string) $feature['key'];
				$value = $feature_value[$index];
				$mob_data[$key] = (string) $value;
			}
		}

		$mob_data['country']=(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':country-code')[0]['value'];
		$mob_data['zipcode']=(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':zipcode')[0]['value'];
		$mob_data['seller']=(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':street')[0]['value'].', './/seller
		(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':zipcode')[0]['value'].' '.
		(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':city')[0]['value'].' '.
		(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':country-code')[0]['value'];
		$mob_data['seller_id'] = (string)$item->xpath('//'.$seller_ns.':seller')[0]['key'];
		$mob_data['seller_company_name'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':company-name')[0]['value'];
		$mob_data['seller_street'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':street')[0]['value'];
		$mob_data['seller_zipcode'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':zipcode')[0]['value'];
		$mob_data['seller_city'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':city')[0]['value'];
		$mob_data['seller_country'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':address/'.$seller_ns.':country-code')[0]['value'];
		$mob_data['seller_email'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':email')[0]['value'];
		$mob_data['seller_phone_country_calling_code'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':phone')[0]['country-calling-code'];
		$mob_data['seller_phone_area_code'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':phone')[0]['area-code'];
		$mob_data['seller_phone_number'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':phone')[0]['number'];
		$mob_data['seller_homepage'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':homepage')[0]['value'];
		$mob_data['seller_since'] = (string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':mobile-seller-since')[0]['value'];
		$mob_data['sellerType']=(string)$item->xpath('//'.$seller_ns.':seller/'.$seller_ns.':type')[0]['value'];

		$enrichedDescription = $item->xpath('//ad:enrichedDescription');
		if (isset($enrichedDescription[0])){

			/*
			 * Due to speed reasons we include the formatter here.
			 * Doing so, we risk some side effects. --bth 2014-10-29
			 * 
			 */
			$temp = $enrichedDescription[0];
			{
			 /**
			   * There are four different ways to format a vehicles description to be displayed at the mobile.de site.
			   *
			   * 1. Zeilenumbruch: TEXT\\ (doppelter Backslash, also das Zeichen neben dem ß)
			   * 2. Fettdruck: **TEXT** (doppelter Stern am Anfang und Ende des FETT-Textes)
			   * 3. Trennlinie: ---- (vierfaches Minuszeichen)
			   * 4. Aufzählung: * TEXT\\ (Stern, Leerzeichen, TEXT und zum Abschluss ein doppelter Backslash)
			   *
			   */
				// Rule 2: Convert '**foo**' to <b>foo</b>
				$temp = preg_replace( "/[*][*]([^*]+)[*][*]/", '<b>${1}</b>', $temp);
				// Rule 4: Convert '*foo\\' to '<li>foo</li>'
				$temp = preg_replace( "/[*]([^\\\]+)[\\\]/", '<li>${1}</li>', $temp);
				// Rule 1: Convert '\\' to <br>
				$breaks = $temp;
				// log_me($breaks);
				$result = str_replace("\\\\", '<br>', $breaks);
				// $temp = stripcslashes($temp);
				// log_me('slashes stripped');
				// log_me($temp);
				// Rule 3: Convert '----' to '<hr>'
				$temp = str_replace("----", "<hr />", $result);
			}
			$mob_data['enriched_description'] = $temp;
		}
		else {
			$mob_data['enriched_description'] = "";
		}
		
		$temp = $item->xpath('//ad:construction-year');
		if (isset($temp[0])) {
			$mob_data['construction-year'] = (string) $temp[0]['value'];
		} else {
			$mob_data['construction-year'] = "";
		}


		$temp = $item->xpath('//ad:construction-date');
		if (isset($temp[0])) {
			$mob_data['construction-date'] = (string) $temp[0]['value'];
		} else {
			$mob_data['construction-date'] = "";
		}

		$temp = $item->xpath('//ad:licensed-weight');
		if (isset($temp[0])) {
			$mob_data['licensed-weight'] = (string) $temp[0]['value'];
		} else {
			$mob_data['licensed-weight'] = "";
		}

		$temp = $item->xpath('//ad:number-of-bunks');
		if (isset($temp[0])) {
			$mob_data['number-of-bunks'] = (string) $temp[0]['value'];
		} else {
			$mob_data['number-of-bunks'] = "";
		}

		$temp = $item->xpath('//ad:vehicle/ad:specifics/ad:dimension');
		if (isset($temp[0])) {
			if (isset($temp[0]['length'])) {
				$mob_data['length'] = (string) $temp[0]['length'];
			} else {
				$mob_data['length'] = "";
			}
			if (isset($temp[0]['width'])) {
				$mob_data['width'] = (string) $temp[0]['width'];
			} else {
				$mob_data['width'] = "";
			}
			if (isset($temp[0]['height'])) {
				$mob_data['height'] = (string) $temp[0]['height'];
			} else {
				$mob_data['height'] = "";
			}
		} else {
			$mob_data['length'] = "";
			$mob_data['height'] = "";
			$mob_data['width'] = "";
		}

		$invKey = $item->xpath('//ad:seller-inventory-key');
		if (isset($invKey[0]['value'])) {
			$mob_data['seller-inventory-key'] = (string) $invKey[0]['value'];
		} else {
			$mob_data['seller-inventory-key'] = "";
		}

		$temp = $item->xpath('//ad:class/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['class'] = (string) $temp[0];
		} else {
			$mob_data['class'] = "";
		}

		$temp = $item->xpath('//ad:category/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['category'] = (string) $temp[0];
		} else {
			$mob_data['category'] = "";
		}

		$temp = $item->xpath('//ad:make/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['make'] = (string) $temp[0];
		} else {
			$mob_data['make'] = "";
		}

		$temp = $item->xpath('//ad:model/resource:local-description');
		$model_description = $item->xpath('//ad:model-description');
		$model_description_string = (string) $model_description[0]['value'];
		$model_fallback_from_description = explode(' ',$model_description_string);
		if (isset($temp[0])) {
			$mob_data['model'] = (string) $temp[0];
		} else {
			$mob_data['model'] = $model_fallback_from_description[0];
		}

		$temp = $item->xpath('//ad:model-description');
		if (isset($temp[0]['value'])) {
			$mob_data['model_description'] = (string) $temp[0]['value'];
		} else {
			$mob_data['model_description'] = "";
		}

		// since 1.9.3 check if type is GTI R ...

		$temp = $item->xpath('//ad:model-description');
	 	if (isset($temp[0]['value'])) {
	 		$string = $temp[0]['value'];
	 		if (strpos($string, 'GTI') !== false ) {
	 			$mob_data['model_variant'] = "GTI";
	 		}
	 		elseif (strpos($string, ' R ') !== false ) {
	 			$mob_data['model_variant'] = "R";
	 		}
	 		elseif (strpos($string, 'GTD') !== false ) {
	 			$mob_data['model_variant'] = "GTD";
	 		}
	 		elseif (strpos($string, 'R-LINE') !== false ) {
	 			$mob_data['model_variant'] = "R-LINE";
	 		}
	 		else {
	 			$mob_data['model_variant'] = "";
	 		}
	 	} else {
	 		$mob_data['model_variant'] = "";
	 	}
		

		$temp = $item->xpath(
				'//ad:exterior-color/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['exterior_color'] = (string) $temp[0];
		} else {
			$mob_data['exterior_color'] = "";
		}

		$temp = $item->xpath('//ad:mileage');
		if (isset($temp[0]['value'])) {
			$temp = (string) $temp[0]['value'];
			$mob_data['mileage_raw'] = $temp; // Saving a raw version for further use.
		/*
		 * Prepare mileage class.
		 * Strongly opposed by bth.
		 */
		$mileage = $mob_data['mileage_raw'];
		if ($mileage > 250000)
			$mileageclass = "über 250.000 km";	
		elseif ($mileage > 200000)
			$mileageclass = "von 200.000 bis 250.000 km";
		elseif ($mileage > 150000)
			$mileageclass = "von 150.000 bis 200.000 km";
		elseif ($mileage > 100000)
			$mileageclass = "von 100.000 bis 150.000 km";
		elseif ($mileage > 75000)
			$mileageclass = "von 75.000 bis 100.000 km";
		elseif ($mileage > 50000)
			$mileageclass = "von 50.000 bis 75.000 km";
		elseif ($mileage > 40000)
			$mileageclass = "von 40.000 bis 50.000 km";	
		elseif ($mileage > 30000)
			$mileageclass = "von 30.000 bis 40.000 km";
		elseif ($mileage > 20000)
			$mileageclass = "von 20.000 bis 30.000 km";
		elseif ($mileage > 10000)
			$mileageclass = "von 10.000 bis 20.000 km";	
		elseif ($mileage > 1000)
			$mileageclass = "von 1.000 bis 10.000 km";
		elseif ($mileage >= 0 || $mileage == "")
			$mileageclass = "bis 1.000 km";
		if (isset ( $mileageclass )) {
			$mob_data['mileage_class'] = $mileageclass;
		} else {
			$mob_data['mileage_class'] = "";
		}	

		
		/*
		* Convert mileage from 29999 to 29.999
		*/
		$temp = number_format($temp, 0, ',', '.');
		$mob_data['mileage'] = $temp;
		} else {
			$mob_data['mileage'] = "";
		}


		$temp = $item->xpath('//ad:first-registration');
		if (isset($temp[0]['value'])) {
			$mob_data['first_registration'] = (string) $temp[0]['value'];
			$temp = new DateTime($mob_data['first_registration']);
			$mob_data['first_registration_year'] = $temp->format('Y');
		} else {
			$mob_data['first_registration'] = "";
			$mob_data['first_registration_year'] = "";
		}
		//emissionClass
		$temp = $item->xpath('//ad:emission-class/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['emission_class'] = (string) $temp[0];
		} else {
			$mob_data['emission_class'] = "";
		}

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0])) {
			$mob_data['enkv-compliant'] = (string) $temp[0]['enkv-compliant'];
		} else {
			$mob_data['enkv-compliant'] = "";
		}

		

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['co2-emission'])) {
			$mob_data['co2_emission'] = (string) $temp[0]['co2-emission'];
		}
		else {
			$mob_data['co2_emission'] = "";
		}

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['inner'])) {
			$mob_data['inner'] = (string) $temp[0]['inner'];
		}
		else {
			$mob_data['inner'] = "";
		}

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['outer'])) {
			$mob_data['outer'] = (string) $temp[0]['outer'];
		}
		else {
			$mob_data['outer'] = "";
		}
			 
		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['combined'])) {
			$mob_data['combined'] = (string) $temp[0]['combined'];
		}
		else {
			$mob_data['combined'] = "";
		}

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['unit'])) {
			$mob_data['unit'] = (string) $temp[0]['unit'];
		} else {
			$mob_data['unit'] = "";
		}

		$emissionSticker = $item->xpath('//ad:emission-sticker/resource:local-description');
		if (isset($emissionSticker[0])) {
			$mob_data['emissionSticker'] = (string) $emissionSticker[0];
		} else {
			$mob_data['emissionSticker'] = "";
		}

		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0]['energy-efficiency-class'])) {
			$mob_data['energy-efficiency-class'] = (string) $temp[0]['energy-efficiency-class'];
		} else {
			$mob_data['energy-efficiency-class'] = "";
		}





















		if (isset($temp[0]['energy-efficiency-class'])) {
			$mob_data['energy-efficiency-class'] = (string) $temp[0]['energy-efficiency-class'];
		} else {
			$mob_data['energy-efficiency-class'] = "";
		}
		$temp = $item->xpath('//ad:fuel/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['fuel'] = (string) $temp[0];
		} else {
			$mob_data['fuel'] = "";
		}
		$temp = $item->xpath('//ad:power');
		if (isset($temp[0]['value'])){
			$mob_data['power'] = (string) $temp[0]['value'];
		}
		else {
			$mob_data['power'] = "";
		}
		$temp = $item->xpath('//ad:gearbox/resource:local-description');
		if (isset($temp[0])){
			$mob_data['gearbox'] = (string) $temp[0];
		}
		else {
			$mob_data['gearbox'] = "";
		}
		$temp = $item->xpath('//ad:cubic-capacity');
		if (isset($temp[0]['value'])){
			$mob_data['cubic_capacity'] = (string) $temp[0]['value'];
		}
		else {
			$mob_data['cubic_capacity'] = "";
		}
		$temp = $item->xpath('//ad:description');
		if (isset($temp[0])){
			$mob_data['description'] = (string) $temp[0];
		}
		else {
			$mob_data['description'] = "";
		}
		
		$temp = $item->xpath('//ad:price/ad:consumer-price-amount');
		if (isset($temp[0]['value'])){
		$temp = (string) $temp[0]['value'];
		$mob_data['price_dropdown'] = $temp; // Saving a raw version for further use.	
			/*
			 * Prepare price class.
			 */

			$price = $mob_data['price_dropdown'];
			if ($price > 80000.00)
				$priceclass = "80000 bis 90000 € und mehr";
			elseif ($price > 70000.00)
				$priceclass = "70000 bis 80000 €";
			elseif ($price > 60000.00)
				$priceclass = "60000 bis 70000 €";
			elseif ($price > 55000.00)
				$priceclass = "55000 bis 60000 €";
			elseif ($price > 50000.00)
				$priceclass = "50000 bis 55000 €";
			elseif ($price > 45000.00)
				$priceclass = "45000 bis 50000 €";
			elseif ($price > 40000.00)
				$priceclass = "40000 bis 45000 €";
			elseif ($price > 35000.00)
				$priceclass = "35000 bis 40000 €";
			elseif ($price > 30000.00)
				$priceclass = "30000 bis 35000 €";
			elseif ($price > 27500.00)
				$priceclass = "27500 bis 30000 €";
			elseif ($price > 25000.00)
				$priceclass = "25000 bis 27500 €";
			elseif ($price > 22500.00)
				$priceclass = "22500 bis 25000 €";
			elseif ($price > 20000.00)
				$priceclass = "20000 bis 22500 €";
			elseif ($price > 17500.00)
				$priceclass = "17500 bis 20000 €";
			elseif ($price > 15000.00)
				$priceclass = "15000 bis 17500 €";
			elseif ($price > 14000.00)
				$priceclass = "14000 bis 15000 €";
			elseif ($price > 13000.00)
				$priceclass = "13000 bis 14000 €";
			elseif ($price > 12000.00)
				$priceclass = "12000 bis 13000 €";
			elseif ($price > 11000.00)
				$priceclass = "11000 bis 12000 €";
			elseif ($price > 10000.00)
				$priceclass = "10000 bis 11000 €";
			elseif ($price > 9000.00)
				$priceclass = "9000 bis 10.000 €";
			elseif ($price > 8000.00)
				$priceclass = "8000 bis 9000 €";
			elseif ($price > 7000.00)
				$priceclass = "7000 bis 8000 €";
			elseif ($price > 6000.00)
				$priceclass = "6000 bis 7000 €";
			elseif ($price > 5000.00)
				$priceclass = "5000 bis 6000 €";
			elseif ($price > 4500.00)
				$priceclass = "4500 bis 5000 €";
			elseif ($price > 4000.00)
				$priceclass = "4000 bis 4500 €";
			elseif ($price > 3500.00)
				$priceclass = "3500 bis 4000 €";
			elseif ($price > 3000.00)
				$priceclass = "3000  bis 3500 €";
			elseif ($price > 2500.00)
				$priceclass = "2500  bis 3000 €";	
			elseif ($price > 2000.00)
				$priceclass = "2000 bis 2500 €";
			elseif ($price > 1500.00)
				$priceclass = "1500 bis 2000 €";
			elseif ($price > 1000.00)
				$priceclass = "1000 bis 1500 €";	
			elseif ($price > 500.00)
				$priceclass = "500 bis 1000 €";
			elseif ($price >= 0 || $price == "")
				$priceclass = "von 0 bis 500 €";
			if (isset ( $priceclass )) {
				$mob_data['price_dropdown'] = $priceclass;
			} else {
				$mob_data['price_dropdown'] = "";
			}
			// price_dropdown
			/*
			 * Prepare price.
			*
			* 1. Converts to German price format. 
			* 	 19802.55 -> 19.302,55
			*  
			* 2. Cuts zeros if there is no cent value.
			*    13.890,00 --> 13.890
			*/
			// Convert to German price format.
			// $temp = (string) $temp[0]['value'];
			// $mob_data['price_raw'] = $temp; // Saving a raw version for further use.
			$temp = number_format(
					$temp, 2, ',', '.');
			$priceParts = explode(',', $temp, 2);
			// Store price without Cent value.
			$mob_data['price_raw_short'] = str_replace(".", "", $priceParts[0]);
			// Cut the Cent value if 00.
			if ($priceParts[1] == "00") {
				$temp = $priceParts[0];
			}
			$mob_data['price'] = $temp; // This variable is called consumer_price_amount in Joomla!. --bth 2014-10-29 15:53:27 
			// echo " Price after calculation = ". $mob_data['price'] . " AdKey = " . $mob_data['ad_key'];
		}
		else {
			$mob_data['price'] = "";
		}

		$temp = $item->xpath('//ad:vatable');
		if (isset($temp[0]['value'])){
			$mob_data['vatable'] = (string) $temp[0]['value'];
		}
		else {
			$mob_data['vatable'] = "";
		}

		$temp = $item->xpath('//ad:detail-page');
		if (isset($temp[0])) {
			$mob_data['detail_page'] = (string) $temp[0]['url'];
		} else {
			$mob_data['detail_page'] = "";
		}
		$temp = $item->xpath('//ad:dealer-price-amount');
		if (isset($temp[0])) {
			$mob_data['dealer-price-amount'] = (string) $temp[0]['value'];
		} else {
			$mob_data['dealer-price-amount'] = "";
		}
		$temp = $item->xpath('//ad:net');
		if (isset($temp[0])) {
			$mob_data['net'] = (string) $temp[0]['value'];
		} else {
			$mob_data['net'] = "";
		}

		$temp = $item->xpath('//ad:included-delivery-costs');
		if (isset($temp[0])) {
			$mob_data['included-delivery-costs'] = (string) $temp[0]['value'];
		} else {
			$mob_data['included-delivery-costs'] = "";
		}
	

		 $temp = $item->xpath('//ad:damage-and-unrepaired');
		 if (isset($temp[0]['value'])) {
		 $mob_data['damage-and-unrepaired'] = (string) $temp[0]['value'];
		 } else {
		 $mob_data['damage-and-unrepaired'] = "";
		 }

		 $temp = $item->xpath('//ad:accident-damaged');
		 if (isset($temp[0]['value'])) {
		 $mob_data['accident_damaged'] = (string) $temp[0]['value']; 
		 } else {
		 $mob_data['accident_damaged'] = "";
		 }	
		$temp = $item->xpath('//ad:roadworthy');
		if (isset($temp[0]['value'])) {
			$mob_data['road_worthy'] = (string) $temp[0]['value'];
		} else {
			$mob_data['road_worthy'] = "";
		}	
		$temp = $item->xpath('//ad:roadworthy');
		if (isset($temp[0]['value'])) {
			$mob_data['road-worthy'] = (string) $temp[0]['value'];
		} else {
			$mob_data['road-worthy'] = "";
		}
		 $temp = $item->xpath('//ad:accident-damaged');
		 if (isset($temp[0]['value'])) {
		 $mob_data['accident_damaged'] = (string) $temp[0]['value'];	 
		 } else {
		 $mob_data['accident_damaged'] = "";
		 }
		$temp = $item->xpath('//ad:exhaust-inspection');
		if (isset($temp[0])) {
			$mob_data['exhaust-inspection'] = (string) $temp[0]['value'];	 
		} else {
			$mob_data['exhaust-inspection'] = "";
		}	
		$temp = $item->xpath('//ad:general-inspection');
		if (isset($temp[0])) {
			$mob_data['nextInspection'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['nextInspection'] = "";
		}		
		$temp = $item->xpath('//ad:door-count/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['door-count'] = (string) $temp[0];
		} else {
			$mob_data['door-count'] = "";
		}
		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0])) {
			$mob_data['petrol-type'] = (string) $temp[0]['petrol-type'];	 
		} else {
			$mob_data['petrol-type'] = "";
		}
		$temp = $item->xpath('//ad:emission-fuel-consumption');
		if (isset($temp[0])) {
			$mob_data['combined-power-consumption'] = (string) $temp[0]['combined-power-consumption'];		 
		} else {
			$mob_data['combined-power-consumption'] = "";
		}
		$temp = $item->xpath('//ad:kba');
		if (isset($temp[0])) {
			if (isset($temp[0]['hsn'])) {
				$mob_data['hsn'] = (string) $temp[0]['hsn'];
			}
			if (isset($temp[0]['tsn'])) {
				$mob_data['tsn'] = (string) $temp[0]['tsn'];
			}
		} else {
			$mob_data['hsn'] = "";
			$mob_data['tsn'] = "";
		}
		$temp = $item->xpath('//ad:schwacke-code');
		if (isset($temp[0])) {
			$mob_data['schwacke-code'] = (string) $temp[0]['value'];
		} else {
			$mob_data['schwacke-code'] = "";
		}
		$temp = $item->xpath('//ad:climatisation/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['climatisation'] = (string) $temp[0];
		} else {
			$mob_data['climatisation'] = "";
		}
		$temp = $item->xpath('//ad:axles');
		if (isset($temp[0])) {
			$mob_data['axles'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['axles'] = "";
		}		
		$temp = $item->xpath('//ad:load-capacity');
		if (isset($temp[0])) {
			$mob_data['loadCapacity'] = (string) $temp[0]['value'];
		} else {
			$mob_data['loadCapacity'] = "";
		}		
		$temp = $item->xpath('//ad:num-seats');
		if (isset($temp[0])) {
			$mob_data['num-seats'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['num-seats'] = "";
		}	
		$temp = $item->xpath('//ad:operating-hours');
		if (isset($temp[0])) {
			$mob_data['operating-hours'] = (string) $temp[0]['value'];
		} else {
			$mob_data['operating-hours'] = "";
		}	

		$temp = $item->xpath('//ad:installation-height');
		if (isset($temp[0])) {
			$mob_data['installation-height'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['installation-height'] = "";
		}		
		$temp = $item->xpath('//ad:lifting-capacity');
		if (isset($temp[0])) {
			$mob_data['lifting-capacity'] = (string) $temp[0]['value'];	 
		} else {
			$mob_data['lifting-capacity'] = "";
		}		
		$temp = $item->xpath('//ad:lifting-height');
		if (isset($temp[0])) {
			$mob_data['lifting-height'] = (string) $temp[0]['value'];	 
		} else {
			$mob_data['lifting-height'] = "";
		}		
		$temp = $item->xpath('//ad:driving-mode/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['driving-mode'] = (string) $temp[0];	 
		} else {
			$mob_data['driving-mode'] = "";
		}	

		$temp = $item->xpath('//ad:driving-cab/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['driving-cab'] = (string) $temp[0];		 
		} else {
			$mob_data['driving-cab'] = "";
		}		
		$temp = $item->xpath('//ad:condition/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['condition'] = (string) $temp[0];
		} else {
			$mob_data['condition'] = "";
		}
		$temp = $item->xpath('//ad:usage-type/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['usage-type'] = (string) $temp[0];
		} else {
			$mob_data['usage-type'] = "";
		}
		
	   /*
		* Prepare addition.
		*
		* $mob_data['addition'] will be e.g. "Oldtimer"
		* if not set, it will be e.g. "Neuwagen".
		*
		* If not wanted, comment out.
		*
		*/

		if ($mob_data['usage-type'] != '') {
			 $mob_data['addition'] = $mob_data['usage-type'];
		} else {
			 $mob_data['addition'] = $mob_data['condition'];
		}
	
		$temp = $item->xpath('//ad:delivery-date');
		if (isset($temp[0])) {
			$temp = (string) $temp[0]['value'];
			// Cut the +hh
			$temp = explode('+', $temp, 2)[0];
			$mob_data['delivery-date'] = $temp;
			// Calculate "available moment".
			$availableFrom = new DateTime($temp);
			$now = new DateTime();
			if ($availableFrom < $now){
				$mob_data['available-from'] = "Sofort";
			} else {
				$mob_data['available-from'] = $availableFrom->format('d.m.Y');
			}
		} else {
			$mob_data['delivery-date'] = "";
			$mob_data['available-from'] = "";
		}
	
		$temp = $item->xpath('//ad:delivery-period');
		if (isset($temp[0])) {
			$mob_data['delivery-period'] = (string) $temp[0]['value'];	 
		} else {
			$mob_data['delivery-period'] = "";

		}
		/*
		* Change Request Test this! --bth 2014-10-29 01:21:56 
		*/
		$temp = $item->xpath('//ad:wheel-formula/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['wheel-formula'] = (string) $temp[0];
		} else {
			$mob_data['wheel-formula'] = "";
		}
		$temp = $item->xpath('//ad:hydraulic-installation/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['hydraulic-installation'] = (string) $temp[0];
		} else {
			$mob_data['hydraulic-installation'] = "";
		}
		$temp = $item->xpath('//ad:europallet-storage-spaces');
		if (isset($temp[0])) {
			$mob_data['europallet-storage-spaces'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['europallet-storage-spaces'] = "";
		}  		
		$temp = $item->xpath('//ad:shipping-volume');
		if (isset($temp[0])) {
			$mob_data['shipping-volume'] = (string) $temp[0]['value'];		 
		} else {
			$mob_data['shipping-volume'] = "";
		}		
		$temp = $item->xpath('//ad:loading-space');
		if (isset($temp[0])) {
			if (isset($temp[0]['length'])) {
				$mob_data['loading-space-length'] = (string) $temp[0]['length'];
			} else {
				$mob_data['loading-space-length'] = "";
			}
			if (isset($temp[0]['width'])) {
				$mob_data['loading-space-width'] = (string) $temp[0]['width'];
			} else {
				$mob_data['loading-space-width'] = "";
			}
			if (isset($temp[0]['loading-space-height'])) {
				$mob_data['height'] = (string) $temp[0]['height'];
			} else {
				$mob_data['loading-space-height'] = "";
			}
		} else {
			$mob_data['loading-space-length'] = "";
			$mob_data['loading-space-height'] = "";
			$mob_data['loading-space-width'] = "";
		}

		$temp = $item->xpath('//ad:identification-number');
		if (isset($temp[0])) {
			$mob_data['identification-number'] = (string) $temp[0]['value'];
		} else {
			$mob_data['identification-number'] = "";
		}
		$temp = $item->xpath('//ad:interior-color/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['interior-color'] = (string) $temp[0];
		} else {
			$mob_data['interior-color'] = "";
		}
		$temp = $item->xpath('//ad:exterior-color/ad:metalic');
		if (isset($temp[0]['value'])) {
			$mob_data['metallic'] = (string) $temp[0]['value'];
		} else {
			$mob_data['metallic'] = "";
		}
		$temp = $item->xpath('//ad:exterior-color/ad:manufacturer-color-name');
		if (isset($temp[0]['value'])) {
			$mob_data['manufacturer-color-name'] = (string) $temp[0]['value'];
		} else {
			$mob_data['manufacturer-color-name'] = "";
		}
		$temp = $item->xpath('//ad:interior-type/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['interior-type'] = (string) $temp[0];
		} else {
			$mob_data['interior-type'] = "";
		}
		$temp = $item->xpath('//ad:airbag/resource:local-description');
		if (isset($temp[0])) {
			$mob_data['airbag'] = (string) $temp[0];		 
		} else {
			$mob_data['airbag'] = "";
		}		
		$temp = $item->xpath('//ad:number-of-previous-owners');
		if (isset($temp[0])) {
			$mob_data['number_of_previous_owners'] = (string) $temp[0];		 
		} else {
			$mob_data['number_of_previous_owners'] = "";
		}		
		$temp = $item->xpath('//ad:countryVersion');
		if (isset($temp[0]['key'])) {
			$mob_data['countryVersion'] = (string) $temp[0]['key'];		 
		} else {
			$mob_data['countryVersion'] = "";
		}	
		$temp = $item->xpath('//ad:videoUrl');
		if (isset($temp[0])) {
			$mob_data['videoUrl'] = (string) $temp[0];	 
		} else {
			$mob_data['videoUrl'] = "";
		}	
		$temp = $item->xpath('//ad:parking-assistants/resource:local-description');
		if (isset($temp[0])){
			$mob_data['parking-assistants'] = (string)$temp[0]; // Needs to be tested. --bth 2014-10-24
		} else {
			$mob_data['parking-assistants'] = "";
		}
	

		// CO2-Emissionen
		$temp = $item->xpath('//ad:emissions/ad:combined');

		if (isset($temp[0]['co2'])) {
			$mob_data['wltp-co2-emission'] = (string) $temp[0]['co2'];
		} else {
			$mob_data['wltp-co2-emission'] = "";
		}
		// CO2-Klasse auf Basis der CO2-Emissionen
		$temp = $item->xpath('//ad:emissions/ad:combined');

		if (isset($temp[0]['co2-class'])) {	
			$mob_data['wltp-co2-class'] = (string) $temp[0]['co2-class'];
		} else {
			$mob_data['wltp-co2-class'] = "";
		}
		// CO2-Emissionen (bei entladener Batterie)
		$temp = $item->xpath('//ad:emissions/ad:discharged');

		if (isset($temp[0]['co2'])) {
			$mob_data['wltp-co2-emission-discharged'] = (string) $temp[0]['co2'];
		} else {
			$mob_data['wltp-co2-emission-discharged'] = "";	
		}
		// CO2-Klasse auf Grundlage der CO2-Emissionen bei entladener Batterie
		$temp = $item->xpath('//ad:emissions/ad:discharged');

		if (isset($temp[0]['co2-class'])) {
			$mob_data['wltp-co2-class-discharged'] = (string) $temp[0]['co2-class'];
		} else {
			$mob_data['wltp-co2-class-discharged'] = "";	
		}
		// Elektrische Reichweite	
		$temp = $item->xpath('//ad:range');
		
		if (isset($temp[0]['value'])) {
			$mob_data['wltp-electric-range'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-electric-range'] = "";
		}
		// Elektrische Reichweite (EAER)
		$temp = $item->xpath('//ad:equivalent-all-electric-range');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-electric-range-equivalent-all'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-electric-range-equivalent-all'] = "";
		}
		// Verbrauch gewichtet, kombiniert Treibstoff
		$temp = $item->xpath('//ad:consumptions/ad:weighted-combined-fuel');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-weighted-combined-fuel'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-weighted-combined-fuel'] = "";
		}
		// Verbrauch gewichtet, kombiniert Strom
		$temp = $item->xpath('//ad:consumptions/ad:weighted-combined-power');
		if (isset($temp[0]['value'])) {
			$mob_data['wltp-weighted-combined-power'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-weighted-combined-power'] = "";
		}
		// Verbrauch kombiniert
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['combined'])) {
			$mob_data['wltp-combined-fuel'] = (string) $temp[0]['combined'];
		} else {
			$mob_data['wltp-combined-fuel'] = "";
		}
		// Verbrauch Innenstadt 
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['city'])) {
			$mob_data['wltp-city-fuel'] = (string) $temp[0]['city'];
		} else {
			$mob_data['wltp-city-fuel'] = "";
		}
		// Verbrauch Stadtrand
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['suburban'])) {
			$mob_data['wltp-suburban-fuel'] = (string) $temp[0]['suburban'];
		} else {
			$mob_data['wltp-suburban-fuel'] = "";
		}
		// Verbrauch Landstraße
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');
		if (isset($temp[0]['rural'])) {
			$mob_data['wltp-rural-fuel'] = (string) $temp[0]['rural'];
		} else {
			$mob_data['wltp-rural-fuel'] = "";
		}
		// Verbrauch Autobahn
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');
		if (isset($temp[0]['highway'])) {
			$mob_data['wltp-highway-fuel'] = (string) $temp[0]['highway'];
		} else {
			$mob_data['wltp-highway-fuel'] = "";
		}
		// Stromverbrauch kombiniert
		$temp = $item->xpath('//ad:consumptions/ad:power-consumption');
		if (isset($temp[0]['combined'])) {
			$mob_data['wltp-combined-power'] = (string) $temp[0]['combined'];
		} else {
			$mob_data['wltp-combined-power'] = "";
		}
		// Stromverbrauch Innenstadt
		$temp = $item->xpath('//ad:consumptions/ad:power-consumption');

		if (isset($temp[0]['city'])) {
			$mob_data['wltp-city-power'] = (string) $temp[0]['city'];
		} else {
			$mob_data['wltp-city-power'] = "";
		}
		// Stromverbrauch Stadtrand
		$temp = $item->xpath('//ad:consumptions/ad:power-consumption');

		if (isset($temp[0]['suburban'])) {
			$mob_data['wltp-suburban-power'] = (string) $temp[0]['suburban'];
		} else {
			$mob_data['wltp-suburban-power'] = "";
		}
		// Stromverbrauch Landstraße
		$temp = $item->xpath('//ad:consumptions/ad:power-consumption');

		if (isset($temp[0]['rural'])) {
			$mob_data['wltp-rural-power'] = (string) $temp[0]['rural'];
		} else {
			$mob_data['wltp-rural-power'] = "";
		}
		// Stromverbrauch Autobahn
		$temp = $item->xpath('//ad:consumptions/ad:power-consumption');

		if (isset($temp[0]['highway'])) {
			$mob_data['wltp-highway-power'] = (string) $temp[0]['highway'];
		} else {
			$mob_data['wltp-highway-power'] = "";
		}
		// Verbrauch bei entladener Batterie kombiniert
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['combined'])) {
			$mob_data['wltp-empty-combined-fuel'] = (string) $temp[0]['combined'];
		} else {
			$mob_data['wltp-empty-combined-fuel'] = "";
		}
		// Verbrauch bei entladener Batterie Innenstadt
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['city'])) {
			$mob_data['wltp-empty-city-fuel'] = (string) $temp[0]['city'];
		} else {
			$mob_data['wltp-empty-city-fuel'] = "";
		}
		// Verbrauch bei entladener Batterie Stadtrand
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['suburban'])) {
			$mob_data['wltp-empty-suburban-fuel'] = (string) $temp[0]['suburban'];
		} else {
			$mob_data['wltp-empty-suburban-fuel'] = "";
		}
		// Verbrauch bei entladener Batterie Landstraße
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['rural'])) {
			$mob_data['wltp-empty-rural-fuel'] = (string) $temp[0]['rural'];
		} else {
			$mob_data['wltp-empty-rural-fuel'] = "";
		}
		// Verbrauch bei entladener Batterie Autobahn
		$temp = $item->xpath('//ad:consumptions/ad:fuel-consumption');

		if (isset($temp[0]['highway'])) {
			$mob_data['wltp-empty-highway-fuel'] = (string) $temp[0]['highway'];
		} else {
			$mob_data['wltp-empty-highway-fuel'] = "";
		}
		// Kraftstoffpreis [Jahr]
		$temp = $item->xpath('//ad:cost-model/ad:fuel-price');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-fuel-price-year'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-fuel-price-year'] = "";
		}
		// Strompreis [Jahr]
		$temp = $item->xpath('//ad:cost-model/ad:power-price');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-power-price-year'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-power-price-year'] = "";
		}
		// Jahresdurchschnitt [Jahr]
		$temp = $item->xpath('//ad:cost-model/ad:consumption-price-year');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-consumption-price-year'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-consumption-price-year'] = "";
		}
		// Energiekosten bei 15.000 km Jahresfahrleistung
		$temp = $item->xpath('//ad:cost-model/ad:consumption-costs');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-consumption-costs'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-consumption-costs'] = "";
		}
		// bei einem angenommenen niedrigen durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:low');

		if (isset($temp[0]['base-price'])) {
			$mob_data['wltp-co2-costs-low-base'] = (string) $temp[0]['base-price'];
		} else {
			$mob_data['wltp-co2-costs-low-base'] = "";
		}
		// bei einem angenommenen mittleren durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:middle');

		if (isset($temp[0]['base-price'])) {
			$mob_data['wltp-co2-costs-middle-base'] = (string) $temp[0]['base-price'];
		} else {
			$mob_data['wltp-co2-costs-middle-base'] = "";
		}
		// bei einem angenommenen hohen durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:high');

		if (isset($temp[0]['base-price'])) {
			$mob_data['wltp-co2-costs-high-base'] = (string) $temp[0]['base-price'];
		} else {
			$mob_data['wltp-co2-costs-high-base'] = "";
		}
		// bei einem angenommenen niedrigen durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:low');

		if (isset($temp[0]['accumulated'])) {
			$mob_data['wltp-co2-costs-low-accumulated'] = (string) $temp[0]['accumulated'];
		} else {
			$mob_data['wltp-co2-costs-low-accumulated'] = "";
		}
		// bei einem angenommenen mittleren durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:middle');

		if (isset($temp[0]['accumulated'])) {
			$mob_data['wltp-co2-costs-middle-accumulated'] = (string) $temp[0]['accumulated'];
		} else {
			$mob_data['wltp-co2-costs-middle-accumulated'] = "";
		}
		// bei einem angenommenen hohen durchschnittlichen CO2-Preis von
		$temp = $item->xpath('//ad:cost-model/ad:co2-costs/ad:high');

		if (isset($temp[0]['accumulated'])) {
			$mob_data['wltp-co2-costs-high-accumulated'] = (string) $temp[0]['accumulated'];
		} else {
			$mob_data['wltp-co2-costs-high-accumulated'] = "";
		}


		// Kraftfahrzeugsteuer
		$temp = $item->xpath('//ad:cost-model/ad:tax');

		if (isset($temp[0]['value'])) {
			$mob_data['wltp-tax'] = (string) $temp[0]['value'];
		} else {
			$mob_data['wltp-tax'] = "";
		}
		// Zeitspanne von
		$temp = $item->xpath('//ad:cost-model/ad:time-frame');

		if (isset($temp[0]['from'])) {
			$mob_data['wltp-cost-model-from'] = (string) $temp[0]['from'];
		} else {
			$mob_data['wltp-cost-model-from'] = "";
		}
		// Zeitspanne bis
		$temp = $item->xpath('//ad:cost-model/ad:time-frame');

		if (isset($temp[0]['till'])) {
			$mob_data['wltp-cost-model-till'] = (string) $temp[0]['till'];
		} else {
			$mob_data['wltp-cost-model-till'] = "";
		}
		// ==================================================================
		// End of new fields section.
		//
		// ------------------------------------------------------------------
		//extract search keys to be used for search
		$mob_data['category_key']=$mob_data['category'];
		$mob_data['class_key']=$mob_data['class'];
		$mob_data['brand_key']=$mob_data['make'];
		$mob_data['model_key']=$mob_data['model'];
		$mob_data['fuel_key']=$mob_data['fuel'];
		$mob_data['power_key']=$mob_data['power'];
		$mob_data['owners_key']=$mob_data['number_of_previous_owners'];
		$mob_data['cubicCapacity_key']=$mob_data['cubic_capacity'];
		$mob_data['gearbox_key']=$mob_data['gearbox'];
		return $mob_data;
	}
}