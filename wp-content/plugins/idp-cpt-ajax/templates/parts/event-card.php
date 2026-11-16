<?php
/**
 * Event card (used in archive and AJAX responses).
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

$idp_date     = get_post_meta( get_the_ID(), 'idp_event_date', true );
$idp_location = get_post_meta( get_the_ID(), 'idp_event_location', true );
$idp_types    = get_the_term_list( get_the_ID(), 'idp_event_type', '', ', ' );
?>
<article <?php post_class( 'idp-event-card' ); ?>>
	<h2 class="idp-event-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<p class="idp-event-card__meta">
		<?php if ( $idp_date ) : ?>
			<time datetime="<?php echo esc_attr( $idp_date ); ?>"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $idp_date ) ); ?></time>
		<?php endif; ?>
		<?php if ( $idp_location ) : ?>
			<span class="idp-event-card__location"><?php echo esc_html( $idp_location ); ?></span>
		<?php endif; ?>
	</p>
	<?php if ( $idp_types && ! is_wp_error( $idp_types ) ) : ?>
		<p class="idp-event-card__types"><?php echo wp_kses_post( $idp_types ); ?></p>
	<?php endif; ?>
	<div class="idp-event-card__excerpt"><?php the_excerpt(); ?></div>
</article>
