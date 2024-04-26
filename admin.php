<?php


/*Settings class */

class mob_Setting{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    public function __construct($mob_data)
    {
		$this->mob_data=$mob_data;
        add_action( 'admin_menu', array( $this, 'add_options_page' ) );
        add_action( 'admin_init', array( $this, 'register_options' ) );     
    }
    /**
    * Add a new item in options menu to access the options page
    */
    public function add_options_page()
    {
        // This page will be under "Settings"
        add_menu_page(
            'Settings Admin', 
            'kfz-web Import', 
            'manage_options', 
            'searchde-setting-admin', /* Page ID */
            array( $this, 'create_options_page' ),
            'dashicons-admin-generic'
        );
    }
   /**
     * Options page callback that create all the fields used in settings
     */
    public function create_options_page()
    {
        // Set class property
        $this->options = get_option( 'MobileDE_option' );//option name
		if(empty( $this->options['mob_download_interval'])){
			 $this->options['mob_download_interval']='daily';
		}

?>

<div class="wrap">

<img src="//www.mobilede-fahrzeugintegration.de/wp-content/uploads/2016/10/kfz-web-logo.png"> </img>

            <?php //screen_icon(); ?>
			<?php
		$license 	= get_option( 'mob_license_key' );
		$status 	= get_option( 'mob_license_status' );
		if( $status == false && $status !== 'valid' ) { 
			echo'
			<div class="notice notice-error"><p>
			' . __('The plugin is not activated. If you have problems with activation, check whether the plugin has already been activated on another site. <b>Deactivate</b> the plugin on the previous domain and then <b>activate</b> it on this page.</p><p>If you do not have access to your previous domain, write to us a <a target="_blank" href="http://support.neusued.de/">Ticket</a> at http://support.neusued.de/', 'kfz-web') . '
			</p></div>
			';
		}
		?>
            <h2><?php __('MobileDE Settings'); ?></h2>
	<form class="inputForm" method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'MobileDE_option' );   
                do_settings_sections( 'searchde-setting-admin' );
                submit_button(); 
            ?><input class="button-primary" type="button" <?php if( $status == false && $status !== 'valid' ) { ?>disabled<?php } ?>
			id="importData" value="<?php esc_attr_e('Import vehicles', 'kfz-web') ?>" /> <input class="button-primary" type="button" id="deletePosts" value="Delete vehicles" />
	</form>
	
		
		<?php
		// PHP Memory Limit Check
		$options = ini_get_all();
		$memory_limit = str_replace('M', '', $options['memory_limit']['local_value']);
		add_action('admin_notices', 'kfz_admin_notices');

		function kfz_admin_notices() {
				// PHP Version check
			if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
				echo '
				<div class="notice notice-error"><p>
				kfz-web needs at leat PHP-Version: ' . PHP_VERSION . "\n" . '</p></div>';
			}
			else {
				echo '
				<div class="notice notice-success is-dismissible"><p>
				Detected PHP-Version:<strong> ' . PHP_VERSION . "\n" . '</strong>
			';
			}
			$memory_limit = ini_get('memory_limit');
			$memory_limit = rtrim($memory_limit, 'M');
			$memory_limit = intval($memory_limit);

