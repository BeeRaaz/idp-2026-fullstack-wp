<?php
/**
 * Plugin Name: IDP CPT & AJAX Demo
 * Description: Events CPT, taxonomy, meta fields and AJAX/REST filtering for the IDP 2026 WordPress track.
 * Version:     0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author:      Biraj Dulal
 * License:     GPL-2.0-or-later
 * Text Domain: idp-cpt-ajax
 *
 * @package IDP_CPT_AJAX
 */

defined( 'ABSPATH' ) || exit;

define( 'IDP_CPT_AJAX_VERSION', '0.1.0' );
define( 'IDP_CPT_AJAX_DIR', plugin_dir_path( __FILE__ ) );
define( 'IDP_CPT_AJAX_URL', plugin_dir_url( __FILE__ ) );

require_once IDP_CPT_AJAX_DIR . 'includes/post-types.php';
require_once IDP_CPT_AJAX_DIR . 'includes/meta.php';
require_once IDP_CPT_AJAX_DIR . 'includes/templates.php';
require_once IDP_CPT_AJAX_DIR . 'includes/ajax.php';
require_once IDP_CPT_AJAX_DIR . 'includes/rest.php';
require_once IDP_CPT_AJAX_DIR . 'cli/class-idp-cpt-ajax-seed-command.php';

/**
 * Flush rewrite rules on activation so the CPT URLs work immediately.
 *
 * @return void
 */
function idp_cpt_ajax_activate() {
	idp_cpt_ajax_register_post_type();
	idp_cpt_ajax_register_taxonomy();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'idp_cpt_ajax_activate' );

/**
 * Flush rewrite rules on deactivation.
 *
 * @return void
 */
function idp_cpt_ajax_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'idp_cpt_ajax_deactivate' );
