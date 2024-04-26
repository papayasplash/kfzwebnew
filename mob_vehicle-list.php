<?php

// Verwenden von get_shortcode_atts_array() für eine sicherere und klarere Handhabung der Attribute
$defaults = array(
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
);

$atts = shortcode_atts($defaults, $atts);

$args = array(
    'post_type' => 'fahrzeuge',
    'meta_key' => $atts['meta_key'],
    'orderby' => $atts['orderby'],
    'order' => $atts['order'],
    'posts_per_page' => $atts['posts_per_page'],
    'marke' => $atts['marke'],
    'modell' => $atts['modell'],
    'zustand' => $atts['zustand'],
    'kraftstoffart' => $atts['kraftstoffart'],
    'getriebe' => $atts['getriebe'],
    'standort' => $atts['standort'],
);

$vehicles = new WP_Query($args);

if ($vehicles->have_posts()) : ?>
    <div class="facetwp-template row">
        <?php while ($vehicles->have_posts()) : $vehicles->the_post();
            $meta_values = get_post_meta(get_the_ID());
            ?>
            <article id="post-<?php the_ID(); ?>" class="vehicle">
                <!-- Vereinfachte Darstellung der Fahrzeugdaten -->
                <?php include 'vehicle-display.php'; ?>
            </article>
        <?php endwhile; ?>
    </div>
    <p>* Weitere Informationen zum offiziellen Kraftstoffverbrauch und zu den offiziellen spezifischen CO2-Emissionen und gegebenenfalls zum Stromverbrauch neuer PKW können dem Leitfaden über den offiziellen Kraftstoffverbrauch, die offiziellen spezifischen CO2-Emissionen und den offiziellen Stromverbrauch neuer PKW entnommen werden, der an allen Verkaufsstellen und bei der 'Deutschen Automobil Treuhand GmbH' unentgeltlich erhältlich ist unter www.dat.de.</p>
<?php endif; wp_reset_postdata(); ?>