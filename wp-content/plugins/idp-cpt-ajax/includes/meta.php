<?php
/**
 * Event meta fields: registration and admin meta box.
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize a Y-m-d date string; return empty string if invalid.
 *
 * @param string $value Raw date value.
 * @return string
 */
function idp_cpt_ajax_sanitize_date( $value ) {
	$value = sanitize_text_field( (string) $value );
	$date  = DateTime::createFromFormat( 'Y-m-d', $value );

	if ( $date && $date->format( 'Y-m-d' ) === $value ) {
		return $value;
	}

	return '';
}

/**
 * Register meta fields (exposed in REST).
 *
 * @return void
 */
function idp_cpt_ajax_register_meta() {
	$auth = static function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta(
		'idp_event',
		'idp_event_date',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'idp_cpt_ajax_sanitize_date',
			'auth_callback'     => $auth,
		)
	);

	register_post_meta(
		'idp_event',
		'idp_event_location',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth,
		)
	);

	register_post_meta(
		'idp_event',
		'idp_event_capacity',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => $auth,
		)
	);
}
add_action( 'init', 'idp_cpt_ajax_register_meta' );

/**
 * Add the Event Details meta box.
 *
 * @return void
 */
function idp_cpt_ajax_add_meta_box() {
	add_meta_box(
		'idp_event_details',
		__( 'Event Details', 'idp-cpt-ajax' ),
		'idp_cpt_ajax_render_meta_box',
		'idp_event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'idp_cpt_ajax_add_meta_box' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function idp_cpt_ajax_render_meta_box( $post ) {
	wp_nonce_field( 'idp_event_save_meta', 'idp_event_meta_nonce' );

	$date     = get_post_meta( $post->ID, 'idp_event_date', true );
	$location = get_post_meta( $post->ID, 'idp_event_location', true );
	$capacity = get_post_meta( $post->ID, 'idp_event_capacity', true );
	?>
	<p>
		<label for="idp_event_date"><?php esc_html_e( 'Date', 'idp-cpt-ajax' ); ?></label><br>
		<input type="date" id="idp_event_date" name="idp_event_date" value="<?php echo esc_attr( $date ); ?>">
	</p>
	<p>
		<label for="idp_event_location"><?php esc_html_e( 'Location', 'idp-cpt-ajax' ); ?></label><br>
		<input type="text" class="widefat" id="idp_event_location" name="idp_event_location" value="<?php echo esc_attr( $location ); ?>">
	</p>
	<p>
		<label for="idp_event_capacity"><?php esc_html_e( 'Capacity', 'idp-cpt-ajax' ); ?></label><br>
		<input type="number" min="0" id="idp_event_capacity" name="idp_event_capacity" value="<?php echo esc_attr( $capacity ); ?>">
	</p>
	<?php
}

/**
 * Save meta box values.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function idp_cpt_ajax_save_meta( $post_id ) {
	if ( ! isset( $_POST['idp_event_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['idp_event_meta_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'idp_event_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['idp_event_date'] ) ) {
		update_post_meta(
			$post_id,
			'idp_event_date',
			idp_cpt_ajax_sanitize_date( sanitize_text_field( wp_unslash( $_POST['idp_event_date'] ) ) )
		);
	}

	if ( isset( $_POST['idp_event_location'] ) ) {
		update_post_meta(
			$post_id,
			'idp_event_location',
			sanitize_text_field( wp_unslash( $_POST['idp_event_location'] ) )
		);
	}

	if ( isset( $_POST['idp_event_capacity'] ) ) {
		update_post_meta(
			$post_id,
			'idp_event_capacity',
			absint( wp_unslash( $_POST['idp_event_capacity'] ) )
		);
	}
}
add_action( 'save_post_idp_event', 'idp_cpt_ajax_save_meta' );
