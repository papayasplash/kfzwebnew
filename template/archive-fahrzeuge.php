<?php 
/*
Template Name: Fahrzeuge
*/
get_header(); ?>
<div id="content" class="content" role="main">
        <div class="archive-header">
            <h1 class="archive-title">
               <?php post_type_archive_title(); ?>
            </h1>
        </div>

        <!-- .archive-header -->
        <div class="archive-listing">
            <ul>

                <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); 
                        $meta_values = get_post_meta( get_the_ID() );
                ?>
                <li>

                    <div class="item" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <h3>
                            <a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        <div class="entry-content">
                           <div class="archiveImage"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php if ( function_exists( 'has_post_thumbnail') && has_post_thumbnail() ) { the_post_thumbnail( 'thumbnail'); } ?>
                             </a></div>
<!-- Hier können Sie die anzuzeigenden Werte auswählen -->
<div class="preiscontainer">
<span class="uebersichtpreis"><?php if(!empty($meta_values['price'][0])){?><?php echo $meta_values['price'][0] . ' '. $meta_values['currency'][0];?><?php } ?></span></br>
<span class="mwst"><?php if(!empty($meta_values['vatable'][0])){?><?php echo $meta_values['vatable'][0]=='false'?'Mehrwertsteuer nicht ausweisbar':'Inkl. 19% USt.';?><?php } ?></span></div>
<div class="fzgliste">



		<!-- Notiz wenn Beschädigt! --><?php if(!empty($meta_values['damageRepaired'][0])){?><p><?php echo $meta_values['damageRepaired'][0]=='false'?'':'Beschädigtes Fahrzeug!';?></p><?php } ?>
		<!-- Notiz wenn nicht fahrbereit! --><?php if(!empty($meta_values['roadWorthy'][0])){?><p><?php echo $meta_values['roadWorthy'][0]=='false'?'Fahrzeug nicht fahrbereit!':'';?></p><?php } ?>
		<!-- Verkäufer Standort --><!-- <span class="seller"><label>Standort: </label><?php echo $meta_values['seller'][0]; ?><br></span> -->
		<!-- Erstzulassung (MM.JJJJ) --><?php if(!empty($meta_values['firstRegistration'][0])){?><p><label>EZ: </label><?php $show_date = date('m.Y', strtotime($meta_values['firstRegistration'][0]));?><?php echo $show_date; ?></p><?php } ?>
		<!-- Kilometerstand --><br> <?php if(!empty($meta_values['mileage'][0])){?> <span><strong>Kilometerstand: </strong><?php echo $meta_values['mileage'][0];?></span><?php } ?>
		<!-- Nächste HU/AU -->          <?php if(!empty($meta_values['nextInspection'][0])){?> <p><label>HU / AU bis: </label><?php $show_date = date('m.Y', strtotime($meta_values['nextInspection'][0]));?><?php echo $show_date; ?></p><?php } ?>
		<!-- Getriebe --><?php if(!empty($meta_values['gearbox'][0])){?> <p><label>Getriebe: </label><?php echo $meta_values['gearbox'][0]; ?></p><?php } ?>
		<!-- Leistung (KW,PS) --><?php if(!empty($meta_values['power'][0])){?> <p><label>Leistung: </label><?php echo $meta_values['power'][0]; ?> kW<?php $zahl1 = $meta_values['power'][0] ; $zahl2 = 1.35962; ?><?php $multiplikation = $zahl1 * $zahl2; ?> / <?php echo round($multiplikation); ?> PS ,<?php } ?> <!-- Diesel, Benzin, etc. --><?php if(!empty($meta_values['fuel'][0])){?><?php echo $meta_values['fuel'][0]; ?></p><?php } ?>
		<!-- Verbrauch kombiniert --><?php if(!empty($meta_values['emissionFuelConsumption_Combined'][0])){?> <p><label>Kraftstoffverbr. komb. ca.: </label><?php echo $meta_values['emissionFuelConsumption_Combined'][0]. ' l/100km *';?></p><?php } ?>
		<!-- CO2 Emission kombiniert --><?php if(!empty($meta_values['emissionFuelConsumption_CO2'][0])){?><p><label>CO2 Emissionen komb. ca.:  </label><?php echo $meta_values['emissionFuelConsumption_CO2'][0]. ' g/km *';?><?php } ?></p>
        <!-- Stromverbrauch kombiniert --><?php if(!empty($meta_values['combinedPowerConsumption'][0])){?> <p><label>Stromverbrauch komb.*: ≈</label><?php echo $meta_values['combinedPowerConsumption'][0]. ' kwH/100km'?></p><?php } ?>
		<!-- Emissionsklasse --><br> <?php if(!empty($meta_values['emissionClass'][0])){?> <span><strong>Emissionsklasse: </strong><?php echo $meta_values['emissionClass'][0];?></span><?php } ?>
	<!---
		<!-- Energieeffizienzklasse --><br> <?php if(!empty($meta_values['efficiency_class'][0])){?> <span><strong>Energieeffizienzklasse: </strong><?php echo $meta_values['efficiency_class'][0];?></span><?php } ?>
    -->


</div>
<div class="summary">
<!-- Hier kann die Beschreibung eingeblendet werden <?php the_content(); ?> -->
</div>                            
<!-- Hier kann die Kategorie eingeblendet werden  <p>Kategorie: <?php the_category(' '); ?></p> -->
                        </div>
                    </div>
                    <!-- post -->
                </li>

                <?php endwhile; ?>
        <?php else: ?>
        <p>Keine Fahrzeuge gefunden.</p>
        <?php endif; ?>
            </ul>
        </div>
        <p>* Weitere Informationen zum offiziellen Kraftstoffverbrauch und zu den offiziellen spezifischen CO2-Emissionen und gegebenenfalls zum Stromverbrauch neuer PKW können dem Leitfaden über den offiziellen Kraftstoffverbrauch, die offiziellen spezifischen CO2-Emissionen und den offiziellen Stromverbrauch neuer PKW' entnommen werden, der an allen Verkaufsstellen und bei der 'Deutschen Automobil Treuhand GmbH' unentgeltlich erhältlich ist unter <a href="http://www.dat.de" target="_blank">www.dat.de</a>.</p>
        <div class="navigation">
            <div class="alignleft">
                <?php next_posts_link( 'Previous entries') ?>
            </div>
            <div class="alignright">
                <?php previous_posts_link( 'Next entries') ?>
            </div>
        </div>
    </div>
    <!-- #content -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>