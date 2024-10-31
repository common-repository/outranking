<?php

/**
 * This file holds the code for the API to be called from Outranking Platform to create, update or selective-update the content of any page or post of the website
 * @since 1.1.0
 */

/**
 * A function to authenticate the api calls using Bearer token
 * @since 1.1.0
 */
function outranking_api_check_authentication($request)
{
    $api_key_option = get_option('outranking_api_key'); // Fetch your stored API key option

    $token = $request->get_header('Authorization');
    /**
     * Validate the token and provide true/false based on the authentication status
     * @since 1.1.0
     */
    if (!$token || ($token !== 'Bearer ' . $api_key_option)) {
        return false;
    }
    return true;
}
/**
 * Adding an action to initialize the endpoint for creating a post
 * @since 1.1.0
 */
add_action('rest_api_init', 'outanking_create_post_init');
function outanking_create_post_init()
{
    register_rest_route('outranking/v1', '/create-post', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . 'outanking_create_post',
        'permission_callback' => __NAMESPACE__ . 'outranking_api_check_authentication',
    ));
}

/**
 * The main function to implement the logic of creating a post
 * @since 1.1.0
 */
function outanking_create_post($request)
{
    $data = $request->get_json_params();
    /**
     * Validating all the JSON inputs
     * @since 1.1.0
     */
    if (empty($data['title']) || empty($data['content'])) {
        return new WP_Error('empty_data', 'Title and Content fields are required.', array('status' => 400));
    }
    if (empty($data['description'])) {
        $data['description'] = '';
    }

    /**
     * Verifying categories, check if the category already exists then assign the id otherwise create the category and assign a new id
     * @since 1.1.0
     */
    $category_names = isset($data['categories']) ? $data['categories'] : array();

    $category_ids = array();
    foreach ($category_names as $category_name) {
        $category_id = get_cat_ID($category_name);

        if (!$category_id) {
            $new_category = wp_insert_term($category_name, 'category');
            if (!is_wp_error($new_category)) {
                $category_id = $new_category['term_id'];
            }
        }

        if ($category_id) {
            $category_ids[] = $category_id;
        }
    }

    /**
     * Creating the post
     * @since 1.1.0
     */
    $post_data = array(
        'post_title' => sanitize_text_field($data['title']),
        'post_content' => outranking_content_filter($data['content']),
        'post_excerpt' => sanitize_text_field($data['description']),
        'post_status' => $data['status'] ?? 'draft',
        'post_type' => 'post',
        'post_category' => $category_ids,
    );
    if (isset($data['slug'])) {
        $post_data['post_name'] = $data['slug'];
    }
    remove_filter('content_save_pre', 'wp_filter_post_kses');
    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    $new_post_id = wp_insert_post($post_data);
    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    $new_post_url = get_permalink($new_post_id);
    /**
     * Returning Response
     * @since 1.1.0
     */
    if (is_wp_error($new_post_id)) {
        return new WP_Error('post_creation_failed', 'Failed to create new post.', array('status' => 500));
    }

    return array('message' => 'Post created successfully.', 'post_id' => $new_post_id, 'url' => $new_post_url);
}

/**
 * Adding an action to initialize the endpoint to fully update a post 
 * @since 1.1.0
 */
add_action('rest_api_init', 'outanking_update_full_post_init');

function outanking_update_full_post_init()
{
    register_rest_route('outranking/v1', '/update-post', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . 'outanking_update_full_post',
        'permission_callback' => __NAMESPACE__ . 'outranking_api_check_authentication',
    ));
}

/**
 * The main function to implement the logic of updating a post
 * @since 1.1.0
 */
function outanking_update_full_post($request)
{
    $data = $request->get_json_params();

    /**
     * Validating all the JSON inputs
     * @since 1.1.0
     */
    if (empty($data['url']) || empty($data['title']) || empty($data['description']) || empty($data['content'])) {
        return new WP_Error('empty_data', 'All fields are required.', array('status' => 400));
    }

    /**
     * Retriving the ID of the post based on url
     * @since 1.1.0
     */
    $post_id = url_to_postid($data['url']);

    /**
     * Returning error in case the url is invalid or ID not found
     * @since 1.1.0
     */
    if (!$post_id) {
        return new WP_Error('invalid_url', 'Invalid URL provided.', array('status' => 400));
    }

    /**
     * Updating the post based on inputs received 
     * @since 1.1.0
     */
    $post_data = array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($data['title']),
        'post_content' => outranking_content_filter($data['content']),
        'post_excerpt' => sanitize_text_field($data['description']),
    );
    if (isset($data['status'])) {
        $post_data['post_status'] = $data['status'];
    }
    remove_filter('content_save_pre', 'wp_filter_post_kses');
    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    wp_update_post($post_data);
    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

    /**
     * Returning Response
     * @since 1.1.0
     */
    return array('message' => 'Post updated successfully.');
}

/**
 * Adding an action to initialize the endpoint for selectively update a post
 * @since 1.1.0
 */
add_action('rest_api_init', 'outanking_selective_update_post_init');
function outanking_selective_update_post_init()
{
    register_rest_route('outranking/v1', '/selective-update', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . 'outanking_selective_update',
        'args' => array(),
        // 'permission_callback' => __NAMESPACE__ . 'outranking_api_check_authentication',
        'permission_callback' => function () {
            return true;
        }
    ));
}
/**
 * The main function to implement the logic of selective update of a post
 * @since 1.1.0
 */
