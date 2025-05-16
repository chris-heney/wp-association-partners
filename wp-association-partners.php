<?php
/**
 * Plugin Name:     Association Partners
 * Plugin URI:      https://guidestar.consulting
 * Description:     Injects GTM and renders ad zones via shortcode, Elementor, etc.
 * Version:         1.0.0
 * Author:          Guidestar Consulting
 * Author URI:      https://guidestar.consulting
 * Text Domain:     association-partners
 * Domain Path:     /languages
 */

namespace AssociationPartners;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/src/Plugin.php';

// Initialize plugin
add_action( 'plugins_loaded', [ __NAMESPACE__ . '\Plugin', 'get_instance' ] );


