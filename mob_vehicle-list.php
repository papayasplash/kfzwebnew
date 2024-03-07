<?php 

// Attributes
	extract( shortcode_atts(
		array(
			'marke' => array(),
			'modell' => array(),
            'order' => '',
           	'zustand' => '',
           	'kraftstoffart' => '',
           	'getriebe' => '',
           	'standort' => '',
            'posts_per_page' => '-1',
            'meta_key' => '',
            'orderby' => '',
		), $atts )
	);
	// Code
	$args = array(
	'post_type' => 'fahrzeuge',
	'meta_key' => $meta_key,
	'orderby' =>  $orderby,
	'order' => $order,
	'posts_per_page' => $posts_per_page,
	'marke' => $marke,
	'modell' => $modell,
	'zustand' => $zustand,
	'kraftstoffart' => $kraftstoffart,
	'getriebe' => $getriebe,
	'standort' => $standort,
	);

	$vehicles = new WP_Query( $args );
	if( $vehicles->have_posts() ) { ?>
	<div class="facetwp-template row">
	<?php while( $vehicles->have_posts() ) {
	$vehicles->the_post();
	$meta_values = get_post_meta( get_the_ID() );
	?>	
		<div id="post-<?php the_ID(); ?>" class="col-xs-12">
		<hr>
		
			<a href="<?php the_permalink(); ?>"><h2 id=""><?php the_title(); ?></h2></a>
			<span id=""><p><?php echo $meta_values['category'][0]; ?> , <?php echo $meta_values['condition'][0]; ?></p></span>
			<!-- <div class="row"> -->

			<div class="col-xs-12 col-md-4"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php if ( function_exists( 'has_post_thumbnail') && has_post_thumbnail() ) { the_post_thumbnail(''); } ?></a>
			</div>
<div class="col-xs-12 col-md-8">
<div class="row">

<div class="col-xs-12 col-md-8">
<table class="table table-responsive table-striped table-condensed">
<?php if(!empty($meta_values['firstRegistration'][0])){?><tr><td><label>EZ: </label></td><td><?php $show_date = date('m.Y', strtotime($meta_values['firstRegistration'][0]));?><?php echo $show_date; ?></td></tr><?php } ?>
<?php if(!empty($meta_values['mileage'][0])){?><tr><td><label>Kilometerstand: </label></td><td><?php echo $meta_values['mileage'][0];?></td></tr><?php } ?>
<?php if(!empty($meta_values['nextInspection'][0])){?> <tr><td><label>HU / AU bis: </label></td><td><?php $show_date = date('m.Y', strtotime($meta_values['nextInspection'][0]));?><?php echo $show_date; ?></td></tr><?php } ?>
<?php if(!empty($meta_values['gearbox'][0])){?><tr><td><label>Getriebe: </label></td><td><?php echo $meta_values['gearbox'][0]; ?></td></tr><?php } ?>
<?php if(!empty($meta_values['power'][0])){?> <tr><td><label>Leistung: </label></td><td><?php echo $meta_values['power'][0]; ?> kW<?php $zahl1 = $meta_values['power'][0] ; $zahl2 = 1.35962; ?>
<?php $multiplikation = $zahl1 * $zahl2; ?> / <?php echo round($multiplikation); ?> PS ,<?php } ?> <!-- Diesel, Benzin, etc. --><?php if(!empty($meta_values['fuel'][0])){?><?php echo $meta_values['fuel'][0]; ?></td></tr><?php } ?>
</table>
<?php if(!empty($meta_values['emissionFuelConsumption_Combined'][0])){?> <small><label>Kraftstoffverbr. komb. ca.:</label><span> <?php echo $meta_values['emissionFuelConsumption_Combined'][0]. ' l/100km *';?></span></small><?php } ?>
<?php if(!empty($meta_values['emissionFuelConsumption_CO2'][0])){?> <small><label>CO2 Emissionen komb. ca.:</label><span> <?php echo $meta_values['emissionFuelConsumption_CO2'][0]. ' g/km *';?></span></small><?php } ?>
<?php if(!empty($meta_values['emissionClass'][0])){?> <small><label>Emissionsklasse:</label><span> <?php echo $meta_values['emissionClass'][0];?></span></small><?php } ?>
<?php if(!empty($meta_values['efficiency_class'][0])){?> <small><label>Energieeffizienzklasse:</label><span> <?php echo $meta_values['efficiency_class'][0];?></span></small><?php } ?>
<?php if(!empty($meta_values['combinedPowerConsumption'][0])){?> <small><label>Stromverbrauch komb.*: ≈</label><span> <?php echo $meta_values['combinedPowerConsumption'][0];?></span> kwH/100km</small><?php } ?>
</div>

<div class="col-xs-12 col-md-4">
<?php if(!empty($meta_values['price'][0])){?>
	<div itemprop="priceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification">
	<meta itemprop="priceCurrency" content="EUR">
    <meta itemprop="price" content="<?php echo $meta_values['price'][0];?>">
    <strong style="font-size: 2rem;"><?php echo $meta_values['price'][0];?> € (Brutto) </strong><br><small> <?php echo $meta_values['vatable'][0]=='false'?'MwSt. nicht ausweisbar':'Inkl. 19% MwSt.';?></small>
	</div>
<?php } ?>
<br>
<a href="<?php the_permalink(); ?>" class="btn btn-primary btn-lg btn-block">Details</a>
</div>


</div>
</div>

				<!-- </div> //row -->

		</div>
		
		<?php //echo custom_taxonomies_terms_links(); ?>
		
		 <?php
		                        }
		 ?>
		 </div> <!-- FacetWP Template End -->

		 <?php
		 
		 }
		      

	 

	