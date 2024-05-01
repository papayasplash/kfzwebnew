<?php
if (!function_exists('display_meta_value')) {
    function display_meta_value($meta_values, $key, $label = '', $suffix = '', $isBoolean = false, $trueValue = '', $falseValue = '') {
        if (!empty($meta_values[$key][0])) {
            $value = $meta_values[$key][0];
            if ($isBoolean) {
                echo "<small>$label" . ($value == 'true' ? $trueValue : $falseValue) . "</small>";
            } else {
                echo "<small>$label" . htmlspecialchars($value) . "$suffix</small>";
            }
        }
    }
}

if (!function_exists('display_price_section')) {
    function display_price_section($meta_values) {
        if (!empty($meta_values['price'][0])) {
            $price = htmlspecialchars($meta_values['price'][0]);
            $vatable = $meta_values['vatable'][0] == 'false' ? 'MwSt. nicht ausweisbar' : 'Inkl. 19% MwSt.';
            echo <<<PRICE
                <div itemprop="priceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification">
                    <meta itemprop="priceCurrency" content="EUR">
                    <meta itemprop="price" content="$price">
                    <strong>$price € (Brutto)</strong><br><small>$vatable</small>
                </div>
PRICE;
        }
    }
}

?>

<article id="post-<?php the_ID(); ?>" class="vehicle">
    <hgroup>
        <a href="<?php the_permalink(); ?>">
            <h2 class="vehicle-title"><?php the_title(); ?></h2>
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
            <?php
            display_meta_value($meta_values, 'firstRegistration', 'EZ: ', ' · ');
            display_meta_value($meta_values, 'mileage', '', ' km · ');
            display_meta_value($meta_values, 'power', '', ' kW · ', false, '', '');
            display_meta_value($meta_values, 'gearbox', '', ' · ');
            display_meta_value($meta_values, 'damage_and_unrepaired', '', '', true, 'Unfallfrei', '');
            display_meta_value($meta_values, 'fuel', '', ' · ');
            display_meta_value($meta_values, 'exterior_color', '', ' · ');
            display_meta_value($meta_values, 'next_inspection', 'HU: ', ' · ');
            display_meta_value($meta_values, 'door_count', '', ' Türen · ');
            ?>
        </div>
        <div class="vehicle-price">
            <?php display_price_section($meta_values); ?>
            <br>
            <a href="<?php the_permalink(); ?>" class="vehicle-btn wp-block-button__link wp-element-button">Details</a>
        </div>
    </div> <!-- // vehicle-row -->
    <div class="vehicle-emission">
        <?php
        display_meta_value($meta_values, 'wltp-co2-emission', 'CO2-Emissionen: ', ' g/km*');
        display_meta_value($meta_values, 'emissionFuelConsumption_Combined', 'Kraftstoffverbr. komb. ca.: ', ' l/100km*');
        display_meta_value($meta_values, 'emissionFuelConsumption_CO2', 'CO2 Emissionen komb. ca.: ', ' g/km*');
        display_meta_value($meta_values, 'emissionClass', 'Emissionsklasse: ');
        display_meta_value($meta_values, 'efficiency_class', 'Energieeffizienzklasse: ');
        // Fügen Sie hier weitere Emissions- und Verbrauchswerte hinzu
        ?>
    </div>
</article>