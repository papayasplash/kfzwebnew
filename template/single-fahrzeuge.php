<?php
/*
 * Template Name: Vehicles Single posts
 * Description: Eine Ausarbeitung der neusued media
 */
$meta_values = get_post_meta( get_the_ID() ); ?>
<!-- BEGIN OF DETAIL PAGE -->

<!-- <div class="container"> -->
  <div itemprop="itemOffered" itemscope itemtype="http://schema.org/Car">
  <!--   Vehicle Header -->
  <div class="row">
    <div class="col-xs-12">
      <h2 class="text-left title" itemprop="name"><?php the_title(); ?></h2>
      <h5 class="text-left" itemprop="category"><?php echo $meta_values['category'][0]; ?><?php if(!empty($meta_values['condition'][0])){?>, <?php echo $meta_values['condition'][0]; ?><?php } ?></h5>
    </div>
  </div>
  <!--   END Vehicle Header -->

  <!--   Vehicle Main Information and Images -->
  <div class="row">
    <div class="col-xs-12 col-sm-7">
    <?php $options = get_option('MobileDE_option');
   if($options['mob_slider_option'] == 'yes') { ?>

    <div class="slider-main" style="overflow: hidden;">
     <?php 
		if(!empty($meta_values['images_ebay'])) {
			$mob_images = $meta_values['images_ebay'];
				foreach ($mob_images as $mob_image) {
          $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
          $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
					echo '<img src="' . $mob_image_ssl . '" />';
				}
		}
		else 
		{

			more_fields(true); // Reset index.
			// Bilder form /wp-content/uploads
			while(($more_pics = more_fields ())) {
	    		echo '
					<img src="'. $more_pics['file'].'"/>
				';
			}
		}		
		?>
	</div>
	<div class="slider-nav" style="overflow: hidden;">
		  <?php 
		if(!empty($meta_values['images_ebay'])) {
			$mob_images = $meta_values['images_ebay'];
				foreach ($mob_images as $mob_image) {
          $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
          $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
					echo '<img src="' . $mob_image_ssl . '" />';
				}
		}
		else 
		{

			more_fields(true); // Reset index.
			// Bilder form /wp-content/uploads
			while(($more_pics = more_fields ())) {
	    		echo '
					<img src="'. $more_pics['sizes']['thumbnail']['file'].'"/>
				';
			}
		}		
		?>
	</div>
    <?php
	}
    else { ?>
    
   
      <img class="img-responsive" src="<?php if ( function_exists('has_post_thumbnail') && has_post_thumbnail() ) {the_post_thumbnail_url();}?>" itemprop="image"/>
      <div class="row">
        <!--   Single further images if no slider is present -->
        <?php 
		if(!empty($meta_values['images_ebay'])) {
			$mob_images = $meta_values['images_ebay'];
				foreach ($mob_images as $mob_image) {
          $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
          $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
					echo '<div class="col-xs-4 col-sm-3 col-lg-2 top15"><img class="img img-responsive" src="' . $mob_image_ssl . '" /></div>';
				}
		}
		else 
		{

			more_fields(true); // Reset index.
			// Bilder form /wp-content/uploads
			while(($more_pics = more_fields ())) {
	    		echo '
	    		<div class="col-xs-4 col-sm-3 col-lg-2 top15">
					<a href="'. $more_pics['file'].'"><img class="img img-responsive" src="'. $more_pics['sizes']['thumbnail']['file'].'"/></a>
				</div>';
			}
		}		
		?>
     
        <!--   END Single further images if no slider is present -->
      </div>
      <?php } ?>
    </div>
    <div class="col-xs-12 col-sm-5">
      <!-- Price -->
      <div class="row">
        <div class="col-xs-12" itemprop="makesOffer" itemscope itemtype="http://schema.org/Offer" itemref="product">
        <?php if(!empty($meta_values['price'][0])){?>
          <div itemprop="priceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification">
            <meta itemprop="priceCurrency" content="EUR">
            <meta itemprop="price" content="<?php echo $meta_values['price'][0];?>">
            <h3><strong><?php echo $meta_values['price'][0];?> € (Brutto) </strong><br><small> <?php echo $meta_values['vatable'][0]=='false'?'MwSt. nicht ausweisbar':'Inkl. 19% MwSt.';?></small></h3>
          </div>
          <?php } ?>
          <!-- Verfügbarkeit + if Verfügbar ab Datum -->
          <?php if(!empty($meta_values['available_from'][0])){?>
          <?php if($meta_values['available_from'][0] == "Sofort"){?>
         <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> sofort verfügbar
          <?php } ?>
          <?php if($meta_values['available_from'][0] != "Sofort"){?>
          <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> verfügbar ab <?php echo $meta_values['available_from'][0];?>
          <?php } ?>
          <?php } ?>
          <!-- END Verfügbarkeit -->
        </div>
      </div>
      <!-- END Price -->
      <!-- Main Stats -->
      <div class="row">
        <div class="col-xs-12">
        	<table class="table responsive-table">
        	<!-- Mileage Ausgabe -->
           	<?php if(!empty($meta_values['mileage'][0])){?>
           	<tr><td>Kilometerstand</td><td><strong> <?php echo $meta_values['mileage'][0].' km';?></strong></td></tr>
           	<?php } ?>
           	<!-- END Mileage Ausgabe -->
           	<!-- Addition Ausgabe -->
           	<?php if(!empty($meta_values['addition'][0])){?>
           	<tr><td>Zustand</td><td><strong><?php echo $meta_values['addition'][0]; ?></strong></td></tr>
           	<?php } ?>
           	<!-- END Addition Ausgabe -->
           	<!-- First Reg Ausgabe -->
           	<?php if(!empty($meta_values['firstRegistration'][0])){?>
          	<tr><td>Erstzulassung</td><td><strong> <?php $first_reg = date('m.Y', strtotime($meta_values['firstRegistration'][0]));?><?php echo $first_reg; ?></strong></td></tr>
          	<?php } ?>
          	<!-- End First Reg -->
          	<!-- Owners Ausgabe -->
          	<?php if(!empty($meta_values['owners'][0])){?>
          	<tr><td>Vorbesitzer</td><td><strong><?php echo $meta_values['owners'][0]; ?></strong></td></tr>
          	<?php } ?>
          	<!-- END Owners Ausgabe -->
          	<!-- Tuev -->
          	<?php if(!empty($meta_values['nextInspection'][0])){?>
          	<tr><td>Hauptuntersuchung</td><td><strong> <?php $hu_au = date('m.Y', strtotime($meta_values['nextInspection'][0]));?><?php echo $hu_au; ?></strong></td></tr> 
          	<?php } ?>
          	</table>
        </div>
      </div>
      	<!-- Power, Gear a Fuel -->
          	<?php if(!empty($meta_values['power'][0])){?>
          	<h4><span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span><span> Leistung</span><span><strong> <?php echo $meta_values['power'][0]; ?> kW (<?php $kw = $meta_values['power'][0] ; $faktor = 1.35962; ?><?php $multiplikation = $kw * $faktor; ?> <?php echo round($multiplikation); ?> PS)</strong></span><small> <?php if(!empty($meta_values['gearbox'][0])){?><?php echo $meta_values['gearbox'][0]; ?><?php } ?><?php if(!empty($meta_values['fuel'][0])){?>, <?php echo $meta_values['fuel'][0]; ?><?php } ?></small></h4>
          	<?php } ?>
          	<!-- END Power, Gear a Fuel -->
      <!-- END Main Stats -->
      <!-- Top Features -->
      <div class="row kfz-web-spf">
        <div class="col-xs-12">
          <?php if(!empty($meta_values['XENON_HEADLIGHTS'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Xenon-Scheinwerfer</div><?php } ?>
          <?php if(!empty($meta_values['NAVIGATION_SYSTEM'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Navigationssystem</div><?php } ?>
          <?php if(!empty($meta_values['ESP'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> ESP</div><?php } ?>
          <?php if(!empty($meta_values['HEAD_UP_DISPLAY'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Head-Up Display</div><?php } ?>
          <?php if(!empty($meta_values['FULL_SERVICE_HISTORY'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Scheckheftgepflegt</div><?php } ?>
          <?php if(!empty($meta_values['BENDING_LIGHTS'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Kurvenlicht</div><?php } ?>
          <?php if(!empty($meta_values['PARKING_SENSORS'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Parksensoren</div><?php } ?>
          <?php if(!empty($meta_values['PANORAMIC_GLASS_ROOF'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Panoramadach</div><?php } ?>
          <?php if(!empty($meta_values['CRUISE_CONTROL'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Tempomat</div><?php } ?>
          <?php if(!empty($meta_values['ELECTRIC_HEATED_SEATS'][0])){?><div class="btn btn-default disabled" style="margin-top: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Sitzheizung</div><?php } ?>
        </div>
      </div>
      <!-- END Top Features -->
      <!-- Direct Contact -->
      <hr>
      <div class="row top15">
        <div class="col-xs-12">
          <h3>Fahrzeug Direktanfrage</h3>
        </div>
        <div class="col-xs-6">
          <!-- CHANGE to Variable of Seller E-Mail - and Car Name-->
          <a href="mailto:<?php echo $meta_values['seller_email'][0]; ?>?subject=Direktanfrage zu <?php the_title();?>">
            <button class="btn btn-primary btn-block">
              E-Mail Anfrage
            </button>
          </a>
        </div>
        <div class="col-xs-6">
          <!-- CHANGE to Variable of Seller Phone Number-->
          
            <button class="btn btn-primary btn-block disabled">
              <?php if($meta_values['seller_phone_country_calling_code'][0]) { echo '+' . $meta_values['seller_phone_country_calling_code'][0]; } if($meta_values['seller_phone_area_code'][0]) { echo $meta_values['seller_phone_area_code'][0]; } if($meta_values['seller_phone_number'][0]){ echo $meta_values['seller_phone_number'][0]; } ?> 
            </button>
          
        </div>
      </div>
      <!-- END Direct Contact -->
    </div>
  </div>
  <br>
  <!-- END First Container with Images, Special Features and Price -->
  <hr>
  <div class="row">
    <div class="col-xs-12">
      <h3>Detailinformationen</h3>
    </div>

    <!-- Box with Details -->
    <div class="col-xs-12 col-sm-4">
    	<div class="panel panel-default">
  			<div class="panel-heading">
    			<h3 class="panel-title"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Motor & Getriebe</h3>
  			</div>
  			<div class="panel-body">
    			<!-- Kraftsftoffart -->
              	<?php if(!empty($meta_values['fuel'][0])){?>
              	<span>Kraftstoff:</span> <strong> <?php echo $meta_values['fuel'][0]; ?></strong>
              	<br/>
              	<?php } ?>
              	<!-- Hubraum -->
              	<?php if(!empty($meta_values['cubic_capacity'][0])){?>
              	<span>Hubraum:</span> <strong> <?php echo $meta_values['cubic_capacity'][0]; ?> cm³</strong>
              	<br/>
              	<?php } ?>
              	<!-- Getriebe -->
              	<?php if(!empty($meta_values['gearbox'][0])){?>
              	<span>Getriebe:</span> <strong> <?php echo $meta_values['gearbox'][0]; ?></strong>
              	<br/>
              	<?php } ?>
              	<!-- Leistung -->
              	<?php if(!empty($meta_values['power'][0])){?>
              	<span>Leistung:</span> <strong> <?php echo $meta_values['power'][0]; ?> kW (<?php $kw = $meta_values['power'][0] ; $faktor = 1.35962; ?><?php $multiplikation = $kw * $faktor; ?> <?php echo round($multiplikation); ?> PS)</strong>
              	<?php } ?>
  			</div>
		</div>
    </div>
    <!-- END Box with Details -->

    <!-- Box with Details -->
    <div class="col-xs-12 col-sm-4">
     	<div class="panel panel-default">
  			<div class="panel-heading">
    			<h3 class="panel-title"><span class="glyphicon glyphicon-wrench placative-info" aria-hidden="true"></span> Daten</h3>
  			</div>
  			<div class="panel-body">
            <?php if(!empty($meta_values['condition'][0])){?>
            <span>Zustand:</span> <strong> <?php echo $meta_values['condition'][0]; ?></strong>
            <br>
            <?php } ?>
            <?php if(!empty($meta_values['firstRegistration'][0])){?>
            <span>Erstzulassung:</span><strong> <?php $first_reg = date('m.Y', strtotime($meta_values['firstRegistration'][0]));?><?php echo $first_reg; ?></strong>
            <br>
            <?php } ?>
            <?php if(!empty($meta_values['construction-year'][0])){?>
            <span>Baujahr:</span> <strong> <?php echo $meta_values['construction-year'][0]; ?></strong>
            <br>
            <?php } ?>
            <?php if(!empty($meta_values['nextInspection'][0])){?>
            <span>nächste HU/AU:</span> <strong> <?php $nextInspection = date('m.Y', strtotime($meta_values['nextInspection'][0]));?><?php echo $nextInspection; ?></strong>
             <?php } ?>
            </div>
        </div>
    </div>

    <!-- END Box with Details -->
    <!-- Box with Details -->
    <div class="col-xs-12 col-sm-4">
     	<div class="panel panel-default">
  			<div class="panel-heading">
  			<h3 class="panel-title"><span class="glyphicon glyphicon-leaf placative-info" aria-hidden="true"></span> Energie & Umwelt</h3>
  			</div>
  			<div class="panel-body">
  			<?php if(!empty($meta_values['emissionFuelConsumption_Combined'][0])){?>
              <span>Verbrauch komb.*:</span> <strong> ≈<?php echo $meta_values['emissionFuelConsumption_Combined'][0]; ?> l/100km</strong>
              <br>
            <?php } ?>
  			<?php if(!empty($meta_values['emissionFuelConsumption_Inner'][0])){?>
              <span>Verbrauch innerorts*:</span> <strong> ≈<?php echo $meta_values['emissionFuelConsumption_Inner'][0]; ?> l/100km</strong>
              <br>
            <?php } ?>
  			<?php if(!empty($meta_values['emissionFuelConsumption_Outer'][0])){?>
              <span>Verbrauch außerorts*:</span> <strong> ≈<?php echo $meta_values['emissionFuelConsumption_Outer'][0]; ?> l/100km</strong>
              <br>
            <?php } ?>
  			<?php if(!empty($meta_values['emissionFuelConsumption_CO2'][0])){?>
            <span>CO2-Emissionen komb.*:</span> <strong> ≈<?php echo $meta_values['emissionFuelConsumption_CO2'][0]; ?> g/km</strong>
            <br>
            <?php } ?>
        <?php if(!empty($meta_values['combinedPowerConsumption'][0])){?>
            <span>Stromverbrauch komb.*:</span> <strong> ≈<?php echo $meta_values['combinedPowerConsumption'][0]; ?> kwH/100km</strong>
            <br>
          <?php } ?>
            <?php if(!empty($meta_values['emissionSticker'][0])){?>
            <span>Emissionsklasse:</span> <strong> <?php echo $meta_values['emissionSticker'][0]; ?></strong>
            <?php } ?>
            </div>
        </div>
    </div>
    <!-- END Box with Details -->    
    <div class="col-xs-12">
    <hr>
    </div>
    <!-- Box Second Row with Details -->
    <div class="col-xs-12 col-sm-6">
   		<div class="panel panel-default">
  			<div class="panel-heading">
  			<h3 class="panel-title"><span class="glyphicon glyphicon-camera placative-info" aria-hidden="true"></span> Optik</h3>
          </div>
  			<div class="panel-body">
  			<?php if(!empty($meta_values['manufacturer_color_name'][0])){?>
              <span>Farbbezeichnung:</span> <strong> <?php echo $meta_values['manufacturer_color_name'][0]; ?></strong>
              <br/>
            <?php } ?>
  			<?php if(!empty($meta_values['exteriorColor'][0])){?>
              <span>Außenfarbe:</span> <strong> <?php echo $meta_values['exteriorColor'][0]; ?> <?php if(!empty($meta_values['METALLIC'][0])) { echo '(' . $meta_values['METALLIC'][0] . ')'; } ?></strong>
              <br/>
            <?php } ?>
  			<?php if(!empty($meta_values['interior_type'][0])){?>
              <span>Innenausstattung:</span> <strong> <?php echo $meta_values['interior_type'][0]; ?><?php if(!empty($meta_values['interior_color'][0])){?>, <?php echo $meta_values['interior_color'][0]; }?></strong>
              <br/>
            <?php } ?>
            </div>
        </div>
    </div>
    
    <!-- END Box with Details -->
    <!-- Box Second Row with Details -->
    <div class="col-xs-12 col-sm-6">
     <div class="panel panel-default">
  			<div class="panel-heading">
  			<h3 class="panel-title"><span class="glyphicon glyphicon-list placative-info" aria-hidden="true"></span> Weitere Daten</h3>
            </div>
           	<div class="panel-body">
           	<?php if(!empty($meta_values['door_count'][0])){?>
            <span>Anzahl d. Türen:</span> <strong><?php echo $meta_values['door_count'][0]; ?></strong>
            <br/>
            <?php } ?>
            <?php if(!empty($meta_values['num_seats'][0])){?>
              <span>Anzahl Sitzplätze:</span> <strong> <?php echo $meta_values['num_seats'][0]; ?></strong>
              <br/>
            <?php } ?>
            <?php if(!empty($meta_values['vehicleListingID'][0])){?>
              <span>Fahrzeugnummer:</span> <strong> <?php echo $meta_values['vehicleListingID'][0]; ?></strong>
              <br/>
            <?php } ?>
            <?php if(!empty($meta_values['schwacke-code'][0])){?>
              <span>Schwacke Code :</span> <strong> <?php echo $meta_values['schwacke-code'][0]; ?></strong>
            <?php } ?>
            </div>
        </div>
    </div>
    <!-- END Box with Details -->    
    <!-- All Features in Table -->
   
    <div class="col-xs-12">
    <hr>
      <!-- <h3>Sonderausstattungen</h3> -->
      <div class="row">
      	<div class="col-xs-12">
      	<h4>Sicherheit:</h4>
      	</div>
      		<!-- ABS -->
      		<?php if(!empty($meta_values['ABS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ABS'][0]; ?></div>
            <?php } ?>
      		<!-- Kurvenlicht -->
      		<?php if(!empty($meta_values['BENDING_LIGHTS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['BENDING_LIGHTS'][0]; ?></div>
            <?php } ?>
      		<!-- Tagfahrlicht -->
      		<?php if(!empty($meta_values['DAYTIME_RUNNING_LIGHTS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['DAYTIME_RUNNING_LIGHTS'][0]; ?></div>
            <?php } ?>
      		<!-- ESP -->
      		<?php if(!empty($meta_values['ESP'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ESP'][0]; ?></div>
            <?php } ?>
      		<!-- Nebelscheinwerfer -->
      		<?php if(!empty($meta_values['FRONT_FOG_LIGHTS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['FRONT_FOG_LIGHTS'][0]; ?></div>
            <?php } ?>
      		<!-- Elektr. Wegfahrsperre -->
      		<?php if(!empty($meta_values['IMMOBILIZER'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['IMMOBILIZER'][0]; ?></div>
            <?php } ?>
      		<!-- Isofix -->
      		<?php if(!empty($meta_values['ISOFIX'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ISOFIX'][0]; ?></div>
            <?php } ?>    
      		<!-- Einparkhilfe -->
      		<?php if(!empty($meta_values['PARKING_SENSORS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['PARKING_SENSORS'][0]; ?></div>
            <?php } ?>
      		<!-- Servolenkung -->
      		<?php if(!empty($meta_values['POWER_ASSISTED_STEERING'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['POWER_ASSISTED_STEERING'][0]; ?></div>
            <?php } ?> 
      		<!-- Traktionskontrolle -->
      		<?php if(!empty($meta_values['TRACTION_CONTROL_SYSTEM'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['TRACTION_CONTROL_SYSTEM'][0]; ?></div>
            <?php } ?>                                          
      		<!-- Xenonscheinwerfer -->
      		<?php if(!empty($meta_values['XENON_HEADLIGHTS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['XENON_HEADLIGHTS'][0]; ?></div>
            <?php } ?> 
      </div>
      <br>
      <hr>
      <div class="row">
      	<div class="col-xs-12">
      	<h4>Komfort:</h4>
      	</div>
      		<!-- Regensensor -->
      		<?php if(!empty($meta_values['AUTOMATIC_RAIN_SENSOR'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['AUTOMATIC_RAIN_SENSOR'][0]; ?></div>
            <?php } ?>
      		<!-- Standheizung -->
      		<?php if(!empty($meta_values['AUXILIARY_HEATING'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['AUXILIARY_HEATING'][0]; ?></div>
            <?php } ?>
      		<!-- Zentralverriegelung -->
      		<?php if(!empty($meta_values['CENTRAL_LOCKING'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['CENTRAL_LOCKING'][0]; ?></div>
            <?php } ?>
      		<!-- Tempomat -->
      		<?php if(!empty($meta_values['CRUISE_CONTROL'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['CRUISE_CONTROL'][0]; ?></div>
            <?php } ?>
      		<!-- Elektr. Sitzeinstellung -->
      		<?php if(!empty($meta_values['ELECTRIC_ADJUSTABLE_SEATS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ELECTRIC_ADJUSTABLE_SEATS'][0]; ?></div>
            <?php } ?>
      		<!-- Elektr. Seitenspiegel -->
      		<?php if(!empty($meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0]; ?></div>
            <?php } ?>
      		<!-- Sitzheizung -->
      		<?php if(!empty($meta_values['ELECTRIC_HEATED_SEATS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ELECTRIC_HEATED_SEATS'][0]; ?></div>
            <?php } ?>    
      		<!-- Elektr. Fensterheber -->
      		<?php if(!empty($meta_values['ELECTRIC_WINDOWS'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['ELECTRIC_WINDOWS'][0]; ?></div>
            <?php } ?>
      		<!-- Lichtsensor -->
      		<?php if(!empty($meta_values['LIGHT_SENSOR'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['LIGHT_SENSOR'][0]; ?></div>
            <?php } ?> 
      		<!-- Multifunktionslenkrad -->
      		<?php if(!empty($meta_values['MULTIFUNCTIONAL_WHEEL'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['MULTIFUNCTIONAL_WHEEL'][0]; ?></div>
            <?php } ?>                                          
      		<!-- Panorama-Dach -->
      		<?php if(!empty($meta_values['PANORAMIC_GLASS_ROOF'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['PANORAMIC_GLASS_ROOF'][0]; ?></div>
            <?php } ?>
      		<!-- Start/Stopp-Automatik -->
      		<?php if(!empty($meta_values['START_STOP_SYSTEM'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['START_STOP_SYSTEM'][0]; ?></div>
            <?php } ?>                                          
      		<!-- Schiebedach -->
      		<?php if(!empty($meta_values['SUNROOF'][0])){?>
            	<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> <?php echo $meta_values['SUNROOF'][0]; ?></div>
            <?php } ?>             
      </div>
      <br>

      <hr>




           

     <!-- END Features in Table -->
     <!-- Description -->
    <div class="row">
    <div class="col-xs-12" itemprop="description">
      <h3>Beschreibung</h3>
      
      <?php if(!empty($meta_values['enriched_description'][0])) { echo $meta_values['enriched_description'][0]; } ?>
      <p>* Weitere Informationen zum offiziellen Kraftstoffverbrauch und zu den offiziellen spezifischen CO2-Emissionen und gegebenenfalls zum Stromverbrauch neuer PKW können dem Leitfaden über den offiziellen Kraftstoffverbrauch, die offiziellen spezifischen CO2-Emissionen und den offiziellen Stromverbrauch neuer PKW' entnommen werden, der an allen Verkaufsstellen und bei der 'Deutschen Automobil Treuhand GmbH' unentgeltlich erhältlich ist unter <a href="http://www.dat.de/" target="_blank">www.dat.de</a>.</p>

    </div>
    <!-- END Description -->
    </div>
  <!-- </div> -->