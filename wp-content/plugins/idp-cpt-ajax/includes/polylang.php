<?php
/**
 * Polylang integration (multilingual Events).
 *
 * Everything here is a no-op when Polylang is not active.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Make the Event CPT translatable.
 *
 * @param string[] $post_types  Translatable post types.
 * @return string[]
 */
function idp_cpt_ajax_pll_post_types( $post_types ) {
	// Keep the CPT enabled and locked on in Polylang settings.
	$post_types['idp_event'] = 'idp_event';

	return $post_types;
}
add_filter( 'pll_get_post_types', 'idp_cpt_ajax_pll_post_types' );

/**
 * Make the Event Type taxonomy translatable.
 *
 * @param string[] $taxonomies  Translatable taxonomies.
 * @return string[]
 */
function idp_cpt_ajax_pll_taxonomies( $taxonomies ) {
	$taxonomies['idp_event_type'] = 'idp_event_type';

	return $taxonomies;
}
add_filter( 'pll_get_taxonomies', 'idp_cpt_ajax_pll_taxonomies' );

/**
 * Register strings that editors can translate under Languages > Translations.
 *
 * @return void
 */
function idp_cpt_ajax_register_strings() {
	if ( function_exists( 'pll_register_string' ) ) {
		pll_register_string( 'events_intro', 'Browse our upcoming events.', 'IDP Events' );
	}
}
add_action( 'init', 'idp_cpt_ajax_register_strings' );

/**
 * Get the translated intro string (falls back to the default without Polylang).
 *
 * @return string
 */
function idp_cpt_ajax_get_intro() {
	$text = 'Browse our upcoming events.';

	return function_exists( 'pll__' ) ? pll__( $text ) : $text;
}

/**
 * Current language slug, or empty string without Polylang.
 *
 * @return string
 */
function idp_cpt_ajax_current_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language();

		return $lang ? $lang : '';
	}

	return '';
}

/**
 * Validate a language slug against configured languages.
 *
 * @param string $value Raw slug.
 * @return string Valid slug or empty string.
 */
function idp_cpt_ajax_sanitize_lang( $value ) {
	$value = sanitize_key( (string) $value );

	if ( '' !== $value && function_exists( 'pll_languages_list' ) && in_array( $value, pll_languages_list(), true ) ) {
		return $value;
	}

	return '';
}

/**
 * Render a language switcher (Polylang only).
 *
 * @return void
 */
function idp_cpt_ajax_language_switcher() {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return;
	}

	echo '<ul class="idp-lang-switcher">';
	pll_the_languages(
		array(
			'show_flags' => 0,
			'show_names' => 1,
		)
	);
	echo '</ul>';
}
