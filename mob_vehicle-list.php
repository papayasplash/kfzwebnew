<?php

// Attributes
extract(
	shortcode_atts(
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
		),
		$atts
	)
);
// Code
$args = array(
	'post_type' => 'fahrzeuge',
	'meta_key' => $meta_key,
	'orderby' => $orderby,
	'order' => $order,
	'posts_per_page' => $posts_per_page,
	'marke' => $marke,
	'modell' => $modell,
	'zustand' => $zustand,
	'kraftstoffart' => $kraftstoffart,
	'getriebe' => $getriebe,
	'standort' => $standort,
);

$vehicles = new WP_Query($args);
if ($vehicles->have_posts()) { ?>
	<div class="facetwp-template row">
		<?php while ($vehicles->have_posts()) {
			$vehicles->the_post();
			$meta_values = get_post_meta(get_the_ID());
			?>
			<article id="post-<?php the_ID(); ?>" class="vehicle">
				<hgroup>
					<a href="<?php the_permalink(); ?>">
						<h2 class="vehicle-title">
							<?php the_title(); ?>
						</h2>

					</a>
				</hgroup>
				<span class="vehicle-addition">
					<p>
						<?php echo $meta_values['category'][0]; ?> ,
						<?php echo $meta_values['condition'][0]; ?>
					</p>
				</span>
				<div class="vehicle-row">
					<div class="vehicle-thumbnail">
						<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
							<?php if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
								the_post_thumbnail('thumbnail');
							} ?>
						</a>
					</div>
					<div class="vehicle-data">
						<?php if (!empty($meta_values['firstRegistration'][0])) { ?>
							<small>
								<label>EZ</label>
								<?php $show_date = date('m.Y', strtotime($meta_values['firstRegistration'][0])); ?>
								<?php echo $show_date; ?>
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['mileage'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['mileage'][0]; ?> km
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['power'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['power'][0]; ?> kW
								<?php
								$zahl1 = $meta_values['power'][0];
								$zahl2 = 1.35962;
								$multiplikation = $zahl1 * $zahl2; ?>
								(
								<?php echo round($multiplikation); ?> PS)
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['gearbox'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['gearbox'][0]; ?>
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['damage_and_unrepaired'][0])) { ?>
							<small>
								·
								<?php if ($meta_values['damage_and_unrepaired'][0] == 'false') {
									echo 'Unfallfrei';
								} ?>
							</small>
						<?php } ?>

						<?php if (!empty($meta_values['fuel'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['fuel'][0]; ?>
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['exterior_color'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['exterior_color'][0]; ?>
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['next_inspection'][0])) { ?>
							<small>
								·
								HU
								<?php echo date('m.Y', strtotime($meta_values['next_inspection'][0])) ?>
							</small>
						<?php } ?>
						<?php if (!empty($meta_values['door_count'][0])) { ?>
							<small>
								·
								<?php echo $meta_values['door_count'][0]; ?> Türen
							</small>
						<?php } ?>

					</div>

					<div class="vehicle-price">
						<?php if (!empty($meta_values['price'][0])) { ?>
							<div itemprop="priceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification">
								<meta itemprop="priceCurrency" content="EUR">
								<meta itemprop="price" content="<?php echo $meta_values['price'][0]; ?>">
								<strong>
									<?php echo $meta_values['price'][0]; ?> € (Brutto)
								</strong><br><small>
									<?php echo $meta_values['vatable'][0] == 'false' ? 'MwSt. nicht ausweisbar' : 'Inkl. 19% MwSt.'; ?>
								</small>
							</div>
						<?php } ?>
						<br>
						<a href="<?php the_permalink(); ?>"
							class="vehicle-btn wp-block-button__link wp-element-button">Details</a>
					</div>
				</div> <!-- // vehicle-row -->
				<div class="vehicle-emission">
					<?php if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) { ?>
						<small><label>Kraftstoffverbr. komb. ca.:</label><span>
								<?php echo $meta_values['emissionFuelConsumption_Combined'][0] . ' l/100km*'; ?>
							</span></small>
					<?php } ?>
					<?php if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) { ?> <small>CO2 Emissionen
								komb. ca.:<span>
								<?php echo $meta_values['emissionFuelConsumption_CO2'][0] . ' g/km*'; ?>
							</span></small>
					<?php } ?>
					<?php if (!empty($meta_values['emissionClass'][0])) { ?> <small>Emissionsklasse:<span>
								<?php echo $meta_values['emissionClass'][0]; ?>
							</span></small>
					<?php } ?>
					<?php if (!empty($meta_values['efficiency_class'][0])) { ?>
						<small>Energieeffizienzklasse:<span>
								<?php echo $meta_values['efficiency_class'][0]; ?>
							</span></small>
					<?php } ?>
					<?php if (!empty($meta_values['combinedPowerConsumption'][0])) { ?> <small>Stromverbrauch
								komb.*: ≈<span>
								<?php echo $meta_values['combinedPowerConsumption'][0]; ?>
							</span> kwH/100km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-co2-emission-combined'][0])) { ?><small>CO₂-Emissionen
								(WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-co2-emission-combined'][0]; ?>
							</span> g/km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-consumption-fuel-combined'][0])) { ?><small>Verbrauch komb.
								(WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-consumption-fuel-combined'][0]; ?>
							</span> l/100km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-consumption-power-combined'][0])) { ?><small>Stromverbrauch
								(WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-consumption-power-combined'][0]; ?>
							</span> kWh/100km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-electric-range'][0])) { ?><small>Elektrische Reichweite
								(WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-electric-range'][0]; ?>
							</span> km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-consumption-fuel-combined-weighted'][0])) { ?><small>Gewichteter
								kombinierter Kraftstoffverbrauch für Plug-in-Hybride (WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-consumption-fuel-combined-weighted'][0]; ?>
							</span> l/100km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-consumption-power-combined-weighted'][0])) { ?><small>Gewichteter
								kombinierter Stromverbrauch für Plug-in-Hybride (WLTP)*: ≈<span>
								<?php echo $meta_values['wltp-consumption-power-combined-weighted'][0]; ?>
							</span> KwH/100km</small>
					<?php } ?>
					<?php if (!empty($meta_values['wltp-co2-emission-combined-weighted'][0])) { ?><small>Gewichtete
								Menge an Kohlendioxidemissionen für Plug-in-Hybride*: ≈<span>
								<?php echo $meta_values['wltp-co2-emission-combined-weighted'][0]; ?>
							</span> g/km</small>
					<?php } ?>
				</div>
			</article>
			<?php
		}
		?>
	</div> <!-- FacetWP Template End -->
	<p>* Weitere Informationen zum offiziellen Kraftstoffverbrauch und zu den offiziellen spezifischen CO2-Emissionen und
		gegebenenfalls zum Stromverbrauch neuer PKW können dem Leitfaden über den offiziellen Kraftstoffverbrauch, die
		offiziellen spezifischen CO2-Emissionen und den offiziellen Stromverbrauch neuer PKW' entnommen werden, der an allen
		Verkaufsstellen und bei der 'Deutschen Automobil Treuhand GmbH' unentgeltlich erhältlich ist unter www.dat.de.</p>
	<?php
}