			// Your existing code goes here
			if ($memory_limit < 128) {
				echo '<div class="notice notice-success is-dismissible"><p>
				The memory limit is currently at:<strong> '. $options['memory_limit']['local_value'] . '</strong> (memory_limits).
				</p></div>';
				// Your existing notice code here
			} elseif ($memory_limit >= 128 && $memory_limit < 256) {
				echo '<div class="notice notice-warning"><p>
				The memory limit is currently at:<strong> '. $options['memory_limit']['local_value'] . '</strong> (memory_limits).
				</p></div>';
				// Your existing notice code here
			} elseif ($memory_limit >= 256) {
				echo '<div class="notice notice-success is-dismissible"><p>
				The memory limit is currently at:<strong> '. $options['memory_limit']['local_value'] . '</strong> (memory_limits).
				</p></div>';
				// Your existing notice code here
			}
			$max_execution_time = $options['max_execution_time']['local_value'];
			if($max_execution_time >= '60') {
				echo '<div class="notice notice-success is-dismissible"><p>
				The maximum script execution time is:<strong> '.$max_execution_time.'</strong> seconds.
				</p></div>';
			}
			else {
				echo '<div class="notice notice-warning"><p>
				The maximum script execution time is:<strong> '.$max_execution_time.'</strong> seconds | We recommend at least: <strong>60</strong> seconds.<br>
				</p></div>';	
			}
			$allow_url_fopen = $options['allow_url_fopen']['local_value'];
			if ($allow_url_fopen == 0) {
				echo '<div class="notice notice-error"><p>
				allow_url_fopen must be <strong>"ON"</strong>.
				</p></div>';
			}
			else {
				echo '<div class="notice notice-success is-dismissible"><p>
				allow_url_fopen ist <strong>"ON"</strong>.
				</p></div>';
			}
		}
	
	}
   /**
     * Register all fields used in settings
     */
    public function register_options()
    { 
        register_setting(
            'MobileDE_option', // Option group
            'MobileDE_option', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
		add_settings_section(
            'connection_section_id', // Section ID
            'Verbindungseinstellungen', // Title
            array( $this, 'print_section_info' ), // Callback
            'searchde-setting-admin' // Page ID
        );

     
		add_settings_field(
            'mob_username', 
            'dlr_ user', 
            array( $this, 'mob_username_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );

		add_settings_field(
            'mob_password', 
            'Password', 
            array( $this, 'mob_password_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'         
    	);

    	add_settings_field(
            'mob_download_interval', 
            'Import interval', 
            array( $this, 'mob_download_interval_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );
   
        add_settings_field(
            'mob_language', 
            'Language', 
       	    array( $this, 'mob_language_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );

        add_settings_field(
            'mob_image_option', 
            'Image storage location', 
            array( $this, 'mob_image_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );

  		// add_settings_field(
        //     'mob_bootstrap_option', 
        //     'Bootstrap CDN', 
        //     array( $this, 'mob_bootstrap_callback' ), 
        //     'searchde-setting-admin', 
        //     'connection_section_id'
        // );

  		add_settings_field(
            'mob_slider_option', 
            'Slickslider', 
            array( $this, 'mob_slider_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );

  		add_settings_field(
            'use_cat_tax', 
            'Standard categories', 
            array( $this, 'mob_cat_tax_callback' ), 
            'searchde-setting-admin', 
            'connection_section_id'
        );


		add_action( 'wp_ajax_ajaxImportData', 'mob_ajaxImportData' );
		add_action( 'wp_ajax_ajaxDeletePosts', 'mob_ajaxDeletePosts' );

		function mob_ajaxImportData(){
			$error=mob_download_import_feed();
				if(empty($error)) {
					die('1');
				} else {
					die($error);
				}
		}
		function mob_ajaxDeletePosts(){
			mob_deleteAllPosts();
			die('1');
			}
		}


		public function sanitize( $data )
		{
			$new_data = array();
			if(!empty($data['mob_url'])){
				$new_data['mob_url']=sanitize_text_field($data['mob_url']);
			}
			if(!empty($data['mob_username'])){
				for($i=0;$i<count($data['mob_username']);$i++) {
					if(!empty($data['mob_username'][$i])){
						$new_data['mob_username'][$i]=sanitize_text_field(trim($data['mob_username'][$i]));
						$new_data['mob_password'][$i]=sanitize_text_field(trim($data['mob_password'][$i]));
					}
				}
			}

			if(!empty($data['mob_language'])){
				$new_data['mob_language']=sanitize_text_field($data['mob_language']);
			}

			if(!empty($data['mob_download_interval'])){
				$new_data['mob_download_interval']=sanitize_text_field($data['mob_download_interval']);
				$this->options = get_option( 'MobileDE_option' );
				//update the interval of scheduler
				if($new_data['mob_download_interval'] != $this->options['mob_download_interval']) {
					mob_update_schedule($new_data['mob_download_interval']);
				}
			}

			if(!empty($data['mob_image_option'])) {
				$new_data['mob_image_option']=sanitize_text_field($data['mob_image_option']);
			}

			// if(!empty($data['mob_bootstrap_option'])) {
			// 	$new_data['mob_bootstrap_option']=sanitize_text_field($data['mob_bootstrap_option']);
			// }


			if(!empty($data['mob_slider_option'])) {
				$new_data['mob_slider_option']=sanitize_text_field($data['mob_slider_option']);
			}	

			if(!empty($data['mob_cat_tax_option'])) {
				$new_data['mob_cat_tax_option']=sanitize_text_field($data['mob_cat_tax_option']);
			}
			return $new_data;
		}

		//Print the Section text
		public function print_section_info()
		{
			if (isset($GLOBALS["mob_wordpressAdKeysCount"])){
			echo $GLOBALS["mob_wordpressAdKeysCount"]; // z.B.
		} else {
			echo "";
		}
		}



		public function mob_username_callback()
		{
			printf(
				'<input type="text" id="mob_username0" name="MobileDE_option[mob_username][0]" placeholder="dlr_" value="%s" />',

				isset( $this->options['mob_username'][0] ) ? esc_attr( $this->options['mob_username'][0]) : ''

			);
			?>
			<input class="button-primary" type="button" id="addAccount"
				value="<?php esc_attr_e('+ add account', 'kfz-web') ?>" />
			<?php

		}


		public function mob_password_callback()
		{
			printf(
				'<input type="password" class="mob_password" id="mob_password0" name="MobileDE_option[mob_password][0]" value="%s" />',
				isset( $this->options['mob_password'][0] ) ? esc_attr( $this->options['mob_password'][0]) : ''
			);
			if (isset($this->options['mob_username'])){
			for($i = 1; $i < count($this->options['mob_username']); $i ++) {
				echo '<tr valign="top"><th colspan="2" scope="row"><hr /></th>';
				echo '<tr valign="top"><th scope="row">Benutzername</th><td>';
				printf('<input type="text" id="mob_username' . $i . '" name="MobileDE_option[mob_username][' . $i .
						 ']" value="%s" />', 
								isset($this->options['mob_username'][$i]) ? esc_attr($this->options['mob_username'][$i]) : '');
				echo '</td></tr>';
				echo '<tr valign="top"><th scope="row">Passwort</th><td>';
				printf(
						'<input type="password" id="mob_password' . $i . '" name="MobileDE_option[mob_password][' . $i .
								 ']" value="%s" />', 
								isset($this->options['mob_password'][$i]) ? esc_attr($this->options['mob_password'][$i]) : '');
				echo '</td></tr>';
			}
		}
		echo '<tr valign="top"><th colspan="2" scope="row"><hr /></th>';
		}
		public function mob_language_callback()
		{
			echo '<select class="settingsSelect" name="MobileDE_option[mob_language]">'.
				mob_htmlToOptions($this->mob_data['languages'], false,
				isset( $this->options['mob_language'] ) ? esc_attr( $this->options['mob_language']) : ''.
				'</select>'
			);
		}
		public function mob_download_interval_callback()
		{
			echo '<select class="settingsSelect" name="MobileDE_option[mob_download_interval]">'.
				mob_htmlToOptions($this->mob_data['download_interval'], false,
				isset( $this->options['mob_download_interval'] ) ? esc_attr( $this->options['mob_download_interval']) : ''.
				'</select>'
			);
		}
		public function mob_image_callback()
		{
			echo '
			<sub>' . __('Leave images on mobile.de or import them and generate thumbnails?', 'kfz-web') . '</sub><br><br>
			<select class="settingsSelect" name="MobileDE_option[mob_image_option]">'.
				mob_htmlToOptions($this->mob_data['mob_images'], false,
				isset( $this->options['mob_image_option'] ) ? esc_attr( $this->options['mob_image_option']) : ''.
				'</select>'
			);
			/* 
			<sub>Wenn Sie die Bilder auf mobile.de belassen, werden die Bilder von mobile.de geladen. Wenn Sie die Bilder importieren, werden die Bilder auf Ihrem Server gespeichert und die Thumbnails generiert.</sub><br><br>
			*/
			// log_me($this->options);
			// log_me($this->mob_data);
		}

		// public function mob_bootstrap_callback()
		// {
		// 	echo '
		// 	<sub>Twitter Bootstrap per CDN (Content Delivery Network) einbinden?</sub><br><br>
		// 	<select class="settingsSelect" name="MobileDE_option[mob_bootstrap_option]">'.
		// 		mob_htmlToOptions($this->mob_data['mob_bootstrap'], false,
		// 		isset( $this->options['mob_bootstrap_option'] ) ? esc_attr( $this->options['mob_bootstrap_option']) : ''.
		// 		'</select>'
		// 	);
		// 	log_me($this->options);
		// }

		public function mob_slider_callback()
		{
			echo '
			<sub><a href="http://kenwheeler.github.io/slick/" target=_blank">Slickslider</a> ' . __('for the vehicle pictures to load?', 'kfz-web') . '</sub><br><br>
			<select class="settingsSelect" name="MobileDE_option[mob_slider_option]">'.
				mob_htmlToOptions($this->mob_data['mob_slider'], false,
				isset( $this->options['mob_slider_option'] ) ? esc_attr( $this->options['mob_slider_option']) : ''.
				'</select>'
			);
			// log_me($this->options);
		}

		public function mob_cat_tax_callback()
		{
			echo '
			<sub>' . __('Should the standard categories be filled with vehicle data?', 'kfz-web') . '</sub>
			<br><br>
			<select class="settingsSelect" name="MobileDE_option[mob_cat_tax_option]">'.
				mob_htmlToOptions($this->mob_data['use_cat_tax'], false,
				isset( $this->options['mob_cat_tax_option'] ) ? esc_attr( $this->options['mob_cat_tax_option']) : ''.
				'</select>'
			);
			// log_me($this->options);
		}
}
$mob_page = new mob_setting($mob_data);