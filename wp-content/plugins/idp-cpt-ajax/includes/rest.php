<?php
/**
 * REST endpoint for Event filtering: GET /wp-json/idp/v1/events
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the REST route.
 *
 * @return void
 */
function idp_cpt_ajax_register_rest_routes() {
	register_rest_route(
		'idp/v1',
		'/events',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'idp_cpt_ajax_rest_get_events',
			'permission_callback' => '__return_true', // Public, read-only data.
			'args'                => array(
				'search'    => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'type'      => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_title',
				),
				'date_from' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'idp_cpt_ajax_sanitize_date',
				),
				'page'      => array(
					'type'              => 'integer',
					'default'           => 1,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'idp_cpt_ajax_register_rest_routes' );

/**
 * REST callback.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function idp_cpt_ajax_rest_get_events( $request ) {
	$result = idp_cpt_ajax_query_events(
		array(
			'search'    => $request->get_param( 'search' ),
			'type'      => $request->get_param( 'type' ),
			'date_from' => $request->get_param( 'date_from' ),
			'page'      => $request->get_param( 'page' ),
		)
	);

	$response = new WP_REST_Response( $result );
	$response->header( 'X-WP-Total', $result['found'] );
	$response->header( 'X-WP-TotalPages', $result['max_pages'] );

	return $response;
}
