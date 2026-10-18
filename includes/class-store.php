<?php
/**
 * Storage for scans and issues.
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_Store {

	public static function scans_table() {
		global $wpdb;
		return $wpdb->prefix . 'markproof_scans';
	}

	public static function issues_table() {
		global $wpdb;
		return $wpdb->prefix . 'markproof_issues';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$scans = self::scans_table();
		dbDelta(
			"CREATE TABLE $scans (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				started_at datetime NOT NULL,
				finished_at datetime DEFAULT NULL,
				status varchar(20) NOT NULL DEFAULT 'running',
				urls_total int(11) NOT NULL DEFAULT 0,
				urls_done int(11) NOT NULL DEFAULT 0,
				urls_failed int(11) NOT NULL DEFAULT 0,
				triggered_by bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY status (status)
			) $charset;"
		);

		$issues = self::issues_table();
		dbDelta(
			"CREATE TABLE $issues (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				scan_id bigint(20) unsigned NOT NULL,
				url text NOT NULL,
				rule_id varchar(64) NOT NULL,
				severity varchar(10) NOT NULL DEFAULT 'error',
				message text NOT NULL,
				snippet text NOT NULL,
				occurrences int(11) NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				KEY scan_id (scan_id),
				KEY rule_id (rule_id)
			) $charset;"
		);
	}

	/**
	 * Are the tables there? Activation normally creates them, but a site can lose them (a restored
	 * database, a host that skips activation hooks) and "unknown scan" is a terrible thing to say to
	 * somebody who just pressed a button.
	 */
	private static function ensure_installed() {
		global $wpdb;
		static $done = false;
		if ( $done ) {
			return;
		}
		$done  = true;
		$table = self::scans_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one cheap check, and only once per request.
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			self::install();
		}
	}

	public static function create_scan( $url_count, $user_id = 0 ) {
		self::ensure_installed();
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
			self::scans_table(),
			array(
				'started_at'   => current_time( 'mysql', true ),
				'status'       => 'running',
				'urls_total'   => (int) $url_count,
				'urls_done'    => 0,
				'urls_failed'  => 0,
				'triggered_by' => (int) $user_id,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function update_scan( $scan_id, array $data ) {
		global $wpdb;
		$wpdb->update( self::scans_table(), $data, array( 'id' => (int) $scan_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
	}

	public static function get_scan( $scan_id ) {
		global $wpdb;
		// The table name comes from $wpdb->prefix and the value is prepared, so both are safe.
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}markproof_scans WHERE id = %d", (int) $scan_id )
		);
	}

	public static function latest_scan() {
		global $wpdb;
		return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}markproof_scans ORDER BY id DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
	}

	public static function add_issue( $scan_id, $url, $rule_id, $severity, $message, $snippet, $occurrences = 1 ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
			self::issues_table(),
			array(
				'scan_id'      => (int) $scan_id,
				'url'          => $url,
				'rule_id'      => $rule_id,
				'severity'     => $severity,
				'message'      => $message,
				'snippet'      => $snippet,
				'occurrences'  => (int) $occurrences,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
		);
	}

	public static function issues_for_scan( $scan_id, $limit = 500, $rule = '', $offset = 0, $url = '' ) {
		global $wpdb;
		if ( $url ) {
			return $wpdb->get_results(// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, so there is no core object cache to use or invalidate. 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}markproof_issues WHERE scan_id = %d AND url = %s ORDER BY severity DESC, url ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
					(int) $scan_id,
					$url,
					(int) $limit,
					(int) $offset
				)
			);
		}
		if ( $rule ) {
			return $wpdb->get_results(// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, so there is no core object cache to use or invalidate. 
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}markproof_issues WHERE scan_id = %d AND rule_id = %s ORDER BY severity DESC, url ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
					(int) $scan_id,
					$rule,
					(int) $limit,
					(int) $offset
				)
			);
		}
		return $wpdb->get_results(// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, so there is no core object cache to use or invalidate. 
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}markproof_issues WHERE scan_id = %d ORDER BY severity DESC, url ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $scan_id,
				(int) $limit,
				(int) $offset
			)
		);
	}

	public static function summary_for_scan( $scan_id ) {
		global $wpdb;
		return $wpdb->get_results(// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, so there is no core object cache to use or invalidate. 
			$wpdb->prepare(
				"SELECT rule_id, severity, COUNT(DISTINCT url) AS pages, SUM(occurrences) AS items FROM {$wpdb->prefix}markproof_issues WHERE scan_id = %d GROUP BY rule_id, severity ORDER BY items DESC", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $scan_id
			)
		);
	}

	public static function delete_scan( $scan_id ) {
		global $wpdb;
		$wpdb->delete( self::issues_table(), array( 'scan_id' => (int) $scan_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
		$wpdb->delete( self::scans_table(), array( 'id' => (int) $scan_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- own table, no core cache applies.
	}
}
