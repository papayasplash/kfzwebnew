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
        //print_r($meta_values);
        $content = '
        <div itemprop="itemOffered" itemscope itemtype="http://schema.org/Car"><div class="row"><div class="col-xs-12"><h2 class="text-left title" itemprop="name">' . get_the_title() . '</h2><h5 class="text-left" itemprop="category">' . $meta_values['category'][0] . ', ' . $meta_values['condition'][0] . '</h5></div></div><div class="row"><div class="col-xs-12 col-sm-7">';
        $options = get_option('MobileDE_option');
        if (isset($options['mob_slider_option']) && $options['mob_slider_option'] == 'yes') {
            $content .= '<div class="slider-main" style="overflow: hidden;">';
            if (!empty($meta_values['images_ebay'])) {
                $mob_images = $meta_values['images_ebay'];
                foreach ($mob_images as $mob_image) {
                    $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
                    $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
                    $content .= '<img src="' . $bigimage . '" />';
                }
            } else {
                more_fields(true); // Reset index.
                while (($more_pics = more_fields())) {
                    $content .= '<img src="' . $more_pics['file'] . '"/>';
                }
            }
            $content .= '</div><div class="slider-nav" style="overflow: hidden;">';
            if (!empty($meta_values['images_ebay'])) {
                $mob_images = $meta_values['images_ebay'];
                foreach ($mob_images as $mob_image) {
                    $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
                    $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
                    $content .= '<img src="' . $mob_image_ssl . '" />';
                }
            } else {
                more_fields(true); // Reset index.
                // Bilder form /wp-content/uploads
                while (($more_pics = more_fields())) {
                    $content .= '<img src="' . $more_pics['sizes']['thumbnail']['file'] . '"/>';
                }
            }
            $content .= '</div>';
        } else {
            $content .= '<img class="img-responsive" src="';
            if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
                $content .= get_the_post_thumbnail_url();
            }
            $content .= '" itemprop="image"/><div class="row">';
            if (!empty($meta_values['images_ebay'])) {
                $mob_images = $meta_values['images_ebay'];
                foreach ($mob_images as $mob_image) {
                    $mob_image_ssl = str_replace('http://', 'https://', $mob_image);
                    $bigimage = str_replace('27.JPG', '57.JPG', $mob_image_ssl);
                    $content .= '<div class="col-xs-4 col-sm-3 col-lg-2 top15"><a href="' . $bigimage . '"><img class="img img-responsive" src="' . $mob_image_ssl . '" /></a></div>';
                }
            } else {
                more_fields(true); // Reset index.
                // Bilder form /wp-content/uploads
                while (($more_pics = more_fields())) {
                    $content .= '<div class="col-xs-4 col-sm-3 col-lg-2 top15"><a href="' . $more_pics['file'] . '"><img class="img img-responsive" src="' . $more_pics['sizes']['thumbnail']['file'] . '"/></a></div>';
                }
            }   
            $content .= '</div>';
        }
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
        $content .= '</div></div></div><div class="col-xs-12 col-sm-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-leaf placative-info" aria-hidden="true"></span> Energie & Umwelt</h3></div><div class="panel-body">';
        if (!empty($meta_values['emissionFuelConsumption_Combined'][0])) {
            $content .= '<span>Verbrauch komb.*:</span> <strong> ≈' . $meta_values['emissionFuelConsumption_Combined'][0] . ' l/100km</strong><br>';
        }

        // WLTP
        if (!empty($meta_values['wltp-co2-emission-combined'][0])) {
            $content .= '<span>CO₂-Emissionen (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-co2-emission-combined'][0] . ' g/km</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-consumption-fuel-combined'][0])) {
            $content .= '<span>Verbrauch komb. (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-consumption-fuel-combined'][0] . ' l/100km</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-consumption-power-combined'][0])) {
            $content .= '<span>Stromverbrauch (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-consumption-power-combined'][0] . ' kWh/100km (kombiniert)</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-electric-range'][0])) {
            $content .= '<span>Elektrische Reichweite (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-electric-range'][0] . ' l/100km</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-consumption-fuel-combined-weighted'][0])) {
            $content .= '<span>Gewichteter kombinierter Kraftstoffverbrauch für Plug-in-Hybride (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-consumption-fuel-combined-weighted'][0] . ' l/100km</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-consumption-power-combined-weighted'][0])) {
            $content .= '<span>Gewichteter kombinierter Stromverbrauch für Plug-in-Hybride (WLTP)*:</span> <strong> ≈' . $meta_values['wltp-consumption-power-combined-weighted'][0] . ' kWh/100km</strong><br>';
        }
        // WLTP
        if (!empty($meta_values['wltp-co2-emission-combined-weighted'][0])) {
            $content .= '<span>Gewichtete Menge an Kohlendioxidemissionen für Plug-in-Hybride*:</span> <strong> ≈' . $meta_values['wltp-co2-emission-combined-weighted'][0] . ' g/km</strong><br>';
        }
        if (!empty($meta_values['emissionFuelConsumption_Inner'][0])) {
            $content .= '<span>Verbrauch innerorts*:</span> <strong> ≈' . $meta_values['emissionFuelConsumption_Inner'][0] . ' l/100km</strong><br>';
        }
        if (!empty($meta_values['emissionFuelConsumption_Outer'][0])) {
            $content .= '<span>Verbrauch außerorts*:</span> <strong> ≈' . $meta_values['emissionFuelConsumption_Outer'][0] . ' l/100km</strong><br>';
        }
        if (!empty($meta_values['emissionFuelConsumption_CO2'][0])) {
            $content .= '<span>CO2-Emissionen komb.*:</span> <strong> ≈' . $meta_values['emissionFuelConsumption_CO2'][0] . ' g/km</strong><br>';
        }
        if (!empty($meta_values['combinedPowerConsumption'][0])) {
            $content .= '<span>Stromverbrauch komb.*:</span> <strong> ≈' . $meta_values['combinedPowerConsumption'][0] . ' kwH/100km</strong><br>';
        }
        if (!empty($meta_values['emissionSticker'][0])) {
            $content .= '<span>Emissionsklasse:</span> <strong> ' . $meta_values['emissionSticker'][0] . '</strong>';
        }
        $content .= '</div></div></div><div class="col-xs-12"><hr></div><div class="col-xs-12 col-sm-6"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-camera placative-info" aria-hidden="true"></span> Optik</h3></div><div class="panel-body">';
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