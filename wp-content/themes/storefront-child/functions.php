<?php
// Enqueue parent theme styles
function storefront_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

function enqueue_custom_scripts() {
    // Enqueue AJAX search script
    wp_enqueue_script('ajax-search', get_stylesheet_directory_uri() . '/js/ajax-search.js', array('jquery'), null, true);
    
    // Enqueue cities table script
    wp_enqueue_script('cities-table-script', get_stylesheet_directory_uri() . '/cities-table.js', array('jquery'), null, true);
    
    // Localize script to make admin-ajax.php URL available in JavaScript
    wp_localize_script('ajax-search', 'ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// Create the Custom Post Type 
function register_cities_post_type() {
    $labels = array(
        'name'               => _x('Cities', 'post type general name'),
        'singular_name'      => _x('City', 'post type singular name'),
        'menu_name'          => _x('Cities', 'admin menu'),
        'name_admin_bar'     => _x('City', 'add new on admin bar'),
        'add_new'            => _x('Add New', 'city'),
        'add_new_item'       => __('Add New City'),
        'new_item'           => __('New City'),
        'edit_item'          => __('Edit City'),
        'view_item'          => __('View City'),
        'all_items'          => __('All Cities'),
        'search_items'       => __('Search Cities'),
        'parent_item_colon'  => __('Parent Cities:'),
        'not_found'          => __('No cities found.'),
        'not_found_in_trash' => __('No cities found in Trash.')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'city'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail'), // Add 'editor' if needed
        'taxonomies'         => array('country') // 'country' taxonomy 
    );

    register_post_type('cities', $args);
}
add_action('init', 'register_cities_post_type');



// Meta Box with Latitude, Longitude and temperature Fields
function cities_meta_box() {
    add_meta_box(
        'cities_location',
        __('City Location', 'textdomain'),
        'cities_location_callback',
        'cities'
    );
}
add_action('add_meta_boxes', 'cities_meta_box');

function cities_location_callback($post) {
    // Retrieve current latitude, Longitude and temperature Fields
    $latitude = get_post_meta($post->ID, '_city_latitude', true);
    $longitude = get_post_meta($post->ID, '_city_longitude', true);
    $temperature = get_post_meta($post->ID, '_city_temperature', true);
    ?>
    <label for="city_latitude">Latitude:</label>
    <input type="text" name="city_latitude" value="<?php echo esc_attr($latitude); ?>" /><br>
    
    <label for="city_longitude">Longitude:</label>
    <input type="text" name="city_longitude" value="<?php echo esc_attr($longitude); ?>" /><br/>

    <label for="city_temperature">Temperature</label>
    <input type="text" name="city_temperature" value="<?php echo esc_attr($temperature); ?>" />
    <?php
}

function save_cities_meta_box_data($post_id) {
    if (array_key_exists('city_latitude', $_POST)) {
        update_post_meta($post_id, '_city_latitude', sanitize_text_field($_POST['city_latitude']));
    }
    if (array_key_exists('city_longitude', $_POST)) {
        update_post_meta($post_id, '_city_longitude', sanitize_text_field($_POST['city_longitude']));
    }
    if (isset($_POST['city_temperature'])) {
        update_post_meta($post_id, '_city_temperature', sanitize_text_field($_POST['city_temperature']));
    }
}
add_action('save_post', 'save_cities_meta_box_data');

// Custom taxonomy for countries
function register_countries_taxonomy() {
    $labels = array(
        'name'              => _x('Countries', 'taxonomy general name'),
        'singular_name'     => _x('Country', 'taxonomy singular name'),
        'search_items'      => __('Search Countries'),
        'all_items'         => __('All Countries'),
        'parent_item'       => __('Parent Country'),
        'parent_item_colon' => __('Parent Country:'),
        'edit_item'         => __('Edit Country'),
        'update_item'       => __('Update Country'),
        'add_new_item'      => __('Add New Country'),
        'new_item_name'     => __('New Country Name'),
        'menu_name'         => __('Countries'),
    );

    $args = array(
        'hierarchical'      => true,  // This makes it behave like categories (hierarchical)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'country'),
        'capabilities'      => array(
            'manage_terms' => 'manage_categories',  // Use built-in 'manage_categories' permission for managing categories
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ),
    );

    register_taxonomy('country', array('cities'), $args);
}
add_action('init', 'register_countries_taxonomy');



// AJAX Search
function search_cities() {
    global $wpdb;
    
    // Get the search query from the request
    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // Query to search cities by title and get temperature data
    $cities_data = $wpdb->get_results($wpdb->prepare("
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
        AND p.post_title LIKE %s
    ", '%' . $wpdb->esc_like($search_query) . '%'));

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
    
    wp_die(); // This is required to terminate the request properly.
}

// Hook the search function to WordPress AJAX
add_action('wp_ajax_search_cities', 'search_cities');
add_action('wp_ajax_nopriv_search_cities', 'search_cities');


// Hook function to add content before the cities table
function custom_content_before_table() {
    echo '<p>This content is added before the cities table.</p>';
}
add_action('before_cities_table', 'custom_content_before_table');

// Hook function to add content after the cities table
function custom_content_after_table() {
    echo '<p>This content is added after the cities table.</p>';
}
add_action('after_cities_table', 'custom_content_after_table');
