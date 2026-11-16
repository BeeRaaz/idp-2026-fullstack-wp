<?php
/**
 * WP-CLI: wp idp seed-events, wp idp translate-events
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Seed and translate sample events.
 */
class IDP_CPT_AJAX_Seed_Command {

	/**
	 * Event type names (English => Nepali).
	 *
	 * @var string[]
	 */
	private $type_names = array(
		'Conference' => 'सम्मेलन',
		'Workshop'   => 'कार्यशाला',
		'Meetup'     => 'भेटघाट',
	);

	/**
	 * Locations (English => Nepali).
	 *
	 * @var string[]
	 */
	private $locations = array(
		'Kathmandu' => 'काठमाडौँ',
		'Pokhara'   => 'पोखरा',
		'Lalitpur'  => 'ललितपुर',
		'Online'    => 'अनलाइन',
	);

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
	 * [--translate]
	 * : Also create linked Nepali copies (requires Polylang).
	 *
	 * [--lang=<slug>]
	 * : Polylang slug of the target language when using --translate.
	 * ---
	 * default: ne
	 * ---
	 *
	 * @subcommand seed-events
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function seed_events( $args, $assoc_args ) {
		$count     = max( 1, (int) ( $assoc_args['count'] ?? 12 ) );
		$types     = array_keys( $this->type_names );
		$locations = array_keys( $this->locations );
		$translate = ! empty( $assoc_args['translate'] );
		$lang      = $assoc_args['lang'] ?? 'ne';

		if ( $translate ) {
			$this->assert_polylang( $lang );
		}

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

		if ( $translate ) {
			$this->translate_events( array(), array( 'lang' => $lang ) );
		}
	}

	/**
	 * Create linked translations (default Nepali) for existing events that lack one.
	 *
	 * Also assigns the default language to events and event types that have no language yet.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<slug>]
	 * : Polylang slug of the target language.
	 * ---
	 * default: ne
	 * ---
	 *
	 * @subcommand translate-events
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function translate_events( $args, $assoc_args ) {
		$lang = $assoc_args['lang'] ?? 'ne';
		$this->assert_polylang( $lang );

		$default_lang = pll_default_language();
		$term_ids     = $this->translate_terms( $default_lang, $lang );

		$events = get_posts(
			array(
				'post_type'        => 'idp_event',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'suppress_filters' => true,
			)
		);

		$created = 0;

		foreach ( $events as $event ) {
			$event_lang = pll_get_post_language( $event->ID );

			if ( ! $event_lang ) {
				pll_set_post_language( $event->ID, $default_lang );
				$event_lang = $default_lang;
			}

			// Only translate source-language events that have no translation yet.
			if ( $event_lang !== $default_lang || pll_get_post( $event->ID, $lang ) ) {
				continue;
			}

			$type_name  = '';
			$type_terms = wp_get_object_terms( $event->ID, 'idp_event_type', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $type_terms ) && ! empty( $type_terms ) ) {
				$type_name = $type_terms[0];
			}

			$ne_type  = $this->type_names[ $type_name ] ?? $type_name;
			$location = (string) get_post_meta( $event->ID, 'idp_event_location', true );

			// Reuse the number from "Type #N" so titles stay recognizable.
			$suffix = preg_match( '/#\d+$/', $event->post_title, $matches ) ? ' ' . $matches[0] : '';

			$new_id = wp_insert_post(
				array(
					'post_type'    => 'idp_event',
					'post_status'  => $event->post_status,
					'post_title'   => $ne_type . $suffix,
					'post_content' => 'परीक्षणको लागि नमूना कार्यक्रम सामग्री।',
					'post_excerpt' => 'नमूना कार्यक्रम सारांश।',
				)
			);

			if ( is_wp_error( $new_id ) ) {
				WP_CLI::warning( $new_id->get_error_message() );
				continue;
			}

			pll_set_post_language( $new_id, $lang );

			if ( isset( $term_ids[ $type_name ] ) ) {
				wp_set_object_terms( $new_id, array( $term_ids[ $type_name ] ), 'idp_event_type' );
			}

			update_post_meta( $new_id, 'idp_event_date', get_post_meta( $event->ID, 'idp_event_date', true ) );
			update_post_meta( $new_id, 'idp_event_location', $this->locations[ $location ] ?? $location );
			update_post_meta( $new_id, 'idp_event_capacity', (int) get_post_meta( $event->ID, 'idp_event_capacity', true ) );

			pll_save_post_translations(
				array(
					$default_lang => $event->ID,
					$lang         => $new_id,
				)
			);

			++$created;
		}

		WP_CLI::success( "Created {$created} '{$lang}' translations." );
	}

	/**
	 * Ensure event types have a language and a translated term. Returns English name => translated term ID.
	 *
	 * @param string $default_lang Default language slug.
	 * @param string $lang    Target language slug.
	 * @return int[]
	 */
	private function translate_terms( $default_lang, $lang ) {
		$map   = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'idp_event_type',
				'hide_empty' => false,
				'lang'       => '',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $map;
		}

		foreach ( $terms as $term ) {
			$term_lang = pll_get_term_language( $term->term_id );

			if ( ! $term_lang ) {
				pll_set_term_language( $term->term_id, $default_lang );
				$term_lang = $default_lang;
			}

			if ( $term_lang !== $default_lang ) {
				continue;
			}

			$existing = pll_get_term( $term->term_id, $lang );

			if ( ! $existing ) {
				$result = wp_insert_term( $this->type_names[ $term->name ] ?? $term->name . ' (' . $lang . ')', 'idp_event_type' );

				if ( is_wp_error( $result ) ) {
					WP_CLI::warning( $result->get_error_message() );
					continue;
				}

				$existing = (int) $result['term_id'];
				pll_set_term_language( $existing, $lang );
				pll_save_term_translations(
					array(
						$default_lang => $term->term_id,
						$lang         => $existing,
					)
				);
			}

			$map[ $term->name ] = (int) $existing;
		}

		return $map;
	}

	/**
	 * Stop with an error unless Polylang is active and the language exists.
	 *
	 * @param string $lang Language slug.
	 * @return void
	 */
	private function assert_polylang( $lang ) {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			WP_CLI::error( 'Polylang is not active.' );
		}

		if ( ! in_array( $lang, pll_languages_list(), true ) ) {
			WP_CLI::error( "Language '{$lang}' is not configured in Polylang (Languages > Languages)." );
		}
	}
}

WP_CLI::add_command( 'idp', 'IDP_CPT_AJAX_Seed_Command' );
