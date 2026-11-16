<?php
/**
 * Template loading for the Event CPT (works with classic and block themes).
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Use plugin templates for Event archive and single views unless the theme overrides them.
 *
 * @param string $template Current template path.
 * @return string
 */
function idp_cpt_ajax_template_include( $template ) {
	if ( is_post_type_archive( 'idp_event' ) || is_tax( 'idp_event_type' ) ) {
		return idp_cpt_ajax_locate_template( 'archive-idp-event.php', $template );
	}

	if ( is_singular( 'idp_event' ) ) {
		return idp_cpt_ajax_locate_template( 'single-idp-event.php', $template );
	}

	return $template;
}
add_filter( 'template_include', 'idp_cpt_ajax_template_include' );

/**
 * Locate a template: theme override first (idp-cpt-ajax/<file>), then the plugin copy.
 *
 * @param string $file     Template file name.
 * @param string $fallback Fallback path.
 * @return string
 */
function idp_cpt_ajax_locate_template( $file, $fallback ) {
	$theme_file = locate_template( array( 'idp-cpt-ajax/' . $file ) );

	if ( $theme_file ) {
		return $theme_file;
	}

	$plugin_file = IDP_CPT_AJAX_DIR . 'templates/' . $file;

	return file_exists( $plugin_file ) ? $plugin_file : $fallback;
}

/**
 * Output the site header for both classic and block themes.
 *
 * @return void
 */
function idp_cpt_ajax_header() {
	if ( ! wp_is_block_theme() ) {
		get_header();
		return;
	}
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	echo '<div class="wp-site-blocks">';
	echo do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Output the site footer for both classic and block themes.
 *
 * @return void
 */
function idp_cpt_ajax_footer() {
	if ( ! wp_is_block_theme() ) {
		get_footer();
		return;
	}

	echo do_blocks( '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
	wp_footer();
	echo '</body></html>';
}
