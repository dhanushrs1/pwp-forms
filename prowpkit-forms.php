<?php
/**
 * Plugin Name: ProWPKit Forms
 * Plugin URI: https://prowpkit.com
 * Description: A developer-first, secure, and professional form builder for Pro WP Kit.
 * Version: 1.0.0
 * Author: Pro WP Kit Team
 * Text Domain: prowpkit-forms
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'PWP_FORMS_VERSION', '1.0.0' );
define( 'PWP_FORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PWP_FORMS_URL', plugin_dir_url( __FILE__ ) );

// Include Main Class
require_once PWP_FORMS_PATH . 'includes/class-prowpkit-forms.php';

/**
 * Main Instance of ProWPKit Forms
 */
function pwp_forms() {
	return ProWPKit_Forms::get_instance();
}

// Initialize Plugin
pwp_forms();

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, [ 'ProWPKit_Forms', 'activate' ] );
