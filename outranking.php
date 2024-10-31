<?php
/*
Plugin Name: Outranking
Description: Outranking Extension for WordPress
Version: 1.1.3
Author: Outranking LLC
Author URI: https://www.outranking.io/
License: GPLv2 or later
Text Domain: outranking
*/

/**
 * Defining Outranking Server URL to be used for fetching data
 * @since 1.0.0
 */
define('OUTRANKING_SERVER_URL', esc_url('https://apps.outranking.io'));
/**
 * Including file that handles metabox related tasks
 * @since 1.0.0
 */
include_once(__DIR__ . '/inc/metabox.php');
/**
 * Including files required to perform actions
 * @since 1.0.2
 */
include_once(__DIR__ . '/inc/core.php');
include_once(__DIR__ . '/inc/ajax.php');
/**
 * Included API file
 * @since 1.1.0
 */
include_once(__DIR__ . '/api/api.php');
/**
 * Registering Metabox in Post to display list of articles to import and export
 * @since 1.0.0
 */
add_action('add_meta_boxes', 'outranking_register_metabox');
function outranking_register_metabox()
{
	/**
	 * Added esc_url in plugins_url
	 * @since 1.0.1
	 */
	add_meta_box(
		'outranking-meta-box',
		__('<div class="outranking-meta-box-header">
			<img src="' . esc_url(plugins_url('./assets/images/outranking_dark_long.png', __FILE__)) . '" class="outranking-logo">
			</div>', 'outranking'),
		function () {
			OutrankingMetaBox::render();
		},
		null,
		'side',
		'default',
	);
}
/**
 * Adding Admin Script and Styles
 * @since 1.0.0
 */
add_action('admin_enqueue_scripts', 'outranking_admin_styles');
function outranking_admin_styles()
{
	wp_enqueue_style('outranking-admin-style', plugins_url('assets/css/outranking_admin.css', __FILE__));
	wp_enqueue_script('outranking-admin-script', plugins_url('assets/js/outranking_admin.js', __FILE__), array('jquery'), false, true);
}
/**
 * Registering actions for different actions performed using JS
 */
add_action("wp_ajax_outranking_save_token", "outranking_save_token");
add_action("wp_ajax_outranking_refresh_metabox", "outranking_refresh_metabox");
add_action('wp_ajax_outranking_import_article', 'outranking_import_article');
add_action('wp_ajax_outranking_export_article', 'outranking_export_article');
