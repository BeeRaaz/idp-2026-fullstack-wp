<?php
/**
 * Single Event template.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

idp_cpt_ajax_header();
?>
<main class="idp-event-single">
	<?php
	while ( have_posts() ) :
		the_post();
		$idp_date     = get_post_meta( get_the_ID(), 'idp_event_date', true );
		$idp_location = get_post_meta( get_the_ID(), 'idp_event_location', true );
		$idp_capacity = (int) get_post_meta( get_the_ID(), 'idp_event_capacity', true );
		?>
		<article <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<ul class="idp-event-single__details">
				<?php if ( $idp_date ) : ?>
					<li><strong><?php esc_html_e( 'Date:', 'idp-cpt-ajax' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ), $idp_date ) ); ?></li>
				<?php endif; ?>
				<?php if ( $idp_location ) : ?>
					<li><strong><?php esc_html_e( 'Location:', 'idp-cpt-ajax' ); ?></strong> <?php echo esc_html( $idp_location ); ?></li>
				<?php endif; ?>
				<?php if ( $idp_capacity ) : ?>
					<li><strong><?php esc_html_e( 'Capacity:', 'idp-cpt-ajax' ); ?></strong> <?php echo esc_html( number_format_i18n( $idp_capacity ) ); ?></li>
				<?php endif; ?>
			</ul>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
</main>
<?php
idp_cpt_ajax_footer();
