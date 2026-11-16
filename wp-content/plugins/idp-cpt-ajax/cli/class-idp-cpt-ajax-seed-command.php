<?php
/**
 * WP-CLI: wp idp seed-events [--count=<n>]
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Seed sample events for testing filters.
 */
class IDP_CPT_AJAX_Seed_Command {

	/**
	 * Create sample events and event types.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<n>]
	 * : Number of events to create.
	 * ---
	 * default: 12
	 * ---
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {
		$count     = max( 1, (int) ( $assoc_args['count'] ?? 12 ) );
		$types     = array( 'Conference', 'Workshop', 'Meetup' );
		$locations = array( 'Kathmandu', 'Pokhara', 'Lalitpur', 'Online' );

		foreach ( $types as $type ) {
			if ( ! term_exists( $type, 'idp_event_type' ) ) {
				wp_insert_term( $type, 'idp_event_type' );
			}
		}

		for ( $i = 1; $i <= $count; $i++ ) {
			$type    = $types[ $i % count( $types ) ];
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'idp_event',
					'post_status'  => 'publish',
					'post_title'   => sprintf( '%s #%d', $type, $i ),
					'post_content' => 'Sample event content for testing.',
					'post_excerpt' => 'Sample event excerpt.',
				)
			);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( $post_id->get_error_message() );
				continue;
			}

			wp_set_object_terms( $post_id, $type, 'idp_event_type' );
			update_post_meta( $post_id, 'idp_event_date', gmdate( 'Y-m-d', strtotime( '+' . ( $i * 5 ) . ' days' ) ) );
			update_post_meta( $post_id, 'idp_event_location', $locations[ $i % count( $locations ) ] );
			update_post_meta( $post_id, 'idp_event_capacity', 20 * $i );
		}

		WP_CLI::success( "Created {$count} events." );
	}
}

WP_CLI::add_command( 'idp seed-events', 'IDP_CPT_AJAX_Seed_Command' );
