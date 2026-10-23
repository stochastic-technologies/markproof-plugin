<?php
/**
 * Plugin Name:       Markproof – Accessibility Checks
 * Plugin URI:        https://accessaudit.dev/
 * Description:       Scans your own pages for real accessibility problems (missing alt text, empty links and buttons, broken heading order, unlabelled form fields, tables without headers, iframes without titles, positive tabindex) and explains how to fix each one. Runs entirely on your site: no external service, no account, no tracking, no data leaves your server.
 * Version:           0.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            An AI-operated company, under a named human owner
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       markproof
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* An earlier release of this plugin used a different name and a different folder, and WordPress can
 * end up with both copies present. Two plugins declaring the same classes is a fatal error on
 * activation, so if the older one is loaded, step aside and say so instead. */
if ( class_exists( 'AccessAudit_Store' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Markproof is not running: an older copy of this plugin, published under a previous name, is still active. Deactivate and delete that copy, then activate this one.', 'markproof' ) . '</p></div>';
		}
	);
	return;
}

define( 'STOCHASTIC_SCANNER_VERSION', '0.2.0' );
define( 'STOCHASTIC_SCANNER_FILE', __FILE__ );
define( 'STOCHASTIC_SCANNER_DIR', plugin_dir_path( __FILE__ ) );
define( 'STOCHASTIC_SCANNER_URL', plugin_dir_url( __FILE__ ) );

require_once STOCHASTIC_SCANNER_DIR . 'includes/class-store.php';
require_once STOCHASTIC_SCANNER_DIR . 'includes/class-rules.php';
require_once STOCHASTIC_SCANNER_DIR . 'includes/class-scanner.php';
require_once STOCHASTIC_SCANNER_DIR . 'includes/class-admin.php';
require_once STOCHASTIC_SCANNER_DIR . 'includes/class-rest.php';

/**
 * Free plan limits. The paid add-on (sold separately, hosted outside WordPress.org)
 * raises these through the filters below.
 */
function markproof_max_urls() {
	return (int) apply_filters( 'markproof_max_urls', 50 );
}

function markproof_schedule_enabled() {
	return (bool) apply_filters( 'markproof_schedule_enabled', false );
}

/** Activation: create tables and schedule the rescan event. */
function markproof_activate() {
	Markproof_Store::install();
	if ( markproof_schedule_enabled() && ! wp_next_scheduled( 'markproof_scheduled_scan' ) ) {
		wp_schedule_event( time() + 3600, 'daily', 'markproof_scheduled_scan' );
	}
}
register_activation_hook( __FILE__, 'markproof_activate' );

function markproof_deactivate() {
	wp_clear_scheduled_hook( 'markproof_scheduled_scan' );
}
register_deactivation_hook( __FILE__, 'markproof_deactivate' );

/** Cron: a scheduled scan, only if the paid add-on switched it on. */
add_action(
	'markproof_scheduled_scan',
	function () {
		if ( ! markproof_schedule_enabled() ) {
			return;
		}
		Markproof_Scanner::start_background_scan();
	}
);

/** Admin boot. */
add_action(
	'plugins_loaded',
	function () {
		new Markproof_REST();
		if ( is_admin() ) {
			new Markproof_Admin();
		}
	}
);

/** The editor sidebar: findings for the post you are working on, where you are working on it. */
add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'markproof-editor',
			STOCHASTIC_SCANNER_URL . 'assets/editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ),
			STOCHASTIC_SCANNER_VERSION,
			true
		);
	}
);

/** WP-CLI, for testing and for hosts without cron. */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once STOCHASTIC_SCANNER_DIR . 'includes/class-cli.php';
	WP_CLI::add_command( 'markproof', 'Markproof_CLI' );
}
