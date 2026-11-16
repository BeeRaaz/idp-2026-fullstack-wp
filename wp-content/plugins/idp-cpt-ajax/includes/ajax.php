<?php
/**
 * Event query + admin-ajax filter endpoint.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query events with the given (already sanitized) filters and render cards.
 *
 * @param array $args {
 *     Filters.
 *
 *     @type string $search    Search keyword.
 *     @type string $type      Event type term slug.
 *     @type string $date_from Y-m-d lower bound.
 *     @type int    $page      Page number.
 * }
 * @return array{html:string,max_pages:int,found:int}
 */
function idp_cpt_ajax_query_events( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'search'    => '',
			'type'      => '',
			'date_from' => '',
			'page'      => 1,
		)
	);

	$query_args = array(
		'post_type'      => 'idp_event',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'paged'          => max( 1, (int) $args['page'] ),
		'meta_key'       => 'idp_event_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	);

	if ( '' !== $args['search'] ) {
		$query_args['s'] = $args['search'];
	}

	if ( '' !== $args['type'] ) {
		$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'idp_event_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			),
		);
	}

	if ( '' !== $args['date_from'] ) {
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'idp_event_date',
				'value'   => $args['date_from'],
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
	}

	$query = new WP_Query( $query_args );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			include IDP_CPT_AJAX_DIR . 'templates/parts/event-card.php';
		}
	} elseif ( (int) $args['page'] <= 1 ) {
		echo '<p class="idp-events-empty">' . esc_html__( 'No events match your filters.', 'idp-cpt-ajax' ) . '</p>';
	}
	wp_reset_postdata();

	return array(
		'html'      => ob_get_clean(),
		'max_pages' => (int) $query->max_num_pages,
		'found'     => (int) $query->found_posts,
	);
}

/**
 * AJAX handler (logged-in and anonymous visitors).
 *
 * @return void
 */
function idp_cpt_ajax_handle_filter() {
	check_ajax_referer( 'idp_filter_events', 'nonce' );

	$filters = array(
		'search'    => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
		'type'      => isset( $_POST['type'] ) ? sanitize_title( wp_unslash( $_POST['type'] ) ) : '',
		'date_from' => isset( $_POST['date_from'] ) ? idp_cpt_ajax_sanitize_date( sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) ) : '',
		'page'      => isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1,
	);

	wp_send_json_success( idp_cpt_ajax_query_events( $filters ) );
}
add_action( 'wp_ajax_idp_filter_events', 'idp_cpt_ajax_handle_filter' );
add_action( 'wp_ajax_nopriv_idp_filter_events', 'idp_cpt_ajax_handle_filter' );

/**
 * Enqueue front-end assets on Event archives.
 *
 * @return void
 */
function idp_cpt_ajax_enqueue_assets() {
	if ( ! is_post_type_archive( 'idp_event' ) && ! is_tax( 'idp_event_type' ) ) {
		return;
	}

	wp_enqueue_style( 'idp-events', IDP_CPT_AJAX_URL . 'assets/css/events.css', array(), IDP_CPT_AJAX_VERSION );
	wp_enqueue_script( 'idp-events-filter', IDP_CPT_AJAX_URL . 'assets/js/events-filter.js', array(), IDP_CPT_AJAX_VERSION, true );
	wp_localize_script(
		'idp-events-filter',
		'idpEvents',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'idp_filter_events' ),
			'restUrl' => esc_url_raw( rest_url( 'idp/v1/events' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'idp_cpt_ajax_enqueue_assets' );
