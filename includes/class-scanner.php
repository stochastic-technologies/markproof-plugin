<?php
/**
 * Scan orchestration: which pages, fetching them, storing what the rules found.
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_Scanner {

	const OPTION_KEY = 'markproof_scan_queue_';
	const MAP_KEY    = 'markproof_scan_posts_';

	/** The pages we will check: the front page plus every published, public post and page. */
	public static function build_url_list( $limit = 0 ) {
		$limit = $limit ? (int) $limit : markproof_max_urls();
		$urls  = array( home_url( '/' ) => 0 );

		$public     = get_post_types( array( 'public' => true ), 'names' );
		unset( $public['attachment'] );
		$post_types = apply_filters( 'markproof_post_types', array_values( $public ) );
		$ids        = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 400,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		foreach ( $ids as $id ) {
			$url = get_permalink( $id );
			if ( $url ) {
				$urls[ $url ] = (int) $id;
			}
		}

		/* The core sitemap covers archives and anything else a theme exposes publicly. */
		$sitemap = home_url( '/wp-sitemap.xml' );
		$cached  = get_transient( 'markproof_sitemap' );
		if ( false === $cached ) {
			$cached = array();
			$response = wp_remote_get( $sitemap, array( 'timeout' => 15, 'user-agent' => 'Markproof – Accessibility Checks/' . STOCHASTIC_SCANNER_VERSION ) );
			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				if ( preg_match_all( '#<loc>([^<]+)</loc>#i', wp_remote_retrieve_body( $response ), $m ) ) {
					foreach ( $m[1] as $loc ) {
						if ( preg_match( '/wp-sitemap-(posts|pages|taxonomies)-[a-z0-9_-]*-?[0-9]+\.xml$/i', $loc ) ) {
							$sub = wp_remote_get( esc_url_raw( $loc ), array( 'timeout' => 15, 'user-agent' => 'Markproof – Accessibility Checks/' . STOCHASTIC_SCANNER_VERSION ) );
							if ( ! is_wp_error( $sub ) && preg_match_all( '#<loc>([^<]+)</loc>#i', wp_remote_retrieve_body( $sub ), $m2 ) ) {
								foreach ( $m2[1] as $u ) {
									$cached[] = esc_url_raw( $u );
								}
							}
						}
					}
				}
			}
			set_transient( 'markproof_sitemap', $cached, HOUR_IN_SECONDS );
		}
		foreach ( (array) $cached as $u ) {
			if ( ! isset( $urls[ $u ] ) ) {
				$urls[ $u ] = 0;
			}
		}

		$urls = array_slice( $urls, 0, $limit, true );
		return $urls;
	}

	public static function start_scan( $user_id = 0, $limit = 0 ) {
		$list  = self::build_url_list( $limit );
		$urls  = array();
		$map   = array();
		foreach ( $list as $url => $post_id ) {
			$urls[]        = $url;
			$map[ $url ]   = $post_id;
		}
		$scan  = Markproof_Store::create_scan( count( $urls ), $user_id );
		update_option( self::OPTION_KEY . $scan, $urls, false );
		update_option( self::MAP_KEY . $scan, $map, false );
		return array(
			'scan_id' => $scan,
			'total'   => count( $urls ),
		);
	}

	/** Fetch and analyse up to $batch pages of an existing scan. */
	public static function process_batch( $scan_id, $batch = 5, $time_budget = 20 ) {
		$scan = Markproof_Store::get_scan( $scan_id );
		if ( ! $scan ) {
			return array( 'error' => 'unknown scan' );
		}
		$queue_key = self::OPTION_KEY . $scan_id;
		$queue     = get_option( $queue_key, array() );
		$started   = microtime( true );
		$processed = 0;
		$failed    = 0;

		while ( $queue && $processed < $batch && ( microtime( true ) - $started ) < $time_budget ) {
			$url = array_shift( $queue );
			$result = self::scan_url( $scan_id, $url );
			if ( is_wp_error( $result ) ) {
				$failed++;
			}
			$processed++;
		}

		update_option( $queue_key, $queue, false );
		$done = (int) $scan->urls_done + $processed;
		$data = array(
			'urls_done'   => $done,
			'urls_failed' => (int) $scan->urls_failed + $failed,
		);
		if ( ! $queue ) {
			$data['status']      = 'finished';
			$data['finished_at'] = current_time( 'mysql', true );
			delete_option( $queue_key );
			self::store_post_counts( $scan_id );
		}
		Markproof_Store::update_scan( $scan_id, $data );

		return array(
			'scan_id'   => (int) $scan_id,
			'done'      => $done,
			'total'     => (int) $scan->urls_total,
			'remaining' => count( $queue ),
			'failed'    => (int) $scan->urls_failed + $failed,
			'finished'  => ! $queue,
		);
	}

	/** How many findings each post has, so the Posts screen can show it at a glance. */
	public static function store_post_counts( $scan_id ) {
		global $wpdb;
		$map    = get_option( self::MAP_KEY . $scan_id, array() );
		$counts = array();
		if ( $map ) {
			$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, no core cache applies.
				$wpdb->prepare( "SELECT url, SUM(occurrences) AS n FROM {$wpdb->prefix}markproof_issues WHERE scan_id = %d GROUP BY url", (int) $scan_id )
			);
			foreach ( $rows as $row ) {
				$post_id = isset( $map[ $row->url ] ) ? (int) $map[ $row->url ] : 0;
				if ( $post_id ) {
					$counts[ $post_id ] = (int) $row->n;
				}
			}
		}
		update_option( 'markproof_post_counts_' . $scan_id, $counts, false );
		update_option( 'markproof_post_counts_latest', array( 'scan_id' => (int) $scan_id, 'counts' => $counts ), false );
	}

	/** The edit link for a scanned URL, when that URL belongs to a post or page. */
	public static function edit_link( $scan_id, $url ) {
		$map = get_option( self::MAP_KEY . $scan_id, array() );
		if ( empty( $map[ $url ] ) ) {
			return '';
		}
		return get_edit_post_link( (int) $map[ $url ] );
	}

	/** Fetch one page and store what we found. Only our own host is ever fetched. */
	public static function scan_url( $scan_id, $url ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host      = wp_parse_url( $url, PHP_URL_HOST );
		if ( $host && $home_host && strtolower( $host ) !== strtolower( $home_host ) ) {
			return new WP_Error( 'markproof_foreign_host', __( 'Refusing to scan a different host.', 'markproof' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'user-agent'  => 'Markproof – Accessibility Checks/' . STOCHASTIC_SCANNER_VERSION . '; accessibility audit started by the site owner',
				'headers'     => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		$html   = '';
		$noted  = false;
		if ( ! is_wp_error( $response ) ) {
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 200 && $code < 400 ) {
				$html = wp_remote_retrieve_body( $response );
			}
		}

		if ( '' === $html ) {
			/* Many hosts cannot fetch their own front end. Fall back to the stored content of the
			 * post or page, and say so, because that markup can differ from what a visitor sees. */
			$map     = get_option( self::MAP_KEY . $scan_id, array() );
			$post_id = isset( $map[ $url ] ) ? (int) $map[ $url ] : 0;
			$post    = $post_id ? get_post( $post_id ) : null;
			if ( ! $post ) {
				return is_wp_error( $response ) ? $response : new WP_Error( 'markproof_http', 'page could not be fetched' );
			}
			$html  = '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><title>' . esc_html( get_the_title( $post ) ) . '</title></head><body>'
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'the_content' is WordPress's own filter, applied here so we check the page as WordPress renders it.
				. apply_filters( 'the_content', $post->post_content ) . '</body></html>';
			$noted = true;
		}

		/* A page far larger than any real document would eat the host's PHP memory limit. Rather
		 * than risk taking a site down, we skip it and say so. */
		if ( strlen( $html ) > 5000000 ) {
			Markproof_Store::add_issue(
				$scan_id,
				$url,
				'page_too_large',
				'warning',
				__( 'This page is larger than 5 MB of HTML, so it was skipped rather than risk the server running out of memory.', 'markproof' ),
				'',
				1
			);
			return 0;
		}

		$issues = Markproof_Rules::analyse( $html );
		if ( $noted ) {
			Markproof_Store::add_issue(
				$scan_id,
				$url,
				'scanned_from_stored_content',
				'warning',
				__( 'This page could not be fetched by the server, so the stored content of the post was checked instead. The live page may differ.', 'markproof' ),
				'',
				1
			);
		}

		/* Group identical findings on the same page so the report stays readable. */
		$counts = array();
		foreach ( $issues as $issue ) {
			$key = $issue['rule_id'] . '|' . $issue['snippet'];
			if ( ! isset( $counts[ $key ] ) ) {
				$counts[ $key ] = $issue;
				$counts[ $key ]['occurrences'] = 0;
			}
			$counts[ $key ]['occurrences']++;
		}
		foreach ( $counts as $issue ) {
			Markproof_Store::add_issue(
				$scan_id,
				$url,
				$issue['rule_id'],
				$issue['severity'],
				$issue['message'],
				$issue['snippet'],
				$issue['occurrences']
			);
		}
		return count( $issues );
	}

	/** Cron path: run a whole scan in chunks without blowing up a single request. */
	public static function start_background_scan() {
		$start = self::start_scan( 0 );
		$guard = 0;
		do {
			$state = self::process_batch( $start['scan_id'], 10, 20 );
			$guard++;
		} while ( ! $state['finished'] && $guard < 40 );
		return $state;
	}
}
