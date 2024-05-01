<?php
//Add template pages for the custom post listing
function mob_custom_type_templates($template)
{
    global $mob_data;
    $post_types = array(
        $mob_data['customType']
    );
    if (is_post_type_archive($post_types) && !file_exists(get_stylesheet_directory() . '/archive-fahrzeuge.php'))
        $template = plugin_dir_path(__FILE__) . 'template/archive-fahrzeuge.php';
    if (is_singular($post_types) && !file_exists(get_stylesheet_directory() . '/single-fahrzeuge.php'))
        $template = plugin_dir_path(__FILE__) . 'template/single-fahrzeuge.php';
    return $template;
}

function check_single_fahrzeuge($content = null)
{
    if (is_singular('fahrzeuge')) {
        $meta_values = get_post_meta(get_the_ID());
        // print_r($meta_values);
        $content = '
        <div itemprop="itemOffered" itemscope itemtype="http://schema.org/Car"><div class="row"><div class="col-xs-12"><h2 class="text-left title" itemprop="name">' . get_the_title() . '</h2><h5 class="text-left" itemprop="category">' . $meta_values['category'][0] . ', ' . $meta_values['condition'][0] . '</h5></div></div><div class="row"><div class="col-xs-12 col-sm-7">';
        $options = get_option('MobileDE_option');
        // if (isset($options['mob_slider_option']) && $options['mob_slider_option'] == 'yes') {
        //     $content .= '<div class="slider-main" style="overflow: hidden;">';
        //     if (!empty($meta_values['ad_gallery'])) {
        //         $mob_images = $meta_values['ad_gallery'];
        //         foreach ($mob_images as $mob_image) {
        //             $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
        //             $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
        //             $content .= '<img src="' . $bigimage . '" />';
        //         }
        //     } else {
        //         more_fields(true); // Reset index.
        //         while (($more_pics = more_fields())) {
        //             $content .= '<img src="' . $more_pics['file'] . '"/>';
        //         }
        //     }
        //     $content .= '</div><div class="slider-nav" style="overflow: hidden;">';
        //     if (!empty($meta_values['ad_gallery'])) {
        //         $mob_images = $meta_values['ad_gallery'];
        //         foreach ($mob_images as $mob_image) {
        //             $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
        //             $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
        //             $content .= '<img src="' . $mob_image_ssl . '" />';
        //         }
        //     } else {
        //         more_fields(true); 
        //         while (($more_pics = more_fields())) {
        //             $content .= '<img src="' . $more_pics['sizes']['thumbnail']['file'] . '"/>';
        //         }
        //     }
        //     $content .= '</div>';
        // } else {
        //     $content .= '<img class="img-responsive" src="';
        //     if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
        //         $content .= get_the_post_thumbnail_url();
        //     }
        //     $content .= '" itemprop="image"/><div class="row">';
        //     if (!empty($meta_values['ad_gallery'])) {
        //         $mob_images = $meta_values['ad_gallery'];
        //         foreach ($mob_images as $mob_image) {
        //             $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
        //             $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
        //             $content .= '<div class="col-xs-4 col-sm-3 col-lg-2 top15"><a href="' . $bigimage . '"><img class="img img-responsive" src="' . $mob_image_ssl . '" /></a></div>';
        //         }
        //     } else {
        //         more_fields(true); 
        //         while (($more_pics = more_fields())) {
        //             $content .= '<div class="col-xs-4 col-sm-3 col-lg-2 top15"><a href="' . $more_pics['file'] . '"><img class="img img-responsive" src="' . $more_pics['sizes']['thumbnail']['file'] . '"/></a></div>';
        //         }
        //     }   
        //     $content .= '</div>';
        // }
        $content .= '</div><hr class="visible-xs"><div class="col-xs-12 col-sm-5"><div class="row"><br><div class="col-xs-12" itemprop="makesOffer" itemscope itemtype="http://schema.org/Offer" itemref="product">';
        if (!empty($meta_values['price'][0])) {
            $content .= '<div itemprop="priceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification"><meta itemprop="priceCurrency" content="EUR"><meta itemprop="price" content="'.$meta_values['price'][0].'">';
            $content .= '<h3><strong>'.$meta_values['price'][0].' € (Brutto) </strong><br><small>';
            if ($meta_values['vatable'][0] == 'false') {
                $content .= 'MwSt. nicht ausweisbar</small></h3></div>';
            } else {
                $content .= 'Inkl. 19% MwSt.</small></h3></div>';
            }
            
            if (!empty($meta_values['available_from'][0])) {
                if ($meta_values['available_from'][0] == "Sofort") {
                    $content .= '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> sofort verfügbar';
                }
                if ($meta_values['available_from'][0] != "Sofort") {
                    $content .= '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> verfügbar ab ' . $meta_values['available_from'][0] . '';
                }
            }
            
            $content .= '</div></div><div class="row"><div class="col-xs-12"><table class="table responsive-table">';
            if (!empty($meta_values['mileage'][0])) {
                $content .= '<tr><td>Kilometerstand</td><td><strong> ' . $meta_values['mileage'][0] . ' km' . '</strong></td></tr>';
            }
            if (!empty($meta_values['addition'][0])) {
                $content .= '<tr><td>Zustand</td><td><strong>' . $meta_values['addition'][0] . '</strong></td></tr>';
            }
            if (!empty($meta_values['firstRegistration'][0])) {
                $content .= '<tr><td>Erstzulassung</td><td><strong>';
                $first_reg = date('m.Y', strtotime($meta_values['firstRegistration'][0]));
                $content .= $first_reg;
                $content .= '</strong></td></tr>';
            }
            if (!empty($meta_values['owners'][0])) {
                $content .= '<tr><td>Vorbesitzer</td><td><strong>' . $meta_values['owners'][0] . '</strong></td></tr>';
            }
            if (!empty($meta_values['nextInspection'][0])) {
                $content .= '<tr><td>Hauptuntersuchung</td><td><strong>';
                $hu_au = date('m.Y', strtotime($meta_values['nextInspection'][0]));
                $content .= $hu_au;
                $content .= '</strong></td></tr> ';
            }
            $content .= '</table></div></div>';
            if (!empty($meta_values['power'][0])) {
                $content .= '<h4><span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span><span> Leistung</span><span><strong> ' . $meta_values['power'][0] . ' kW (';
                $kw             = $meta_values['power'][0];
                $faktor         = 1.35962;
                $multiplikation = $kw * $faktor;
                $content .= round($multiplikation);
                $content .= 'PS)</strong></span><small> ';
            }
            if (!empty($meta_values['gearbox'][0])) {
                $content .= $meta_values['gearbox'][0];
            }
            if (!empty($meta_values['fuel'][0])) {
                $content .= ', ' . $meta_values['fuel'][0];
            }
            $content .= '</small></h4>';
        }
        $content .= '<div class="row kfz-web-spf"><div class="col-xs-12">';
        if (!empty($meta_values['XENON_HEADLIGHTS'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['XENON_HEADLIGHTS'][0].'</div>';
        }
        if (!empty($meta_values['NAVIGATION_SYSTEM'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['NAVIGATION_SYSTEM'][0].'</div>';
        }
        if (!empty($meta_values['ESP'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['ESP'][0].'</div>';
        }
        if (!empty($meta_values['HEAD_UP_DISPLAY'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['HEAD_UP_DISPLAY'][0].'</div>';
        }
        if (!empty($meta_values['FULL_SERVICE_HISTORY'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['FULL_SERVICE_HISTORY'][0].'</div>';
        }
        if (!empty($meta_values['BENDING_LIGHTS'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['BENDING_LIGHTS'][0].'</div>';
        }
        if (!empty($meta_values['PARKING_SENSORS'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['PARKING_SENSORS'][0].'</div>';
        }
        if (!empty($meta_values['PANORAMIC_GLASS_ROOF'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['PANORAMIC_GLASS_ROOF'][0].'</div>';
        }
        if (!empty($meta_values['CRUISE_CONTROL'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['CRUISE_CONTROL'][0].'</div>';
        }
        if (!empty($meta_values['ELECTRIC_HEATED_SEATS'][0])) {
            $content .= '<div class="btn btn-default disabled" style="margin-top: 5px;margin-right: 5px;"> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '.$meta_values['ELECTRIC_HEATED_SEATS'][0].'</div>';
        }
        $content .= '</div></div><hr><div class="row top15"><div class="col-xs-12"><h3>Fahrzeug Direktanfrage</h3></div><div class="col-xs-6"><a href="mailto:' . $meta_values['seller_email'][0] . '?subject=Direktanfrage zu ' . get_the_title() . '"><button class="btn btn-primary btn-block">E-Mail Anfrage</button></a></div><div class="col-xs-6"><a href="tel:+' . $meta_values['seller_phone_country_calling_code'][0] . $meta_values['seller_phone_area_code'][0] . $meta_values['seller_phone_number'][0] . '" class="btn btn-primary btn-block">+' . $meta_values['seller_phone_country_calling_code'][0] . ' ' . $meta_values['seller_phone_area_code'][0] . ' ' . $meta_values['seller_phone_number'][0] . '</a></div></div></div></div><br><br><hr><div class="row"><div class="col-xs-12"><h3>Detailinformationen</h3></div><div class="col-xs-12 col-sm-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Motor & Getriebe</h3></div><div class="panel-body">';
        if (!empty($meta_values['fuel'][0])) {
            $content .= '<span>Kraftstoff:</span> <strong> ' . $meta_values['fuel'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['cubic_capacity'][0])) {
            $content .= '<span>Hubraum:</span> <strong> ' . $meta_values['cubic_capacity'][0] . ' cm³</strong><br/>';
        }
        if (!empty($meta_values['gearbox'][0])) {
            $content .= '<span>Getriebe:</span> <strong> ' . $meta_values['gearbox'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['power'][0])) {
            $content .= '<span>Leistung:</span> <strong> ' . $meta_values['power'][0] . ' kW (';
            $kw             = $meta_values['power'][0];
            $faktor         = 1.35962;
            $multiplikation = $kw * $faktor;
            $content .= round($multiplikation);
            $content .= 'PS)</strong>';
        }
        $content .= '</div></div></div><div class="col-xs-12 col-sm-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-wrench placative-info" aria-hidden="true"></span> Daten</h3></div><div class="panel-body">';
        if (!empty($meta_values['condition'][0])) {
            $content .= '<span>Zustand:</span> <strong> ' . $meta_values['condition'][0] . '</strong><br>';
        }
        if (!empty($meta_values['firstRegistration'][0])) {
            $content .= '<span>Erstzulassung:</span><strong>';
            $first_reg = date('m.Y', strtotime($meta_values['firstRegistration'][0]));
            $content .= $first_reg;
            $content .= '</strong><br>';
        }
        if (!empty($meta_values['construction-year'][0])) {
            $content .= '<span>Baujahr:</span> <strong> ' . $meta_values['construction-year'][0] . '</strong><br>';
        }
        if (!empty($meta_values['nextInspection'][0])) {
            $content .= '<span>nächste HU/AU:</span> <strong> ';
            $nextInspection = date('m.Y', strtotime($meta_values['nextInspection'][0]));
            $content .= $nextInspection;
            $content .= '</strong>';
        }
        $content .= '</div></div></div>
        <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-leaf placative-info" aria-hidden="true"></span> Energie & Umwelt</h3>
        </div>
        <div class="panel-body">
        <table>
        <style>
        td {
            vertical-align: top;
            height: 25px;
          }
        </style>
        <tbody align="top">

        ';
        
        if (!empty($meta_values['wltp-co2-emission'][0])) {
            $content .= '<tr><td><span>CO2-Emissionen (komb.)* <div class="popover-container"><button>&#x1F6C8;</button><div class="popover-content"><p>CO₂-Emissionen</p><p>Es werden nur die CO₂-Emissionen angegeben, die durch den Betrieb des Pkw entstehen. CO₂-Emissionen, die durch die Produktion und Bereitstellung des Pkw sowie des Kraftstoffes bzw. der Energieträger entstehen oder vermieden werden, werden bei der Ermittlung der CO₂-Emissionen gemäß WLTP nicht berücksichtigt.</p></div></div>: </span></td><td><strong>' . $meta_values['wltp-co2-emission'][0] . ' g/km</strong></td></tr>';
        }
        if (!empty($meta_values['wltp-co2-class'][0]) && !empty($meta_values['wltp-co2-class-discharged'][0])) { 
            $content .= '<tr><td><span>CO2-Klasse: </span></td><td><small>auf Basis der CO2-Emissionen (kombiniert)</small><br><img style="padding: .5rem; background: white; border: 1px solid lightgray;" src="https://img.classistatic.de/api/v1/mo-prod/images/co2class-' . $meta_values['wltp-co2-class'][0] . '-' . $meta_values['wltp-co2-class-discharged'][0] . '-de?rule=mo-240.jpg" /></td></tr>';
        } elseif (!empty($meta_values['wltp-co2-class'][0]) ) { 
            $content .= '<tr><td><span>CO2-Klasse: </span></td><td><small>auf Basis der CO2-Emissionen (kombiniert)</small><br><img style="padding: .5rem; background: white; border: 1px solid lightgray;" src="https://img.classistatic.de/api/v1/mo-prod/images/co2class-' . $meta_values['wltp-co2-class'][0] . '?rule=mo-240.jpg" /></td></tr>';
        }
        
        if (!empty($meta_values['wltp-combined-fuel'][0]) && ($meta_values['fuel'][0] !== 'Elektro' && $meta_values['fuel'][0] !== 'Plug-in-Hybrid' && $meta_values['fuel'][0] !== 'Hybrid (Benzin/Elektro)')) { 
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
        if (!empty($meta_values['wltp-electric-range'][0])) { 
            $content .= '<tr><td><span>Elektrische Reichweite:</span></td><td> <strong>' . $meta_values['wltp-electric-range'][0] . ' km</strong><br></td></tr>';
        } 
        if (!empty($meta_values['wltp-electric-range-equivalent-all'][0])) { 
            $content .= '<tr><td><span>Elektrische Reichweite (EAER):</span></td><td> <strong>' . $meta_values['wltp-electric-range-equivalent-all'][0] . ' km</strong><br></td></tr>';
        } 
        if (!empty($meta_values['wltp-weighted-combined-fuel'][0])) { 
            $content .= '<tr><td><span>Verbrauch gewichtet, kombiniert:</span></td><td> <strong>' . $meta_values['wltp-weighted-combined-fuel'][0] . ' l/km</strong><br></td></tr>';
        } 
        if (!empty($meta_values['wltp-combined-power'][0])) { 
            $content .= '<tr><td><span>Stromverbrauch bei rein elektrischem Antrieb:</span></td><td> 
            <strong>' . $meta_values['wltp-combined-power'][0] . ' kWh/100km (kombiniert)</strong><br>';
        
            if (!empty($meta_values['wltp-city-power'][0])) {
                $content .= $meta_values['wltp-city-power'][0] . ' kWh/100km (Innenstadt)<br>';
            }
            if (!empty($meta_values['wltp-suburban-power'][0])) {
                $content .= $meta_values['wltp-suburban-power'][0] . ' kWh/100km (Stadtrand)<br>';
            }
            if (!empty($meta_values['wltp-rural-power'][0])) {
                $content .= $meta_values['wltp-rural-power'][0] . ' kWh/100km (Landstraße)<br>';
            }
            if (!empty($meta_values['wltp-highway-power'][0])) {
                $content .= $meta_values['wltp-highway-power'][0] . ' kWh/100km (Autobahn)<br>';
            }
        
            $content .= '</td></tr>';
        } 
        if (!empty($meta_values['wltp-empty-combined-fuel'][0]) && !empty($meta_values['wltp-electric-range']) || !empty($meta_values['wltp-electric-range-equivalent-all'])) { 
            $content .= '<tr><td><span>Kraftstoffverbrauch bei entladener Batterie:</span></td><td> 
            <strong>' . $meta_values['wltp-empty-combined-fuel'][0] . ' l/100km (kombiniert)</strong><br>';
        
            if (!empty($meta_values['wltp-empty-city-fuel'][0])) {
                $content .= $meta_values['wltp-empty-city-fuel'][0] . ' l/100km (Innenstadt)<br>';
            }
            if (!empty($meta_values['wltp-empty-suburban-fuel'][0])) {
                $content .= $meta_values['wltp-empty-suburban-fuel'][0] . ' l/100km (Stadtrand)<br>';
            }
            if (!empty($meta_values['wltp-empty-rural-fuel'][0])) {
                $content .= $meta_values['wltp-empty-rural-fuel'][0] . ' l/100km (Landstraße)<br>';
            }
            if (!empty($meta_values['wltp-empty-highway-fuel'][0])) {
                $content .= $meta_values['wltp-empty-highway-fuel'][0] . ' l/100km (Autobahn)<br>';
            }
            $content .= '</td></tr>';
        } 
        if (!empty($meta_values['wltp-fuel-price-year'][0])) {
            $formattedPrice = number_format((float)$meta_values['wltp-fuel-price-year'][0], 2, ',', '.');
            $content .= '<tr><td><span>Kraftstoffpreis:</span></td><td> ' . formatCurrency($meta_values['wltp-fuel-price-year'][0]) . '/l';
        
            if (!empty($meta_values['wltp-consumption-price-year'][0])) {
                $content .= ' <small>(Jahresdurchschnitt ' . $meta_values['wltp-consumption-price-year'][0] . ')</small>';
            }
        
            $content .= '<br></td></tr>';
        }
        if (!empty($meta_values['wltp-power-price-year'][0])) {
            $content .= '<tr><td><span>Strompreis:</span></td><td> ' . formatCurrency($meta_values['wltp-power-price-year'][0]) . '/kWh';
        
            if (!empty($meta_values['wltp-consumption-price-year'][0])) {
                $content .= ' <small>(Jahresdurchschnitt ' . $meta_values['wltp-consumption-price-year'][0] . ')</small>';
            }
        
            $content .= '<br></td></tr>';
        }
        if (!empty($meta_values['wltp-consumption-costs'][0])) { 
            $content .= '<tr><td><span>Energiekosten bei 15.000 km Jahresfahrleistung:</span></td><td> ' . formatCurrency($meta_values['wltp-consumption-costs'][0]) . '/Jahr<br>';
        } 




        $middleAccumulated = !empty($meta_values['wltp-co2-costs-middle-accumulated'][0]) ? $meta_values['wltp-co2-costs-middle-accumulated'][0] : 0;
        $middleBase = !empty($meta_values['wltp-co2-costs-middle-base'][0]) ? $meta_values['wltp-co2-costs-middle-base'][0] : 0;
        $lowAccumulated = !empty($meta_values['wltp-co2-costs-low-accumulated'][0]) ? $meta_values['wltp-co2-costs-low-accumulated'][0] : 0;
        $lowBase = !empty($meta_values['wltp-co2-costs-low-base'][0]) ? $meta_values['wltp-co2-costs-low-base'][0] : 0;
        $highAccumulated = !empty($meta_values['wltp-co2-costs-high-accumulated'][0]) ? $meta_values['wltp-co2-costs-high-accumulated'][0] : 0;
        $highBase = !empty($meta_values['wltp-co2-costs-high-base'][0]) ? $meta_values['wltp-co2-costs-high-base'][0] : 0;
        
        $content .= '<tr><td>Mögliche CO₂-Kosten über die nächsten 10 Jahre (15.000 km/Jahr)</td><td> <strong>' . formatCurrency($middleAccumulated) . ' (bei einem angenommenen mittleren durchschnittlichen CO2-Preis von ' . formatCurrency($middleBase) . '/t)</strong><br>';
        if ($lowBase > 0) { 
           $content .= formatCurrency($lowAccumulated) . ' (bei einem angenommenen niedrigen durchschnittlichen CO2-Preis von ' . formatCurrency($lowBase) . '/t)<br>'; 
        }
        if ($highBase > 0) { 
            $content .= formatCurrency($highAccumulated) . ' (bei einem angenommenen hohen durchschnittlichen CO2-Preis von ' . formatCurrency($highBase) . '/t)<br>'; 
        }
            
        
        if (!empty($meta_values['wltp-tax'][0])) {
            $content .= '<tr><td><span>Kraftfahrzeugsteuer:</span></td><td> ' . formatCurrency($meta_values['wltp-tax'][0]) . '/Jahr<br></td></tr>';
        }
       
        if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) {
            $content .= '<tr><td><span>Verbrauch komb.*:</span></td><td> <strong> ≈' . $meta_values['emissionFuelConsumption_Combined'][0] . ' l/100km</strong><br></td></tr>';
        }
        if (!empty($meta_values['emissionFuelConsumption_Inner'][0])) {
            $content .= '<tr><td><span>Verbrauch innerorts*:</span></td><td> <strong> ≈' . $meta_values['emissionFuelConsumption_Inner'][0] . ' l/100km</strong><br></td></tr>';
        }
        if (!empty($meta_values['emissionFuelConsumption_Outer'][0])) {
            $content .= '<tr><td><span>Verbrauch außerorts*:</span></td><td> <strong> ≈' . $meta_values['emissionFuelConsumption_Outer'][0] . ' l/100km</strong><br></td></tr>';
        }
        if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) {
            $content .= '<tr><td><span>CO2-Emissionen komb.*:</span></td><td> <strong> ≈' . $meta_values['emissionFuelConsumption_CO2'][0] . ' g/km</strong><br></td></tr>';
        }
        if (!empty($meta_values['combinedPowerConsumption'][0])) {
            $content .= '<tr><td><span>Stromverbrauch komb.*:</span></td><td> <strong> ≈' . $meta_values['combinedPowerConsumption'][0] . ' kwH/100km</strong><br></td></tr>';
        }
        if (!empty($meta_values['emissionSticker'][0])) {
            $content .= '<tr><td><span>Emissionsklasse:</span></td><td> ' . $meta_values['emissionSticker'][0] . '';
        }
        $content .= '
        </tbody>
        </table>
        </div></div></div><div class="col-xs-12"><hr></div><div class="col-xs-12 col-sm-6"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-camera placative-info" aria-hidden="true"></span> Optik</h3></div><div class="panel-body">';
        if (!empty($meta_values['manufacturer_color_name'][0])) {
            $content .= '<span>Farbbezeichnung:</span> <strong> ' . $meta_values['manufacturer_color_name'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['exteriorColor'][0])) {
            $content .= '<span>Außenfarbe:</span> <strong> ' . $meta_values['exteriorColor'][0];
            if (!empty($meta_values['METALLIC'][0])) {
                $content .= '(' . $meta_values['METALLIC'][0] . ')';
            }
            $content .= '</strong><br/>';
        }
        if (!empty($meta_values['interior_type'][0])) {
            $content .= '<span>Innenausstattung:</span> <strong> ' . $meta_values['interior_type'][0] . '';
            if (!empty($meta_values['interior_color'][0])) {
                $content .= ', ';
                $content .= $meta_values['interior_color'][0];
            }
            $content .= '</strong><br/>';
        }
        $content .= '</div></div></div><div class="col-xs-12 col-sm-6"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-list placative-info" aria-hidden="true"></span> Weitere Daten</h3></div><div class="panel-body">';
        if (!empty($meta_values['door_count'][0])) {
            $content .= '<span>Anzahl d. Türen:</span> <strong>' . $meta_values['door_count'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['num_seats'][0])) {
            $content .= '<span>Anzahl Sitzplätze:</span> <strong>' . $meta_values['num_seats'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['vehicleListingID'][0])) {
            $content .= '<span>Fahrzeugnummer:</span> <strong> ' . $meta_values['vehicleListingID'][0] . '</strong><br/>';
        }
        if (!empty($meta_values['schwacke-code'][0])) {
            $content .= '<span>Schwacke Code :</span> <strong> ' . $meta_values['schwacke-code'][0] . '</strong>';
        }
        $content .= '</div></div></div><div class="col-xs-12"><hr>';
        if(!empty($meta_values['ABS'][0]) || !empty($meta_values['BENDING_LIGHTS'][0]) || !empty($meta_values['DAYTIME_RUNNING_LIGHTS'][0]) || !empty($meta_values['ESP'][0]) || !empty($meta_values['FRONT_FOG_LIGHTS'][0]) || !empty($meta_values['IMMOBILIZER'][0]) || !empty($meta_values['ISOFIX'][0]) || !empty($meta_values['PARKING_SENSORS'][0]) || !empty($meta_values['POWER_ASSISTED_STEERING'][0]) || !empty($meta_values['TRACTION_CONTROL_SYSTEM'][0]) || !empty($meta_values['XENON_HEADLIGHTS'][0])) {
        $content .= '<div class="row"><div class="col-xs-12"><h4>Sicherheit:</h4></div>';
        // ABS
        if (!empty($meta_values['ABS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ABS'][0] . '</div>';
        }
        // Kurvenlicht
        if (!empty($meta_values['BENDING_LIGHTS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['BENDING_LIGHTS'][0] . '</div>';
        } 
        // Tagfahrlicht
        if (!empty($meta_values['DAYTIME_RUNNING_LIGHTS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['DAYTIME_RUNNING_LIGHTS'][0] . '</div>';    
        }
        // ESP
        if (!empty($meta_values['ESP'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ESP'][0] . '</div>';
		}
		// Nebelscheinwerfer
		if (!empty($meta_values['FRONT_FOG_LIGHTS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['FRONT_FOG_LIGHTS'][0] . '</div>';
		}
		// Elektr. Wegfahrsperre
		if (!empty($meta_values['IMMOBILIZER'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['IMMOBILIZER'][0] . '</div>';
		}
		// Isofix
		if (!empty($meta_values['ISOFIX'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ISOFIX'][0] . '</div>';
		}
		// Einparkhilfe
		if (!empty($meta_values['PARKING_SENSORS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['PARKING_SENSORS'][0] . '</div>';
		}
		// Servolenkung
		if (!empty($meta_values['POWER_ASSISTED_STEERING'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['POWER_ASSISTED_STEERING'][0] . '</div>';
		}
		// Traktionskontrolle
		if (!empty($meta_values['TRACTION_CONTROL_SYSTEM'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['TRACTION_CONTROL_SYSTEM'][0] . '</div>';
		}
		//Xenonscheinwerfer
		if (!empty($meta_values['XENON_HEADLIGHTS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['XENON_HEADLIGHTS'][0] . '</div>';
		}
        $content .= '</div><br><hr>';
        }
        if(!empty($meta_values['AUTOMATIC_RAIN_SENSOR'][0]) || !empty($meta_values['AUXILIARY_HEATING'][0]) || !empty($meta_values['CENTRAL_LOCKING'][0]) || !empty($meta_values['CRUISE_CONTROL'][0]) || !empty($meta_values['ELECTRIC_ADJUSTABLE_SEATS'][0]) || !empty($meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0]) || !empty($meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0]) || !empty($meta_values['ELECTRIC_HEATED_SEATS'][0]) || !empty($meta_values['ELECTRIC_WINDOWS'][0]) || !empty($meta_values['LIGHT_SENSOR'][0]) || !empty($meta_values['MULTIFUNCTIONAL_WHEEL'][0]) || !empty($meta_values['PANORAMIC_GLASS_ROOF'][0]) || !empty($meta_values['SUNROOF'][0])) {
		$content .= '<div class="row"><div class="col-xs-12"><h4>Komfort:</h4></div>';
		if (!empty($meta_values['AUTOMATIC_RAIN_SENSOR'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['AUTOMATIC_RAIN_SENSOR'][0] . '</div>';
		}
		// Standheizung
		if (!empty($meta_values['AUXILIARY_HEATING'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['AUXILIARY_HEATING'][0] . '</div>';
		}
		// Zentralverriegelung
		if (!empty($meta_values['CENTRAL_LOCKING'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['CENTRAL_LOCKING'][0] . '</div>';
        }
		// Tempomat
		if (!empty($meta_values['CRUISE_CONTROL'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['CRUISE_CONTROL'][0] . '</div>';
		}
		// Elektr. Sitzeinstellung
		if (!empty($meta_values['ELECTRIC_ADJUSTABLE_SEATS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ELECTRIC_ADJUSTABLE_SEATS'][0] . '</div>';
		}
		// Elektr. Seitenspiegel
		if (!empty($meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ELECTRIC_EXTERIOR_MIRRORS'][0] . '</div>';
		}
		// Sitzheizung
		if (!empty($meta_values['ELECTRIC_HEATED_SEATS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ELECTRIC_HEATED_SEATS'][0] . '</div>';
		}
		// Elektr. Fensterheber -->
		if (!empty($meta_values['ELECTRIC_WINDOWS'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ELECTRIC_WINDOWS'][0] . '</div>';
		}
		// Lichtsensor
		if (!empty($meta_values['LIGHT_SENSOR'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['LIGHT_SENSOR'][0] . '</div>';
		}
		// Multifunktionslenkrad
		if (!empty($meta_values['MULTIFUNCTIONAL_WHEEL'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['MULTIFUNCTIONAL_WHEEL'][0] . '</div>';
		}
		// Panorama-Dach
		if (!empty($meta_values['PANORAMIC_GLASS_ROOF'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['PANORAMIC_GLASS_ROOF'][0] . '</div>';
		}
		// Start/Stopp-Automatik
		if (!empty($meta_values['START_STOP_SYSTEM'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['START_STOP_SYSTEM'][0] . '</div>';
		}
		// Schiebedach
		if (!empty($meta_values['SUNROOF'][0])) {
			$content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['SUNROOF'][0] . '</div>';
		}
        $content .= '</div><br><hr>';
        } // If Komfort
        if(!empty($meta_values['BLUETOOTH'][0]) || !empty($meta_values['CD_MULTICHANGER'][0]) || !empty($meta_values['HANDS_FREE_PHONE_SYSTEM'][0]) || !empty($meta_values['HEAD_UP_DISPLAY'][0]) || !empty($meta_values['MP3_INTERFACE'][0]) || !empty($meta_values['NAVIGATION_SYSTEM'][0]) || !empty($meta_values['ON_BOARD_COMPUTER'][0]) || !empty($meta_values['TUNER'][0])) {
        $content .= '<div class="row"><div class="col-xs-12"><h4>Multimedia:</h4></div>';
        if (!empty($meta_values['BLUETOOTH'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['BLUETOOTH'][0] . '</div>';
        }
        if (!empty($meta_values['CD_MULTICHANGER'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['CD_MULTICHANGER'][0] . '</div>';
        }
        if (!empty($meta_values['HANDS_FREE_PHONE_SYSTEM'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['HANDS_FREE_PHONE_SYSTEM'][0] . '</div>';
        }
        if (!empty($meta_values['HEAD_UP_DISPLAY'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['HEAD_UP_DISPLAY'][0] . '</div>';
        }
        if (!empty($meta_values['MP3_INTERFACE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['MP3_INTERFACE'][0] . '</div>';
        }
        if (!empty($meta_values['NAVIGATION_SYSTEM'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['NAVIGATION_SYSTEM'][0] . '</div>';
        }
        if (!empty($meta_values['ON_BOARD_COMPUTER'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ON_BOARD_COMPUTER'][0] . '</div>';
        }
        if (!empty($meta_values['TUNER'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['TUNER'][0] . '</div>';
        }
        $content .= '</div><br><hr>';
        } // If Multimedia
        if(!empty($meta_values['ALLOY_WHEELS'][0]) || !empty($meta_values['FULL_SERVICE_HISTORY'][0]) || !empty($meta_values['BIODIESEL_SUITABLE'][0]) || !empty($meta_values['E10_ENABLED'][0]) || !empty($meta_values['HU_AU_NEU'][0]) || !empty($meta_values['FOUR_WHEEL_DRIVE'][0]) || !empty($meta_values['HYBRID_PLUGIN'][0]) || !empty($meta_values['METALLIC'][0]) || !empty($meta_values['NONSMOKER_VEHICLE'][0]) || !empty($meta_values['PARTICULATE_FILTER_DIESEL'][0]) || !empty($meta_values['PERFORMANCE_HANDLING_SYSTEM'][0]) || !empty($meta_values['ROOF_RAILS'][0]) || !empty($meta_values['SKI_BAG'][0]) || !empty($meta_values['SPORT_PACKAGE'][0]) || !empty($meta_values['SPORT_SEATS'][0]) || !empty($meta_values['TRAILER_COUPLING'][0]) || !empty($meta_values['VEGETABLEOILFUEL_SUITABLE'][0]) || !empty($meta_values['WARRANTY'][0])) {
        $content .= '<div class="row"><div class="col-xs-12"><h4>Extras:</h4></div>';
        if (!empty($meta_values['ALLOY_WHEELS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ALLOY_WHEELS'][0] . '</div>';
        }
        if (!empty($meta_values['FULL_SERVICE_HISTORY'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['FULL_SERVICE_HISTORY'][0] . '</div>';
        }
        if (!empty($meta_values['BIODIESEL_SUITABLE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['BIODIESEL_SUITABLE'][0] . '</div>';
        }
        if (!empty($meta_values['E10_ENABLED'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['E10_ENABLED'][0] . '</div>';
        }
        if (!empty($meta_values['HU_AU_NEU'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['HU_AU_NEU'][0] . '</div>';
        }
        if (!empty($meta_values['FOUR_WHEEL_DRIVE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['FOUR_WHEEL_DRIVE'][0] . '</div>';
        }
        if (!empty($meta_values['HYBRID_PLUGIN'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['HYBRID_PLUGIN'][0] . '</div>';
        }
        if (!empty($meta_values['METALLIC'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['METALLIC'][0] . '</div>';
        }
        if (!empty($meta_values['NONSMOKER_VEHICLE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['NONSMOKER_VEHICLE'][0] . '</div>';
        }
        if (!empty($meta_values['PARTICULATE_FILTER_DIESEL'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['PARTICULATE_FILTER_DIESEL'][0] . '</div>';
        }
         if (!empty($meta_values['PERFORMANCE_HANDLING_SYSTEM'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['PERFORMANCE_HANDLING_SYSTEM'][0] . '</div>';
        }
        if (!empty($meta_values['ROOF_RAILS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['ROOF_RAILS'][0] . '</div>';
        }
        if (!empty($meta_values['SKI_BAG'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['SKI_BAG'][0] . '</div>';
        }
        if (!empty($meta_values['SPORT_PACKAGE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['SPORT_PACKAGE'][0] . '</div>';
        }
        if (!empty($meta_values['SPORT_SEATS'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['SPORT_SEATS'][0] . '</div>';
        }
        if (!empty($meta_values['TRAILER_COUPLING'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['TRAILER_COUPLING'][0] . '</div>';
        }
        if (!empty($meta_values['VEGETABLEOILFUEL_SUITABLE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['VEGETABLEOILFUEL_SUITABLE'][0] . '</div>';
        }
        if (!empty($meta_values['WARRANTY'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['WARRANTY'][0] . '</div>';
        }
        $content .= '</div><br><hr>';
        } // If Extras
        if(!empty($meta_values['EXPORT'][0]) || !empty($meta_values['TAXI'][0]) || !empty($meta_values['DISABLED_ACCESSIBLE'][0])) {
        $content .= '<div class="row"><div class="col-xs-12"><h4>Sonstiges:</h4></div>';
        if (!empty($meta_values['EXPORT'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['EXPORT'][0] . '</div>';
        }
        if (!empty($meta_values['TAXI'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['TAXI'][0] . '</div>';
        }
        if (!empty($meta_values['DISABLED_ACCESSIBLE'][0])) {
            $content .= '<div class="col-xs-6 col-sm-3 top15 text-left"><span class="glyphicon glyphicon-chevron-right"></span> ' . $meta_values['DISABLED_ACCESSIBLE'][0] . '</div>';
        }
        $content .= '</div><br><hr>';
        } // If Sonstiges
        $content .= '</div>';
        if (!empty($meta_values['enriched_description'][0])) {
        if (!empty($meta_values['efficiency_class_image_url'][0])) {
        $content .= '<div class="col-xs-12 col-sm-8" itemprop="description"><h3>Beschreibung</h3>';
        } else {
        $content .= '<div class="col-xs-12" itemprop="description"><h3>Beschreibung</h3>';
        }
		$content .= $meta_values['enriched_description'][0];
        $content .= '</div>';
		}
        if (!empty($meta_values['efficiency_class_image_url'][0])) {
        $content .= '<div class="col-xs-12 col-sm-4">';
        $content .= '<hr class="visible-xs" />';
        $content .= '<h3>Energieeffizienzklasse</h3><img class="img-responsive" src="';
        $content .= $meta_values['efficiency_class_image_url'][0];
        $content .= '" />';
        $content .= '</div>';
        }
		$content .= '<div class="col-xs-12"><hr><p>* Weitere Informationen zum offiziellen Kraftstoffverbrauch und zu den offiziellen spezifischen CO2-Emissionen und gegebenenfalls zum Stromverbrauch neuer PKW können dem Leitfaden über den offiziellen Kraftstoffverbrauch, die offiziellen spezifischen CO2-Emissionen und den offiziellen Stromverbrauch neuer PKW entnommen werden, der an allen Verkaufsstellen und bei der Deutschen Automobil Treuhand GmbH unentgeltlich erhältlich ist unter <a href="http://www.dat.de/" target="_blank">www.dat.de</a>.</p></div></div></div>';
		}
	return $content;
}