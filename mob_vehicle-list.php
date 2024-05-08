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
            'orderby' => ''
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
    'facetwp' => true,
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
                                the_post_thumbnail('medium');
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
                        <ul class="vehicle-emission">
					<?php 
					 if (!empty($meta_values['wltp-combined-fuel'][0])) { 
						$content .= '<tr><td><span>Kraftstoffverbrauch:</span></td><td> 
						<strong>' . $meta_values['wltp-combined-fuel'][0] . 'l/100km (kombiniert)</strong><br>';
					
						if (!empty($meta_values['wltp-city-fuel'][0])) {
							$content .= $meta_values['wltp-city-fuel'][0] . ' l/100km (Innenstadt)<br>';
						}
						if (!empty($meta_values['wltp-suburban-fuel'][0])) {
							$content .= $meta_values['wltp-suburban-fuel'][0] . ' l/100km (Stadtrand)<br>';
						}
						if (!empty($meta_values['wltp-rural-fuel'][0])) {
							$content .= $meta_values['wltp-rural-fuel'][0] . ' l/100km (Landstraße)<br>';
						}
						if (!empty($meta_values['wltp-highway-fuel'][0])) {
							$content .= $meta_values['wltp-highway-fuel'][0] . ' l/100km (Autobahn)<br>';
						}
					
						$content .= '</td></tr>';
					} 
					?>
					<!-- // Combined fuel consumption for all nonelectric vehicles, optional for plugin hybrids, number in l/100km (natural gas (CNG) in kg/100km) -->
					<?php if (!empty($meta_values['wltp-consumption-fuel-combined'][0])) { ?>
                        <li><small>Verbrauch komb.*: ≈<?php echo $meta_values['wltp-consumption-fuel-combined'][0]; ?> l/100km</small></li>
                       
                    <?php } ?>
                    <?php if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) { ?>
                        <li><small>Verbrauch komb.*: ≈<?php echo $meta_values['emissionFuelConsumption_Combined'][0]; ?> l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['emissionFuelConsumption_Inner'][0])) { ?>
                        <li><small>Verbrauch innerorts*: ≈<?php echo $meta_values['emissionFuelConsumption_Inner'][0]; ?> l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['emissionFuelConsumption_Outer'][0])) { ?>
                        <li><small>Verbrauch außerorts*: ≈<?php echo $meta_values['emissionFuelConsumption_Outer'][0]; ?> l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) { ?>
                        <li><small>CO2-Emissionen komb.*: ≈<?php echo $meta_values['emissionFuelConsumption_CO2'][0]; ?> g/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['combinedPowerConsumption'][0])) { ?>
                        <li><small>Stromverbrauch komb.*: ≈<?php echo $meta_values['combinedPowerConsumption'][0]; ?> kwH/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['emissionSticker'][0])) { ?>
                        <li><small>Emissionsklasse: <?php echo $meta_values['emissionSticker'][0]; ?></small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['efficiency_class_image_url'][0])) { ?>
                        <img src="<?php echo $meta_values['efficiency_class_image_url'][0]; ?>" />
                    <?php } ?>
                    <!-- Verbrauch kombiniert -->
                    <?php if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) { ?>
                        <li style="color: red;"><small>Kraftstoffverbr. komb. ca.:
                                    <?php echo $meta_values['emissionFuelConsumption_Combined'][0] . ' l/100km *'; ?>
                                </small></li>
                    <?php } ?>
                    <!-- CO2 Emission kombiniert -->
                    <?php if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) { ?>
                        <li style="color: red;"><small>CO2 Emissionen komb. ca.: 
                                    <?php echo $meta_values['emissionFuelConsumption_CO2'][0] . ' g/km *'; ?>
                                </small></li>
                    <?php } ?>
                    <!-- Stromverbrauch kombiniert -->
                    <?php if (!empty($meta_values['combinedPowerConsumption'][0])) { ?>
                        <li style="color: red;"><small>Stromverbrauch komb.*: ≈ 
                                    <?php echo $meta_values['combinedPowerConsumption'][0] . ' kwH/100km' ?>
                                </small></li>
                    <?php } ?>
                    <!-- Emissionsklasse -->
                    <?php if (!empty($meta_values['emissionClass'][0])) { ?>
                        <li style="color: red;"><small>Emissionsklasse: 
                                    <?php echo $meta_values['emissionClass'][0]; ?>
                                </small></li>
                    <?php } ?>
                    <!-- Energieeffizienzklasse -->
                    <?php if (!empty($meta_values['efficiency_class'][0])) { ?>
                        <li style="color: red;"><small>Energieeffizienzklasse: 
                                    <?php echo $meta_values['efficiency_class'][0]; ?>
                                </small></li>
                    <?php } ?>
                    <!-- CO2-Emissionen	-->
                    <?php if (!empty($meta_values['wltp-co2-emission'][0])) { ?>
                        <li><small>CO₂-Emissionen*: ≈ 
                                    <?php echo $meta_values['wltp-co2-emission'][0]; ?>
                                 g/km</small></li>
                    <?php } ?>
                    <!-- CO2-Emissionen	-->
                    <?php if (!empty($meta_values['wltp-co2-class'][0])) { ?>
                        <li><small>CO2-Klasse auf Basis der CO2-Emissionen: 
                                    <?php echo $meta_values['wltp-co2-class'][0]; ?>
                                </small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['wltp-electric-range'][0])) { ?>
                        <li><small>Elektrische Reichweite: 
                                    <?php echo $meta_values['wltp-electric-range'][0]; ?>
                                 km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['wltp-electric-range-equivalent-all'][0])) { ?>
                        <li><small>Elektrische Reichweite (EAER): 
                                    <?php echo $meta_values['wltp-electric-range-equivalent-all'][0]; ?>
                                 km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['wltp-weighted-combined-fuel'][0])) { ?>
                        <li><small>Verbrauch gewichtet, kombiniert: 
                                    <?php echo $meta_values['wltp-weighted-combined-fuel'][0]; ?>
                                 kg/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_values['wltp-combined-fuel'][0])) { ?>
                        <li><small>Verbrauch kombiniert: 
                                    <?php echo $meta_values['wltp-combined-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>

                    <?php if (!empty($meta_value['wltp-city-fuel'][0])) { ?>
                        <li><small>Verbrauch Innenstadt: 
                                    <?php echo $meta_values['wltp-city-fuel'][0]; ?>
                                l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-suburban-fuel'][0])) { ?>
                        <li><small>Verbrauch Stadtrand: 
                                    <?php echo $meta_values['wltp-suburban-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-rural-fuel'][0])) { ?>
                        <li><small>Verbrauch Landstraße: 
                                    <?php echo $meta_values['wltp-rural-fuel'][0]; ?>
                                l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-highway-fuel'][0])) { ?>
                        <li><small>Verbrauch Autobahn: 
                                    <?php echo $meta_values['wltp-highway-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-combined-power'][0])) { ?>
                        <li><small>Stromverbrauch kombiniert: 
                                    <?php echo $meta_values['wltp-combined-power'][0]; ?>
                                 kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-city-power'][0])) { ?>
                        <li><small>Stromverbrauch Innenstadt: 
                                    <?php echo $meta_values['wltp-city-power'][0]; ?>
                                 kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-suburban-power'][0])) { ?>
                        <li><small>Stromverbrauch Stadtrand: 
                                    <?php echo $meta_values['wltp-suburban-power'][0]; ?>
                                 kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-rural-power'][0])) { ?>
                        <li><small>Stromverbrauch Landstraße: 
                                    <?php echo $meta_values['wltp-rural-power'][0]; ?>
                                 kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-highway-power'][0])) { ?>
                        <li><small>Stromverbrauch Autobahn: <span>
                                    <?php echo $meta_values['wltp-highway-power'][0]; ?>
                                 kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-empty-combined-fuel'][0])) { ?>
                        <li><small>Verbrauch bei entladener Batterie kombiniert: 
                                    <?php echo $meta_values['wltp-empty-combined-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-empty-city-fuel'][0])) { ?>
                        <li><small>Verbrauch bei entladener Batterie Innenstadt: 
                                    <?php echo $meta_values['wltp-empty-city-fuel'][0]; ?>
                                l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-empty-suburban-fuel'][0])) { ?>
                        <li><small>Verbrauch bei entladener Batterie Stadtrand: 
                                    <?php echo $meta_values['wltp-empty-suburban-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-empty-rural-fuel'][0])) { ?>
                        <li><small>Verbrauch bei entladener Batterie Landstraße: 
                                    <?php echo $meta_values['wltp-empty-rural-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-empty-highway-fuel'][0])) { ?>
                        <li><small>Verbrauch bei entladener Batterie Autobahn: 
                                    <?php echo $meta_values['wltp-empty-highway-fuel'][0]; ?>
                                 l/100km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-fuel-price-year'][0])) { ?>
                        <li><small>Kraftstoffpreis [Jahr]: 
                                    <?php echo $meta_values['wltp-fuel-price-year'][0]; ?>
                                 EUR/l</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-power-price-year'][0])) { ?>
                        <li><small>Strompreis [Jahr]: 
                                    <?php echo $meta_values['wltp-power-price-year'][0]; ?>
                                 EUR/kW/h</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-consumption-price-year'][0])) { ?>
                        <li><small>Jahresdurchschnitt [Jahr]: 
                                    <?php echo $meta_values['wltp-consumption-price-year'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-consumption-costs'][0])) { ?>
                        <li><small>Energiekosten bei 15.000 km Jahresfahrleistung: 
                                    <?php echo $meta_values['wltp-consumption-costs'][0]; ?>
                                 EUR</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-low-base'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-low-base'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-low-accumulated'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-low-accumulated'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-middle-base'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-middle-base'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-middle-accumulated'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-middle-accumulated'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-high-base'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-high-base'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-co2-costs-high-accumulated'][0])) { ?>
                        <li><small>CO2-Kosten bei einem angenommenen mittleren durchschnittlichen CO2-Preis von: 
                                    <?php echo $meta_values['wltp-co2-costs-high-accumulated'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-tax'][0])) { ?>
                        <li><small>Kraftfahrzeugsteuer: 
                                    <?php echo $meta_values['wltp-tax'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-cost-model-from'][0])) { ?>
                        <li><small>Zeitspanne von: 
                                    <?php echo $meta_values['wltp-cost-model-from'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>
                    <?php if (!empty($meta_value['wltp-cost-model-till'][0])) { ?>
                        <li><small>Zeitspanne bis: 
                                    <?php echo $meta_values['wltp-cost-model-till'][0]; ?>
                                 EUR/km</small></li>
                    <?php } ?>


                </ul>

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