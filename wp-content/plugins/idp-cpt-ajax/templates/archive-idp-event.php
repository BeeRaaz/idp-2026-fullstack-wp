<?php
/**
 * Event archive template with AJAX filters.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

idp_cpt_ajax_header();

$idp_types = get_terms(
	array(
		'taxonomy'   => 'idp_event_type',
		'hide_empty' => true,
	)
);
?>
<main class="idp-events-archive">
	<h1><?php esc_html_e( 'Events', 'idp-cpt-ajax' ); ?></h1>

	<form class="idp-events-filter" id="idp-events-filter" method="get">
		<label>
			<span><?php esc_html_e( 'Search', 'idp-cpt-ajax' ); ?></span>
			<input type="search" name="search">
		</label>
		<label>
			<span><?php esc_html_e( 'Type', 'idp-cpt-ajax' ); ?></span>
			<select name="type">
				<option value=""><?php esc_html_e( 'All types', 'idp-cpt-ajax' ); ?></option>
				<?php if ( ! is_wp_error( $idp_types ) ) : ?>
					<?php foreach ( $idp_types as $idp_type ) : ?>
						<option value="<?php echo esc_attr( $idp_type->slug ); ?>"><?php echo esc_html( $idp_type->name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'From date', 'idp-cpt-ajax' ); ?></span>
			<input type="date" name="date_from">
		</label>
		<button type="submit"><?php esc_html_e( 'Filter', 'idp-cpt-ajax' ); ?></button>
	</form>

	<div id="idp-events-results" aria-live="polite">
		<?php
		$idp_result = idp_cpt_ajax_query_events( array() );
		echo $idp_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped template parts.
		?>
	</div>

	<button type="button" id="idp-events-more" data-page="1" data-max="<?php echo esc_attr( $idp_result['max_pages'] ); ?>"<?php echo $idp_result['max_pages'] > 1 ? '' : ' hidden'; ?>>
		<?php esc_html_e( 'Load more', 'idp-cpt-ajax' ); ?>
	</button>
</main>
<?php
idp_cpt_ajax_footer();