function outanking_selective_update($request)
{
    $data = $request->get_json_params();

    /**
     * Validating all the JSON inputs
     * @since 1.1.0
     */
    if (empty($data['url']) || empty($data['find_text']) || empty($data['replace_text'])) {
        return new WP_Error('empty_data', 'All fields are required.', array('status' => 400));
    }

    /**
     * Retriving the ID of the post based on url
     * @since 1.1.0
     */
    $post_id = url_to_postid($data['url']);

    /**
     * Returning error in case the url is invalid or ID not found
     * @since 1.1.0
     */
    if (!$post_id) {
        return new WP_Error('invalid_url', 'Invalid URL provided.', array('status' => 400));
    }

    /**
     * Getting the post content to find the string and replace it with a new string
     * @since 1.1.0
     */
    $post_content = html_entity_decode(apply_filters('the_content', wptexturize(get_post_field('post_content', $post_id))));
    // print_r($post_content);
    /**
     * Check if find text exists in the content 
     * @since 1.1.0
     */
    $find_text_formatted = html_entity_decode(wptexturize($data['find_text']));
    if (!stripos($post_content, $find_text_formatted)) {
        return new WP_Error('invalid_find_text', 'Provided find_text is not present in the content', array('status' => 400));
    }
    /**
     * Replace the "find_text" with "replace_text" passed in JSON
     * @since 1.1.0
     */
    $replace_text_formatted = html_entity_decode(wptexturize($data['replace_text']));
    $new_content = str_replace($find_text_formatted, $replace_text_formatted, $post_content);

    /**
     * Check if content is replaced 
     * @since 1.1.0
     */
    if (!stripos($new_content, $replace_text_formatted)) {
        return new WP_Error('unable_to_replace', 'Unable to replace the provided text', array('status' => 500));
    }
    /**
     * Finally, updating the post
     * @since 1.1.0
     */
    $post_data = array(
        'ID' => $post_id,
        'post_content' => $new_content,
    );
    wp_update_post($post_data);

    /**
     * Returning Response
     * @since 1.1.0
     */
    return array('message' => 'Text replaced successfully.');
}

/**
 * Adding an API to check if the plugin is installed or not
 * @since 1.1.1
 */
add_action('rest_api_init', 'outranking_is_plugin_installed_init');
function outranking_is_plugin_installed_init()
{
    register_rest_route('outranking/v1', '/is-plugin-installed', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . 'outranking_is_plugin_installed',
        'permission_callback' => function () {
            return true;
        }
    ));
}

function outranking_is_plugin_installed()
{
    return array('ranking' => true);
}

/**
 * Adding an API to check if the URL already exists or not
 * @since 1.1.1
 */
add_action('rest_api_init', 'outranking_is_url_exists_init');
function outranking_is_url_exists_init()
{
    register_rest_route('outranking/v1', '/is-url-exists', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . 'outranking_is_url_exists',
        'permission_callback' => function () {
            return true;
        }
    ));
}

function outranking_is_url_exists($request)
{

    $data = $request->get_query_params();
    $query_posts = new WP_Query(array('name' => $data['slug'], 'post_type' => 'post', 'fields' => 'ids'));
    if ($query_posts->have_posts()) {
        return array('exists' => true);
    } else {
        return array('exists' => false);
    }
}
/**
 * Adding an API to check the post type from URL
 * @since 1.1.3
 */
add_action('rest_api_init', 'outranking_url_types_init');
function outranking_url_types_init()
{
    register_rest_route('outranking/v1', '/url-types', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . 'outranking_url_types',
        'permission_callback' => function () {
            return true;
        }
    ));
}

function outranking_url_types($request)
{

    $data = $request->get_json_params();
    $types = array();
    if (!isset($data['urls']) || count($data['urls']) === 0) {
        return array('message' => 'URLs are required');
    }
    foreach ($data['urls'] as $url) {
        $slug = str_replace(site_url() . '/', '', $url);

        $post_id = get_page_by_path($slug, 'ARRAY_A', ['post', 'page']);

        if ($post_id === null) {
            $types[] = array('url' => $url, 'exists' => false);
        } else {
            $types[] = array('url' => $url, 'exists' => true, 'type' => $post_id['post_type']);
        }
    }
    return array('urls' => $types);
}


add_action('rest_api_init', 'outranking_search_endpoint_init');
function outranking_search_endpoint_init()
{
    register_rest_route('outranking/v1', '/search-keyword', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . 'outranking_search_keyword',
        'args' => array(
            'search_query' => array(
                'required' => true,
                'type' => 'string',
            ),
        ),
        'permission_callback' => __NAMESPACE__ . 'outranking_api_check_authentication',
    ));
}

function outranking_search_keyword($request)
{
    $search_query = $request->get_param('search_query');

    $matching_urls = array();

    // Split the search query into individual terms.
    $search_terms = explode(' ', $search_query);

    // Create a new WP_Query to search for posts and pages.
    $args = array(
        's' => $search_query, // Use the full search query
        'post_type' => array('post', 'page'), // Search in both posts and pages
    );
    $query = new WP_Query($args);

    // Check if there are matching posts/pages.
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $url = get_permalink();
            $post_type = get_post_type();
            $matching_urls[] = array('url' => $url, 'type' => $post_type);
        }
        wp_reset_postdata();
    }

    return array('urls' => $matching_urls);
}
