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
			<div id="post-<?php the_ID(); ?>" class="vehicle">
				<a href="<?php the_permalink(); ?>">
					<h2 class="vehicle-title">
						<?php the_title(); ?>
					</h2>

				</a>
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
								the_post_thumbnail('medium');
							} ?>
						</a>
					</div>
					<div class="vehicle-data">
						<table class="table table-responsive table-striped table-condensed">
							<?php if (!empty($meta_values['firstRegistration'][0])) { ?>
								<tr>
									<td><label><small>EZ: </small></label></td>
									<td>
										<?php $show_date = date('m.Y', strtotime($meta_values['firstRegistration'][0])); ?>
										<small><?php echo $show_date; ?></small>
									</td>
								</tr>
							<?php } ?>
							<?php if (!empty($meta_values['mileage'][0])) { ?>
								<tr>
									<td><label><small>Kilometerstand: </small></label></td>
									<td>
									<small><?php echo $meta_values['mileage'][0]; ?></small>
									</td>
								</tr>
							<?php } ?>
							<?php if (!empty($meta_values['nextInspection'][0])) { ?>
								<tr>
									<td><label><small>HU / AU bis: </small></label></td>
									<td>
										<?php $show_date = date('m.Y', strtotime($meta_values['nextInspection'][0])); ?>
										<small><?php echo $show_date; ?></small>
									</td>
								</tr>
							<?php } ?>
							<?php if (!empty($meta_values['gearbox'][0])) { ?>
								<tr>
									<td><label><small>Getriebe: </small></label></td>
									<td>
									<small><?php echo $meta_values['gearbox'][0]; ?></small>
									</td>
								</tr>
							<?php } ?>
							<?php if (!empty($meta_values['power'][0])) { ?>
								<tr>
									<td><label><small>Leistung: </small></label></td>
									<td>
										<small><?php echo $meta_values['power'][0]; ?> kW</small>
										<?php $zahl1 = $meta_values['power'][0];
										$zahl2 = 1.35962; ?>
										<?php $multiplikation = $zahl1 * $zahl2; ?> /
										<small><?php echo round($multiplikation); ?> PS ,</small>
									<?php } ?> <!-- Diesel, Benzin, etc. -->
									<?php if (!empty($meta_values['fuel'][0])) { ?>
										<small><?php echo $meta_values['fuel'][0]; ?></small>
									</td>
								</tr>
							<?php } ?>
						</table>
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
						<a href="<?php the_permalink(); ?>" class="vehicle-btn">Details</a>
					</div>
				</div> <!-- // vehicle-row -->
				<?php if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) { ?>
							<small><label>Kraftstoffverbr. komb. ca.:</label><span>
									<?php echo $meta_values['emissionFuelConsumption_Combined'][0] . ' l/100km *'; ?>
								</span></small>
						<?php } ?>
						<?php if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) { ?> <small><label>CO2 Emissionen
									komb. ca.:</label><span>
									<?php echo $meta_values['emissionFuelConsumption_CO2'][0] . ' g/km *'; ?>
								</span></small>
						<?php } ?>
						<?php if (!empty($meta_values['emissionClass'][0])) { ?> <small><label>Emissionsklasse:</label><span>
									<?php echo $meta_values['emissionClass'][0]; ?>
								</span></small>
						<?php } ?>
						<?php if (!empty($meta_values['efficiency_class'][0])) { ?>
							<small><label>Energieeffizienzklasse:</label><span>
									<?php echo $meta_values['efficiency_class'][0]; ?>
								</span></small>
						<?php } ?>
						<?php if (!empty($meta_values['combinedPowerConsumption'][0])) { ?> <small><label>Stromverbrauch
									komb.*: ≈</label><span>
									<?php echo $meta_values['combinedPowerConsumption'][0]; ?>
								</span> kwH/100km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-co2-emission-combined'][0])) { ?><small><label>CO₂-Emissionen
									(WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-co2-emission-combined'][0]; ?>
								</span> g/km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-consumption-fuel-combined'][0])) { ?><small><label>Verbrauch komb.
									(WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-consumption-fuel-combined'][0]; ?>
								</span> l/100km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-consumption-power-combined'][0])) { ?><small><label>Stromverbrauch
									(WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-consumption-power-combined'][0]; ?>
								</span> kWh/100km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-electric-range'][0])) { ?><small><label>Elektrische Reichweite
									(WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-electric-range'][0]; ?>
								</span> km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-consumption-fuel-combined-weighted'][0])) { ?><small><label>Gewichteter
									kombinierter Kraftstoffverbrauch für Plug-in-Hybride (WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-consumption-fuel-combined-weighted'][0]; ?>
								</span> l/100km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-consumption-power-combined-weighted'][0])) { ?><small><label>Gewichteter
									kombinierter Stromverbrauch für Plug-in-Hybride (WLTP)*: ≈</label><span>
									<?php echo $meta_values['wltp-consumption-power-combined-weighted'][0]; ?>
								</span> KwH/100km</small>
						<?php } ?>
						<?php if (!empty($meta_values['wltp-co2-emission-combined-weighted'][0])) { ?><small><label>Gewichtete
									Menge an Kohlendioxidemissionen für Plug-in-Hybride*: ≈</label><span>
									<?php echo $meta_values['wltp-co2-emission-combined-weighted'][0]; ?>
								</span> g/km</small>
						<?php } ?>
			</div>
			<?php
		}
		?>
	</div> <!-- FacetWP Template End -->
	<?php
}