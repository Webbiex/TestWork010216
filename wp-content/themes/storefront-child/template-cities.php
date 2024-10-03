<?php
/*
Template Name: Cities Table
*/
get_header();
global $wpdb;

// Fire the custom action hook before the table
do_action('before_cities_table');
?>

<!-- Search input -->
<input type="text" id="city-search" placeholder="Search cities...">
<div id="search-results"></div>

<?php

// Query the cities and associated countries
$cities_data = $wpdb->get_results("
    SELECT p.ID, p.post_title AS city_name, t.name AS country_name, 
           pm1.meta_value AS latitude, pm2.meta_value AS longitude, 
           pm3.meta_value AS temperature
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
    LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
    LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
    LEFT JOIN {$wpdb->postmeta} pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_city_latitude')
    LEFT JOIN {$wpdb->postmeta} pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_city_longitude')
    LEFT JOIN {$wpdb->postmeta} pm3 ON (p.ID = pm3.post_id AND pm3.meta_key = '_city_temperature') 
    WHERE p.post_type = 'cities' AND p.post_status = 'publish'
");


if ($cities_data) {
    echo '<table>';
    echo '<tr><th>Country</th><th>City</th><th>Latitude</th><th>Longitude</th><th>Temperature</th></tr>'; 
    foreach ($cities_data as $city) {
        echo '<tr>';
        echo '<td>' . esc_html($city->country_name) . '</td>';
        echo '<td>' . esc_html($city->city_name) . '</td>';
        echo '<td>' . esc_html($city->latitude) . '</td>';
        echo '<td>' . esc_html($city->longitude) . '</td>';
        echo '<td>' . esc_html($city->temperature) . '</td>'; 
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo 'No cities found.';
}


// Fire the custom action hook after the table
do_action('after_cities_table');

get_footer();
?>
