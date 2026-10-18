<?php
/**
 * Remove everything the plugin created. Runs only when the user deletes the plugin.
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Table names come from $wpdb->prefix, so they cannot be supplied by a user.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}markproof_issues" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- a drop cannot be prepared.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}markproof_scans" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- a drop cannot be prepared.

$markproof_options = array( 'markproof_scan_queue_%', 'markproof_scan_posts_%', 'markproof_post_counts_%' );
foreach ( $markproof_options as $markproof_like ) {
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $markproof_like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- deleting our own options.
}
delete_option( 'markproof_post_counts_latest' );
delete_option( 'markproof_welcome_dismissed' );
delete_transient( 'markproof_sitemap' );
