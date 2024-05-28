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
                                <?php $show_date = date('m.Y', strtotime($meta_values['firstRegistration'][0])); ?>
                                <?php echo $show_date; ?>
                                ·
                            </small>
                        <?php } ?>
                        <?php if (!empty($meta_values['mileage'][0])) { ?>
                            <small>
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
                        <?php if (!empty($meta_values['wltp-weighted-combined-fuel'][0])) { ?>
                        <span>Kraftstoffverbrauch gewichtet, kombiniert: 
                                    <?php echo $meta_values['wltp-weighted-combined-fuel'][0]; ?>
                                 l/100km</span><br>
                    <?php } ?>
 <?php if (!empty($meta_values['wltp-combined-fuel'][0])) { ?>
                        <span>Verbrauch komb.: 
                                    <?php echo $meta_values['wltp-combined-fuel'][0]; ?>
                                 l/100km</span><br>
                    <?php } ?>
                    <?php if (!empty($meta_values['wltp-weighted-combined-power'][0])) { ?>
                        <span>Stromverbrauch gewichtet, kombiniert: 
                                    <?php echo $meta_values['wltp-weighted-combined-power'][0]; ?>
                                 kWh/100km</span><br>
                    <?php } ?>
                     <?php if (!empty($meta_value['wltp-combined-power'][0])) { ?>
                        <span>Stromverbrauch komb.: 
                                    <?php echo $meta_values['wltp-combined-power'][0]; ?>
                                 kW/h</span><br>
                    <?php } ?>
                   
                    <?php if (!empty($meta_value['wltp-empty-combined-fuel'][0])) { ?>
                        <span>Verbrauch bei entladener Batterie kombiniert: 
                                    <?php echo $meta_values['wltp-empty-combined-fuel'][0]; ?>
                                 l/100km</span><br>
                    <?php } ?>
                   
  <!-- CO2-Emissionen	-->
                    <?php if (!empty($meta_values['wltp-co2-emission'][0])) { ?>
                        <span>CO₂-Emissionen komb.: <?php echo $meta_values['wltp-co2-emission'][0]; ?>
                                 g/km</span><br>
                    <?php } ?>
                    <!-- CO2-Emissionen	-->
                   
 
 <?php if (!empty($meta_values['wltp-co2-class'][0])) { ?>
                        <span>CO₂-Klasse: 
                                    <?php echo $meta_values['wltp-co2-class'][0]; ?>
                                </span><br>
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
    <p><strong>Angaben zum Verbrauch</strong><br>
    <p>Die Informationen erfolgen gemäß der Pkw-Energieverbrauchskennzeichnungsverordnung. Die angegebenen Werte wurden nach dem vorgeschrieben Messverfahren WLTP (World Harmonised Light Vehicles Test Procedure) ermittelt. Der Kraftstoffverbrauch und der CO2-Ausstoß eines PKW sind nicht nur von der effizienten Ausnutzung des Kraftstoffs durch den PKW, sondern auch vom Fahrstil und anderen nichttechnischen Faktoren abhängig. CO2 ist das fr die Erderwärmung hauptsächlich verantwortliche Treibgas. Ein Leitfaden ber den Kraftstoffverbrauch und die CO2-Emissionen aller in Deutschland angebotenen neuen PKW-Modelle ist unentgeltlich in elektronischer Form einsehbar an jedem Verkaufsort in Deutschland, an dem neue Personenkraftfahrzeuge ausgestellt oder angeboten werden. Der Leitfaden ist auch abrufbar unter der Internetadresse: <a href="https://www.dat.de/co2/">https://www.dat.de/co2/</a>.</p>
    <p>Es werden nur die CO2-Emissionen angegeben, die durch den Betrieb des PKW entstehen. CO2-Emissionen, die durch die Produktion und Bereitstellung des PKW sowie des Kraftstoffes bzw. der Energieträger entstehen oder vermieden werden, werden bei der Ermittlung der CO2-Emissionen gemäß WLTP nicht bercksichtigt.</p>
   
    <?php
}

