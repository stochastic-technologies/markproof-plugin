<?php
/**
 * WP-CLI command, so a scan can be run from the terminal and tested automatically.
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_CLI {

	/**
	 * Run a full scan now.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Maximum number of pages to check.
	 *
	 * ## EXAMPLES
	 *     wp markproof scan --limit=10
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function scan( $args, $assoc_args ) {
		$limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
		$start = Markproof_Scanner::start_scan( 0, $limit );
		if ( ! isset( $start['scan_id'] ) ) {
			WP_CLI::error( isset( $start['error'] ) ? $start['error'] : 'The scan could not be started.' );
		}
		WP_CLI::log( sprintf( 'Scan %d started over %d pages.', $start['scan_id'], $start['total'] ) );
		if ( 0 === (int) $start['total'] ) {
			WP_CLI::warning( 'There was nothing to check: no published posts or pages were found.' );
			return;
		}
		do {
			$state = Markproof_Scanner::process_batch( $start['scan_id'], 10, 25 );
			if ( isset( $state['error'] ) ) {
				WP_CLI::error( $state['error'] );
			}
			WP_CLI::log( sprintf( '  %d/%d checked, %d failed', (int) $state['done'], (int) $state['total'], (int) $state['failed'] ) );
		} while ( empty( $state['finished'] ) );
		$summary = Markproof_Store::summary_for_scan( $start['scan_id'] );
		WP_CLI::success( sprintf( 'Finished. %d problem types found.', count( $summary ) ) );
		foreach ( $summary as $row ) {
			WP_CLI::log( sprintf( '  %-24s %-8s %5d items on %d pages', $row->rule_id, $row->severity, $row->items, $row->pages ) );
		}
	}

	/**
	 * Print the findings of the latest scan as CSV.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function findings( $args, $assoc_args ) {
		$scan = Markproof_Store::latest_scan();
		if ( ! $scan ) {
			WP_CLI::error( 'No scan found. Run: wp markproof scan' );
		}
		$rows = Markproof_Store::issues_for_scan( $scan->id, 1000 );
		$out  = fopen( 'php://stdout', 'w' );
		// The escape parameter is passed explicitly: PHP 8.4 deprecated relying on the default, and an
		// empty string is the behaviour a CSV reader expects.
		fputcsv( $out, array( 'url', 'rule', 'severity', 'message', 'snippet' ), ',', '"', '' );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row->url, $row->rule_id, $row->severity, $row->message, $row->snippet ), ',', '"', '' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- terminal output, not a file on disk.
		fclose( $out );
	}
}
