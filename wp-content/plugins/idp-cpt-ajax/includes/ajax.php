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
 *     @type string $lang      Polylang language slug.
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
			'lang'      => '',
		)
	);

	$query_args = array(
		'post_type'      => 'idp_event',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'paged'          => max( 1, (int) $args['page'] ),
	);

	if ( '' !== $args['lang'] ) {
		$query_args['lang'] = $args['lang']; // Polylang query var.
	}

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
		// Date filter: only dated events can match.
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'idp_date' => array(
				'key'     => 'idp_event_date',
				'value'   => $args['date_from'],
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
	} else {
		// No date filter: include events without a date too (OR EXISTS / NOT EXISTS), undated last.
		$query_args['idp_undated_last'] = true;
		$query_args['meta_query']       = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			'idp_date' => array(
				'key'     => 'idp_event_date',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => 'idp_event_date',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$query_args['orderby'] = array(
		'idp_date' => 'ASC',
		'title'    => 'ASC',
	);

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
		'lang'      => isset( $_POST['lang'] ) ? idp_cpt_ajax_sanitize_lang( sanitize_key( wp_unslash( $_POST['lang'] ) ) ) : '',
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

/**
 * Put events without a date after dated ones (only for queries flagged idp_undated_last).
 *
 * @param string   $orderby SQL ORDER BY clause.
 * @param WP_Query $query   Query.
 * @return string
 */
function idp_cpt_ajax_orderby_undated_last( $orderby, $query ) {
	if ( ! $query->get( 'idp_undated_last' ) || empty( $query->meta_query ) ) {
		return $orderby;
	}

	$clauses = $query->meta_query->get_clauses();

	if ( empty( $clauses['idp_date']['alias'] ) ) {
		return $orderby;
	}

	global $wpdb;
	$alias = preg_replace( '/[^A-Za-z0-9_]/', '', $clauses['idp_date']['alias'] );

	return "CASE WHEN {$alias}.meta_value IS NULL THEN 1 ELSE 0 END ASC, {$alias}.meta_value ASC, {$wpdb->posts}.post_title ASC";
}
add_filter( 'posts_orderby', 'idp_cpt_ajax_orderby_undated_last', 10, 2 );

/**
 * Read and sanitize filter values from the URL (GET). Read-only, so no nonce needed.
 *
 * @return array{search:string,type:string,date_from:string}
 */
function idp_cpt_ajax_get_request_filters() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	return array(
		'search'    => isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '',
		'type'      => isset( $_GET['type'] ) ? sanitize_title( wp_unslash( $_GET['type'] ) ) : '',
		'date_from' => isset( $_GET['date_from'] ) ? idp_cpt_ajax_sanitize_date( sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) ) : '',
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
