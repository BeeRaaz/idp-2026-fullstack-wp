<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Event post type.
 *
 * @return void
 */
function idp_cpt_ajax_register_post_type() {
	$labels = array(
		'name'               => _x( 'Events', 'post type general name', 'idp-cpt-ajax' ),
		'singular_name'      => _x( 'Event', 'post type singular name', 'idp-cpt-ajax' ),
		'add_new_item'       => __( 'Add New Event', 'idp-cpt-ajax' ),
		'edit_item'          => __( 'Edit Event', 'idp-cpt-ajax' ),
		'new_item'           => __( 'New Event', 'idp-cpt-ajax' ),
		'view_item'          => __( 'View Event', 'idp-cpt-ajax' ),
		'search_items'       => __( 'Search Events', 'idp-cpt-ajax' ),
		'not_found'          => __( 'No events found.', 'idp-cpt-ajax' ),
		'not_found_in_trash' => __( 'No events found in Trash.', 'idp-cpt-ajax' ),
		'menu_name'          => __( 'Events', 'idp-cpt-ajax' ),
	);

	register_post_type(
		'idp_event',
		array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => 'events',
			'rewrite'      => array( 'slug' => 'events' ),
			'menu_icon'    => 'dashicons-calendar-alt',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		)
	);
}
add_action( 'init', 'idp_cpt_ajax_register_post_type' );

/**
 * Register the Event Type taxonomy.
 *
 * @return void
 */
function idp_cpt_ajax_register_taxonomy() {
	$labels = array(
		'name'          => _x( 'Event Types', 'taxonomy general name', 'idp-cpt-ajax' ),
		'singular_name' => _x( 'Event Type', 'taxonomy singular name', 'idp-cpt-ajax' ),
		'search_items'  => __( 'Search Event Types', 'idp-cpt-ajax' ),
		'all_items'     => __( 'All Event Types', 'idp-cpt-ajax' ),
		'edit_item'     => __( 'Edit Event Type', 'idp-cpt-ajax' ),
		'add_new_item'  => __( 'Add New Event Type', 'idp-cpt-ajax' ),
		'menu_name'     => __( 'Event Types', 'idp-cpt-ajax' ),
	);

	register_taxonomy(
		'idp_event_type',
		array( 'idp_event' ),
		array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'event-type' ),
		)
	);
}
add_action( 'init', 'idp_cpt_ajax_register_taxonomy' );
